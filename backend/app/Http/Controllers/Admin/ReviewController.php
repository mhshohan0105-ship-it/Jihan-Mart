<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Customer reviews from the storefront: new ones wait here as "pending" until approved. */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::query()->with(['product:id,name,slug,thumbnail_upload_id,image_url', 'product.thumbnail', 'photo', 'order:id,code'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->rating))
            ->when($request->input('type') === 'store', fn ($q) => $q->whereNull('product_id'))
            ->when($request->input('type') === 'product', fn ($q) => $q->whereNotNull('product_id'))
            ->when($request->boolean('photo'), fn ($q) => $q->whereNotNull('photo_upload_id'))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('comment', 'like', '%'.$request->search.'%')
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->search.'%'))))
            ->when($request->input('sort') === 'rating_desc', fn ($q) => $q->orderByDesc('rating'))
            ->when($request->input('sort') === 'rating_asc', fn ($q) => $q->orderBy('rating'))
            ->latest('id')
            ->paginate(15)->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'counts' => Review::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function status(Request $request, Review $review): JsonResponse
    {
        $review->update($request->validate(['status' => ['required', Rule::in(Review::STATUSES)]]));

        return response()->json(['success' => true, 'message' => __('Review :status', ['status' => __($review->status)])]);
    }

    public function reply(Request $request, Review $review): JsonResponse
    {
        $review->update($request->validate(['admin_reply' => ['nullable', 'string', 'max:1000']]));

        return response()->json(['success' => true, 'message' => __('Reply saved')]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json(['success' => true, 'message' => __('Review deleted')]);
    }

    /** "Auto approve new reviews" switch. */
    public function autoApprove(Request $request): JsonResponse
    {
        BusinessSetting::put('review_auto_approve', $request->boolean('value') ? '1' : '0');

        return response()->json(['success' => true, 'message' => __('Updated successfully')]);
    }
}
