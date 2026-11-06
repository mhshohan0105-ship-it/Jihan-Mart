<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadController extends Controller
{
    public function __construct(private UploadService $uploads) {}

    public function index(Request $request): View
    {
        return view('admin.uploads.index', [
            'files' => $this->query($request)->paginate(30)->withQueryString(),
            'sort' => $request->input('sort', 'newest'),
            'search' => $request->input('search'),
        ]);
    }

    public function create(): View
    {
        return view('admin.uploads.create', ['accept' => UploadService::allowedExtensions()]);
    }

    /** One file per request (the uploader sends files one by one to show progress). */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:'.implode(',', UploadService::allowedExtensions())],
        ]);

        $upload = $this->uploads->store($request->file('file'), $request->user()->id);

        return response()->json(['success' => true, 'message' => __('File uploaded'), 'data' => $upload]);
    }

    /** Media picker feed. */
    public function json(Request $request): JsonResponse
    {
        $page = $this->query($request)->paginate(24);

        return response()->json([
            'data' => $page->items(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    /** Previews for ids already stored in a field. */
    public function byIds(Request $request): JsonResponse
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->input('ids'))));
        $files = Upload::query()->whereIn('id', $ids)->get()->sortBy(fn ($f) => array_search($f->id, $ids))->values();

        return response()->json(['data' => $files]);
    }

    public function download(Upload $upload): StreamedResponse
    {
        return Storage::disk('public')->download($upload->file_name, $upload->file_original_name.'.'.$upload->extension);
    }

    public function destroy(Request $request, Upload $upload): JsonResponse|RedirectResponse
    {
        $this->uploads->delete($upload);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => __('File deleted')])
            : back()->with('success', __('File deleted'));
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        Upload::query()->whereIn('id', $ids)->get()->each(fn (Upload $u) => $this->uploads->delete($u));

        return response()->json(['success' => true, 'message' => __('Selected files deleted')]);
    }

    private function query(Request $request): Builder
    {
        $q = Upload::query();
        if ($search = trim((string) $request->input('search'))) {
            $q->where('file_original_name', 'like', '%'.$search.'%');
        }
        if ($type = $request->input('type')) {
            $q->where('type', $type);
        }

        return match ($request->input('sort', 'newest')) {
            'oldest' => $q->oldest('id'),
            'smallest' => $q->orderBy('file_size'),
            'largest' => $q->orderByDesc('file_size'),
            default => $q->latest('id'),
        };
    }
}
