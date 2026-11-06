<?php

namespace App\Services\Couriers;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use RuntimeException;
use Throwable;

/** "Send Courier": books orders with Steadfast or Pathao and stores consignment + tracking on the order. */
class CourierService
{
    public const COURIERS = ['steadfast' => 'Steadfast', 'pathao' => 'Pathao'];

    /** Steadfast webhook status → our delivery status (null = only log it). */
    public const STEADFAST_STATUS = [
        'delivered' => 'delivered',
        'partial_delivered' => 'delivered',
        'cancelled' => 'returned',
        'hold' => 'on_the_way',
        'in_review' => 'on_the_way',
        'pending' => 'on_the_way',
    ];

    public function __construct(private SteadfastClient $steadfast, private PathaoClient $pathao, private OrderService $orders) {}

    /**
     * @param  iterable<Order>  $orders
     * @return array{sent:int, failed:array<string>}
     */
    public function sendMany(iterable $orders, ?string $courier, ?User $staff): array
    {
        $sent = 0;
        $failed = [];
        foreach ($orders as $order) {
            try {
                $this->send($order, $courier, $staff);
                $sent++;
            } catch (Throwable $e) {
                $failed[] = "#{$order->code}: ".$e->getMessage();
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function send(Order $order, ?string $courier, ?User $staff): Order
    {
        $courier = $courier ?: get_setting('default_courier', 'steadfast');
        if (! isset(self::COURIERS[$courier])) {
            throw new RuntimeException(__('Unknown courier.'));
        }
        if ($order->consignment_id) {
            throw new RuntimeException(__('Already sent to :courier (:id).', ['courier' => self::COURIERS[$order->courier] ?? $order->courier, 'id' => $order->consignment_id]));
        }
        if (in_array($order->delivery_status, ['cancelled', 'returned', 'delivered'], true)) {
            throw new RuntimeException(__('This order is :status.', ['status' => __(Order::DELIVERY_STATUSES[$order->delivery_status])]));
        }

        $result = $courier === 'pathao' ? $this->pathao->createOrder($this->pathaoParcel($order)) : $this->steadfast->createOrder($this->steadfastParcel($order));

        $order->update(['courier' => $courier, 'consignment_id' => $result['consignment_id'], 'tracking_code' => $result['tracking_code'] ?? $order->tracking_code]);
        $this->orders->log($order, 'courier_sent', __('Sent to :courier', ['courier' => self::COURIERS[$courier]]), null, $result['consignment_id'], $result['tracking_code'] ? 'Tracking: '.$result['tracking_code'] : null, $staff);

        $next = get_setting('courier_sent_status', 'picked_up');
        if ($next && isset(Order::DELIVERY_STATUSES[$next]) && $order->delivery_status !== $next) {
            $this->orders->changeDeliveryStatus($order->fresh(), $next, $staff);
        }

        return $order->fresh();
    }

    /** Apply a Steadfast webhook. Returns the order it changed, or null. */
    public function applySteadfastUpdate(array $payload): ?Order
    {
        $order = null;
        if (! empty($payload['consignment_id'])) {
            $order = Order::query()->where('consignment_id', (string) $payload['consignment_id'])->first();
        }
        if (! $order && ! empty($payload['invoice'])) {
            $order = Order::query()->where('code', (int) preg_replace('/\D/', '', (string) $payload['invoice']))->first();
        }
        if (! $order) {
            return null;
        }

        $raw = (string) ($payload['status'] ?? '');
        $message = $payload['tracking_message'] ?? null;
        $mapped = self::STEADFAST_STATUS[$raw] ?? null;

        if ($mapped && $mapped !== $order->delivery_status) {
            $this->orders->changeDeliveryStatus($order, $mapped);
        }
        $this->orders->log($order, 'courier_update', __('Steadfast update'), null, $raw ?: null, $message);

        return $order;
    }

    public function trackingUrl(Order $order): ?string
    {
        return match ($order->courier) {
            'steadfast' => $order->tracking_code ? config('couriers.steadfast.tracking_url').$order->tracking_code : null,
            'pathao' => $order->consignment_id ? 'https://merchant.pathao.com/tracking?consignment_id='.$order->consignment_id.'&phone='.$order->phone : null,
            default => null,
        };
    }

    private function steadfastParcel(Order $order): array
    {
        return [
            'invoice' => (string) $order->code,
            'recipient_name' => $order->name,
            'recipient_phone' => $order->phone,
            'recipient_address' => trim($order->address.', '.$order->areaLabel(), ', '),
            'cod_amount' => (float) $order->due,
            'note' => (string) $order->admin_note,
        ];
    }

    private function pathaoParcel(Order $order): array
    {
        $city = $order->city;
        if (! $city?->pathao_city_id || ! $city->pathao_zone_id) {
            throw new RuntimeException(__('Area ":area" is not linked to a Pathao city/zone (Shipping Cities).', ['area' => $city?->name ?? '—']));
        }

        return [
            'merchant_order_id' => (string) $order->code,
            'recipient_name' => $order->name,
            'recipient_phone' => $order->phone,
            'recipient_address' => $order->address,
            'recipient_city' => $city->pathao_city_id,
            'recipient_zone' => $city->pathao_zone_id,
            'recipient_area' => $city->pathao_area_id,
            'delivery_type' => 48,
            'item_type' => 2,
            'item_quantity' => max(1, $order->itemCount()),
            'item_weight' => 0.5,
            'amount_to_collect' => (int) round((float) $order->due),
            'special_instruction' => (string) $order->admin_note,
        ];
    }
}
