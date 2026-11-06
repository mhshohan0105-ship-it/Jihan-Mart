<?php

namespace App\Services\Couriers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Pathao merchant API ("Aladdin", https://api-hermes.pathao.com).
 * Every parcel needs Pathao's own city/zone ids, so our areas (cities table) carry a mapping.
 */
class PathaoClient
{
    private const TOKEN_KEY = 'pathao_access_token';

    public function configured(): bool
    {
        return filled(config('couriers.pathao.client_id')) && filled(config('couriers.pathao.client_secret'))
            && filled(config('couriers.pathao.username')) && filled(config('couriers.pathao.password'));
    }

    /**
     * @return array{consignment_id:string, tracking_code:?string, status:?string}
     */
    public function createOrder(array $parcel): array
    {
        $res = $this->http()->post('aladdin/api/v1/orders', $parcel + ['store_id' => (int) config('couriers.pathao.store_id')])->json();
        if (empty($res['data']['consignment_id'])) {
            throw new RuntimeException($this->errorMessage($res));
        }

        return ['consignment_id' => (string) $res['data']['consignment_id'], 'tracking_code' => (string) $res['data']['consignment_id'], 'status' => $res['data']['order_status'] ?? null];
    }

    public function stores(): array
    {
        return $this->http()->get('aladdin/api/v1/stores')->json('data.data') ?? [];
    }

    public function cities(): array
    {
        return Cache::remember('pathao_cities', now()->addDay(), fn () => $this->http()->get('aladdin/api/v1/city-list')->json('data.data') ?? []);
    }

    public function zones(int $cityId): array
    {
        return Cache::remember("pathao_zones_{$cityId}", now()->addDay(), fn () => $this->http()->get("aladdin/api/v1/cities/{$cityId}/zone-list")->json('data.data') ?? []);
    }

    public function areas(int $zoneId): array
    {
        return Cache::remember("pathao_areas_{$zoneId}", now()->addDay(), fn () => $this->http()->get("aladdin/api/v1/zones/{$zoneId}/area-list")->json('data.data') ?? []);
    }

    /** Access token, cached until shortly before it expires. */
    public function token(): string
    {
        if ($token = Cache::get(self::TOKEN_KEY)) {
            return $token;
        }
        if (! $this->configured()) {
            throw new RuntimeException(__('Pathao API credentials are not set. Add them in Shipping → Courier API.'));
        }
        $res = Http::baseUrl(rtrim(config('couriers.pathao.base_url'), '/').'/')->acceptJson()->asJson()->timeout(30)
            ->post('aladdin/api/v1/issue-token', [
                'client_id' => config('couriers.pathao.client_id'),
                'client_secret' => config('couriers.pathao.client_secret'),
                'username' => config('couriers.pathao.username'),
                'password' => config('couriers.pathao.password'),
                'grant_type' => 'password',
            ])->json();
        if (empty($res['access_token'])) {
            throw new RuntimeException($this->errorMessage($res) ?: __('Pathao login failed. Check the credentials.'));
        }
        Cache::put(self::TOKEN_KEY, $res['access_token'], now()->addSeconds(max(60, (int) ($res['expires_in'] ?? 3600) - 300)));

        return $res['access_token'];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('couriers.pathao.base_url'), '/').'/')
            ->withToken($this->token())->acceptJson()->asJson()->timeout(30);
    }

    private function errorMessage(?array $res): string
    {
        if (! empty($res['errors'])) {
            return collect($res['errors'])->flatten()->implode(' ');
        }

        return (string) ($res['message'] ?? __('Pathao did not accept the request.'));
    }
}
