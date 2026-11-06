<?php

namespace App\Services;

use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

/** Saves a file into the media library. Raster images are resized (max 2000px) and stored as .webp. */
class UploadService
{
    public const MAX_SIDE = 2000;

    public const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif'];

    public const DOCUMENT_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    public const VIDEO_EXT = ['mp4', 'webm', 'mov'];

    public function store(UploadedFile $file, ?int $userId = null): Upload
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $dir = 'uploads/'.now()->format('Y/m');
        $base = Str::uuid()->toString();

        // animated gifs would lose their frames, keep them as they are
        if (in_array($ext, self::IMAGE_EXT, true) && $ext !== 'gif') {
            try {
                return $this->storeAsWebp($file, $dir, $base, $original, $userId);
            } catch (Throwable $e) {
                report($e); // unreadable image: fall through and keep the original file
            }
        }

        $path = $file->storeAs($dir, $base.'.'.$ext, 'public');
        [$width, $height] = $ext === 'gif' ? (@getimagesize($file->getRealPath()) ?: [null, null]) : [null, null];

        return Upload::query()->create([
            'user_id' => $userId,
            'file_original_name' => Str::limit($original, 190, ''),
            'file_name' => $path,
            'extension' => $ext,
            'type' => $this->typeOf($ext),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);
    }

    private function storeAsWebp(UploadedFile $file, string $dir, string $base, string $original, ?int $userId): Upload
    {
        $image = ImageManager::usingDriver(Driver::class)->decodePath($file->getRealPath());
        $image->scaleDown(self::MAX_SIDE, self::MAX_SIDE);
        $encoded = $image->encode(new WebpEncoder(quality: 82, strip: true))->toString();

        $path = $dir.'/'.$base.'.webp';
        Storage::disk('public')->put($path, $encoded);

        return Upload::query()->create([
            'user_id' => $userId,
            'file_original_name' => Str::limit($original, 190, ''),
            'file_name' => $path,
            'extension' => 'webp',
            'type' => 'image',
            'file_size' => strlen($encoded),
            'width' => $image->width(),
            'height' => $image->height(),
        ]);
    }

    public function delete(Upload $upload): void
    {
        Storage::disk('public')->delete($upload->file_name);
        $upload->delete();
    }

    public function typeOf(string $ext): string
    {
        return match (true) {
            in_array($ext, self::IMAGE_EXT, true) => 'image',
            in_array($ext, self::DOCUMENT_EXT, true) => 'document',
            in_array($ext, self::VIDEO_EXT, true) => 'video',
            default => 'other',
        };
    }

    public static function allowedExtensions(): array
    {
        return [...self::IMAGE_EXT, ...self::DOCUMENT_EXT, ...self::VIDEO_EXT];
    }
}
