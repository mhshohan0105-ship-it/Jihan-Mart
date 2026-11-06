<?php

namespace App\Services\Couriers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Steadfast (Packzy) merchant API.
 * Endpoints and headers match Steadfast's own WordPress plugin: base https://portal.packzy.com/api/v1,
 * headers `api-key` and `secret-key`.
 */
class SteadfastClient
{
    public function configured(): bool
    {
        return filled(config('couriers.steadfast.api_key')) && filled(config('couriers.steadfast.secret_key'));
    }

    /**
     * @param  array{invoice:string, recipient_name:string, recipient_phone:string, recipient_address:string, cod_amount:float, note?:string}  $parcel
     * @return array{consignment_id:string, tracking_code:?string, status:?string}
     */
    public function createOrder(array $parcel): array
    {
        $res = $this->http()->post('create_order', $parcel)->json();
        if ((int) ($res['status'] ?? 0) !== 200 || empty($res['consignment']['consignment_id'])) {
            throw new RuntimeException($this->errorMessage($res));
        }

        return [
            'consignment_id' => (string) $res['consignment']['consignment_id'],
            'tracking_code' => $res['consignment']['tracking_code'] ?? null,
            'status' => $res['consignment']['status'] ?? null,
        ];
    }

    public function balance(): float
    {
        $res = $this->http()->get('get_balance')->json();
        if (! isset($res['current_balance'])) {
            throw new RuntimeException($this->errorMessage($res));
        }

        return (float) $res['current_balance'];
    }

    public function statusByConsignment(string $id): ?string
    {
        return $this->http()->get('status_by_cid/'.urlencode($id))->json('delivery_status');
    }

    /** Customer delivery history across Steadfast ("Delivery Risk Check"). */
    public function fraudScore(string $phone): array
    {
        $response = $this->http()->get('fraud_check/score/'.$phone);
        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException(__('Steadfast rejected the API keys.'));
        }
        $res = $response->json() ?? [];
        $total = (int) ($res['total_parcels'] ?? 0);
        $delivered = (int) ($res['total_delivered'] ?? $res['delivered_count'] ?? 0);
        $cancelled = (int) ($res['total_cancelled'] ?? $res['cancelled_count'] ?? 0);

        return [
            'total' => $total,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'success_rate' => $total ? round($delivered / $total * 100, 1) : null,
            'reports' => (int) ($res['total_reports'] ?? 0),
        ];
    }

    private function http(): PendingRequest
    {
        if (! $this->configured()) {
            throw new RuntimeException(__('Steadfast API keys are not set. Add them in Shipping → Courier API.'));
        }

        return Http::baseUrl(rtrim(config('couriers.steadfast.base_url'), '/').'/')
            ->withHeaders(['api-key' => config('couriers.steadfast.api_key'), 'secret-key' => config('couriers.steadfast.secret_key')])
            ->acceptJson()->asJson()->timeout(30);
    }

    private function errorMessage(?array $res): string
    {
        if (! empty($res['errors'])) {
            return collect($res['errors'])->flatten()->implode(' ');
        }

        return (string) ($res['message'] ?? __('Steadfast did not accept the request.'));
    }
}
