<?php

namespace App\Services\Couriers;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Customer trust score: Steadfast delivery history (cached 24h per phone) + our own order history.
 * The list page only reads the cache and store history; couriers are called on "Refresh Live".
 */
class FraudCheckService
{
    public function __construct(private SteadfastClient $steadfast) {}

    public function check(string $phone, bool $live = false): array
    {
        $cached = DB::table('fraud_check_cache')->where('phone', $phone)->first();
        $couriers = $cached ? json_decode($cached->data, true) : null;
        // live = "Refresh Live" in the modal, or the first look at a phone we never checked
        if ($live) {
            $couriers = $this->fetchCouriers($phone);
            DB::table('fraud_check_cache')->updateOrInsert(['phone' => $phone], ['data' => json_encode($couriers), 'checked_at' => now()]);
            $cached = (object) ['checked_at' => now()];
        }

        $store = $this->storeHistory($phone);

        return [
            'phone' => $phone,
            'steadfast' => $couriers['steadfast'] ?? null,
            'store' => $store,
            'overall' => $this->overall($couriers['steadfast'] ?? null, $store),
            'checked_at' => $cached?->checked_at ? format_date($cached->checked_at) : null,
        ];
    }

    /** Badges for many phones at once (cache + store history only, no courier calls). */
    public function batch(array $phones): array
    {
        $out = [];
        foreach (array_unique(array_filter($phones)) as $phone) {
            $out[$phone] = $this->check($phone, false)['overall'];
        }

        return $out;
    }

    private function fetchCouriers(string $phone): array
    {
        $data = [];
        if ($this->steadfast->configured()) {
            try {
                $data['steadfast'] = $this->steadfast->fraudScore($phone);
            } catch (Throwable $e) {
                $data['steadfast'] = ['error' => $e->getMessage()];
            }
        }

        return $data;
    }

    private function storeHistory(string $phone): array
    {
        $rows = Order::query()->where('phone', $phone)->selectRaw('delivery_status, count(*) as c')->groupBy('delivery_status')->pluck('c', 'delivery_status');
        $delivered = (int) ($rows['delivered'] ?? 0);
        $failed = (int) ($rows['returned'] ?? 0) + (int) ($rows['cancelled'] ?? 0);

        return ['total' => (int) $rows->sum(), 'delivered' => $delivered, 'cancelled' => $failed, 'by_status' => $rows];
    }

    /** Combine courier + store history into one risk level. */
    private function overall(?array $steadfast, array $store): array
    {
        $total = $store['delivered'] + $store['cancelled'];
        $delivered = $store['delivered'];
        if ($steadfast && empty($steadfast['error'])) {
            $total += $steadfast['delivered'] + $steadfast['cancelled'];
            $delivered += $steadfast['delivered'];
        }
        if ($total === 0) {
            return ['level' => 'new', 'rate' => null, 'total' => 0, 'label' => __('New customer')];
        }
        $rate = round($delivered / $total * 100);

        return match (true) {
            $rate >= 80 => ['level' => 'safe', 'rate' => $rate, 'total' => $total, 'label' => __('Reliable Customer')],
            $rate >= 50 => ['level' => 'medium', 'rate' => $rate, 'total' => $total, 'label' => __('Medium risk')],
            default => ['level' => 'risky', 'rate' => $rate, 'total' => $total, 'label' => __('Risky customer')],
        };
    }
}
