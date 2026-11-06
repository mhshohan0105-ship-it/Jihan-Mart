<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Couriers\CourierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Delivery status pushed by Steadfast (set this URL + bearer token in the Steadfast panel → Webhook). */
class CourierWebhookController extends Controller
{
    public function steadfast(Request $request, CourierService $couriers): JsonResponse
    {
        $secret = config('couriers.steadfast.webhook_secret');
        if (! $secret || ! hash_equals($secret, (string) $request->bearerToken())) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $order = $couriers->applySteadfastUpdate($request->all());

        return response()->json(['status' => 'success', 'message' => $order ? 'Order updated' : 'Order not found']);
    }
}
