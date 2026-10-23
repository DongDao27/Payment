<?php

namespace App\Services;

use App\Http\Resources\OrderResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;

class DashboardService
{
    public function summary(): array
    {
        $today = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay();
        $from = $today->subDays(6);
        $until = $today->addDay();
        // Vietnam is UTC+07:00 year-round; avoid depending on MySQL timezone tables.
        $daily = Order::where('created_at', '>=', $from->utc())
            ->where('created_at', '<', $until->utc())
            ->selectRaw('DATE(DATE_ADD(created_at, INTERVAL 7 HOUR)) AS day, COUNT(*) AS total')
            ->groupBy('day')->pluck('total', 'day');

        return [
            'timezone' => 'Asia/Ho_Chi_Minh',
            'counts' => [
                'active_products' => Product::where('is_active', true)->whereHas('category', fn ($q) => $q->where('is_active', true))->count(),
                'active_categories' => Category::where('is_active', true)->count(),
                'orders_today' => (int) ($daily[$today->toDateString()] ?? 0),
                'awaiting_processing' => Order::where('lifecycle', 'open')->where('fulfillment_status', 'unprocessed')->count(),
            ],
            'daily_orders' => collect(range(0, 6))->map(fn ($offset) => [
                'date' => $from->addDays($offset)->toDateString(),
                'count' => (int) ($daily[$from->addDays($offset)->toDateString()] ?? 0),
            ])->all(),
            'recent_orders' => OrderResource::collection(Order::with('items')->orderByDesc('created_at')->orderByDesc('id')->limit(5)->get()),
        ];
    }
}
