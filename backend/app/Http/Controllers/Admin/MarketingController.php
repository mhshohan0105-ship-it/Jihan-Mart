<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** Subscribers, newsletter and the Facebook / Google product catalog feed. */
class MarketingController extends Controller
{
    public function subscribers(Request $request): View
    {
        $subscribers = Subscriber::query()
            ->when($request->filled('search'), fn ($q) => $q->where('email', 'like', '%'.$request->search.'%')->orWhere('phone', 'like', '%'.$request->search.'%'))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.marketing.subscribers', compact('subscribers'));
    }

    public function destroySubscriber(Subscriber $subscriber): JsonResponse
    {
        $subscriber->delete();

        return response()->json(['success' => true, 'message' => __('Subscriber deleted')]);
    }

    public function newsletter(): View
    {
        return view('admin.marketing.newsletter', [
            'subscriberEmails' => Subscriber::query()->whereNotNull('email')->count(),
            'customers' => User::query()->where('type', User::TYPE_CUSTOMER)->whereNotNull('email')->orderBy('name')->get(['id', 'name', 'email']),
            'phoneOnly' => Subscriber::query()->whereNull('email')->count(),
            'mailer' => config('mail.default'),
        ]);
    }

    /** Sends one email per recipient (BCC is avoided so addresses stay private). */
    public function sendNewsletter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string'],
            'to_subscribers' => ['nullable', 'boolean'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
        ]);
        $emails = collect();
        if ($request->boolean('to_subscribers')) {
            $emails = $emails->merge(Subscriber::query()->whereNotNull('email')->pluck('email'));
        }
        if (! empty($data['user_ids'])) {
            $emails = $emails->merge(User::query()->whereIn('id', $data['user_ids'])->whereNotNull('email')->pluck('email'));
        }
        $emails = $emails->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->unique()->values();
        if ($emails->isEmpty()) {
            return back()->withInput()->with('error', __('No email address selected.'));
        }

        $sent = 0;
        foreach ($emails as $email) {
            try {
                Mail::html($data['content'], fn ($m) => $m->to($email)->subject($data['subject']));
                $sent++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return back()->with($sent ? 'success' : 'error', __('Newsletter sent to :sent of :total addresses', ['sent' => $sent, 'total' => $emails->count()]));
    }

    /** Facebook / Google catalog CSV (also public at /feeds/products.csv). */
    public static function feed(): StreamedResponse
    {
        $products = Product::query()->where('published', true)->with(['brand:id,name', 'thumbnail'])->orderBy('id');

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'title', 'description', 'availability', 'condition', 'price', 'sale_price', 'link', 'image_link', 'brand']);
            $products->chunk(300, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    $final = $p->finalPrice();
                    fputcsv($out, [
                        $p->id, $p->name, Str::limit(trim(strip_tags((string) $p->description)) ?: $p->name, 4900, ''),
                        $p->current_stock > 0 ? 'in stock' : 'out of stock', 'new',
                        number_format((float) $p->unit_price, 2, '.', '').' BDT',
                        $final < (float) $p->unit_price ? number_format($final, 2, '.', '').' BDT' : '',
                        url('/product/'.$p->slug), $p->thumbnailUrl(), $p->brand?->name ?? get_setting('site_name', 'Jihan Mart'),
                    ]);
                }
            });
            fclose($out);
        }, 'products-feed.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
