<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\City;
use App\Models\Order;
use App\Services\Couriers\CourierService;
use App\Services\Couriers\FraudCheckService;
use App\Services\Couriers\PathaoClient;
use App\Services\Couriers\SteadfastClient;
use App\Support\EnvWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class CourierController extends Controller
{
    public function settings(SteadfastClient $steadfast, PathaoClient $pathao): View
    {
        $mask = fn (string $key) => EnvWriter::mask(config($key));

        return view('admin.shipping.couriers', [
            'steadfastOn' => $steadfast->configured(),
            'pathaoOn' => $pathao->configured(),
            'masked' => [
                'STEADFAST_API_KEY' => $mask('couriers.steadfast.api_key'), 'STEADFAST_SECRET_KEY' => $mask('couriers.steadfast.secret_key'),
                'STEADFAST_WEBHOOK_SECRET' => $mask('couriers.steadfast.webhook_secret'),
                'PATHAO_CLIENT_ID' => $mask('couriers.pathao.client_id'), 'PATHAO_CLIENT_SECRET' => $mask('couriers.pathao.client_secret'),
                'PATHAO_USERNAME' => config('couriers.pathao.username'), 'PATHAO_PASSWORD' => $mask('couriers.pathao.password'),
                'PATHAO_STORE_ID' => config('couriers.pathao.store_id'),
            ],
            'webhookUrl' => route('api.webhooks.steadfast'),
        ]);
    }

    /** Secrets go to .env; empty inputs keep the current value. */
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'default_courier' => ['required', Rule::in(array_keys(CourierService::COURIERS))],
            'courier_sent_status' => ['nullable', Rule::in(array_keys(Order::DELIVERY_STATUSES))],
        ]);
        BusinessSetting::put('default_courier', $request->input('default_courier'));
        BusinessSetting::put('courier_sent_status', $request->input('courier_sent_status') ?: '');

        $values = [];
        foreach (['STEADFAST_API_KEY', 'STEADFAST_SECRET_KEY', 'STEADFAST_WEBHOOK_SECRET', 'PATHAO_CLIENT_ID', 'PATHAO_CLIENT_SECRET', 'PATHAO_USERNAME', 'PATHAO_PASSWORD', 'PATHAO_STORE_ID'] as $key) {
            $value = trim((string) $request->input($key));
            if ($value !== '') {
                $values[$key] = $value;
            }
        }
        if ($request->boolean('generate_webhook_secret')) {
            $values['STEADFAST_WEBHOOK_SECRET'] = Str::random(40);
        }
        EnvWriter::set($values);
        Artisan::call('config:clear');
        Cache::forget('pathao_access_token');

        return back()->with('success', __('Courier settings saved'));
    }

    public function test(string $courier, SteadfastClient $steadfast, PathaoClient $pathao): JsonResponse
    {
        try {
            $message = match ($courier) {
                'steadfast' => __('Connected. Steadfast balance: :amount', ['amount' => format_price($steadfast->balance())]),
                'pathao' => __('Connected. Pathao stores: :stores', ['stores' => collect($pathao->stores())->pluck('store_name')->implode(', ') ?: '—']),
                default => abort(404),
            };

            return response()->json(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Send one or many orders (bulk) to a courier. */
    public function send(Request $request, CourierService $couriers): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'courier' => ['nullable', Rule::in(array_keys(CourierService::COURIERS))],
        ]);
        $orders = Order::query()->whereIn('id', $data['ids'])->with(['state', 'city', 'details'])->get();
        $result = $couriers->sendMany($orders, $data['courier'] ?? null, $request->user());

        $message = __(':sent sent to courier', ['sent' => $result['sent']]);
        if ($result['failed']) {
            $message .= '. '.implode(' | ', array_slice($result['failed'], 0, 5));
        }

        return response()->json(['success' => $result['sent'] > 0, 'message' => $message, 'failed' => $result['failed']], $result['sent'] || ! $result['failed'] ? 200 : 422);
    }

    public function fraud(Request $request, FraudCheckService $fraud): JsonResponse
    {
        $phone = cleanBdPhone($request->input('phone'));
        abort_unless($phone, 422, __('Invalid phone number'));

        return response()->json(['success' => true, 'data' => $fraud->check($phone, $request->boolean('force_refresh'))]);
    }

    public function fraudBatch(Request $request, FraudCheckService $fraud): JsonResponse
    {
        $phones = array_filter(array_map(fn ($p) => cleanBdPhone($p), (array) $request->input('phones', [])));

        return response()->json(['data' => $fraud->batch(array_slice($phones, 0, 50))]);
    }

    /** Pathao location lists for mapping our areas (Shipping Cities). */
    public function pathaoLocations(Request $request, PathaoClient $pathao): JsonResponse
    {
        try {
            $data = match ($request->input('type')) {
                'zones' => collect($pathao->zones($request->integer('id')))->map(fn ($z) => ['id' => $z['zone_id'], 'name' => $z['zone_name']]),
                'areas' => collect($pathao->areas($request->integer('id')))->map(fn ($a) => ['id' => $a['area_id'], 'name' => $a['area_name']]),
                default => collect($pathao->cities())->map(fn ($c) => ['id' => $c['city_id'], 'name' => $c['city_name']]),
            };

            return response()->json(['data' => $data->values()]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function savePathaoMapping(Request $request, City $city): JsonResponse
    {
        $city->update($request->validate([
            'pathao_city_id' => ['nullable', 'integer'],
            'pathao_zone_id' => ['nullable', 'integer'],
            'pathao_area_id' => ['nullable', 'integer'],
        ]));

        return response()->json(['success' => true, 'message' => __('Pathao area saved')]);
    }
}
