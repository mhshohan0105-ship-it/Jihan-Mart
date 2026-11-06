<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Date range from a preset (today, last_7, this_month...) or a custom from/to, in the app timezone. */
class DateRange
{
    public const PRESETS = [
        'today' => 'Today', 'yesterday' => 'Yesterday', 'last_7' => 'Last 7 Days', 'this_month' => 'This Month',
        'last_month' => 'Last Month', 'all_time' => 'All Time', 'custom' => 'Custom Range',
    ];

    public function __construct(public ?Carbon $from, public ?Carbon $to, public string $preset) {}

    public static function fromRequest(Request $request, string $default = 'this_month'): self
    {
        $preset = $request->input('range', $default);
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $preset = 'custom';
        }
        $now = now();

        return match ($preset) {
            'today' => new self($now->copy()->startOfDay(), $now->copy()->endOfDay(), $preset),
            'yesterday' => new self($now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), $preset),
            'last_7' => new self($now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), $preset),
            'last_month' => new self($now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth(), $preset),
            'all_time' => new self(null, null, $preset),
            'custom' => new self(
                $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null,
                $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : null,
                'custom',
            ),
            default => new self($now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'this_month'),
        };
    }

    /** Limit a query's `created_at` (or another column) to this range. */
    public function apply($query, string $column = 'created_at')
    {
        return $query->when($this->from, fn ($q) => $q->where($column, '>=', $this->from))
            ->when($this->to, fn ($q) => $q->where($column, '<=', $this->to));
    }

    public function label(): string
    {
        if (! $this->from && ! $this->to) {
            return __('All Time');
        }

        return trim(($this->from ? format_date($this->from, false) : '…').' → '.($this->to ? format_date($this->to, false) : '…'));
    }
}
