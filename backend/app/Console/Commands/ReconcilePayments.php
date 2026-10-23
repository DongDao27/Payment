<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\ReconciliationService;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--limit=20}';

    protected $description = 'Query unresolved payments outside database locks with bounded work';

    public function handle(ReconciliationService $reconciliation): int
    {
        $deadline = microtime(true) + 45;
        $orders = Order::whereIn('payment_status', ['pending', 'reconciliation_required'])->whereHas('attempts', fn ($q) => $q->whereIn('status', ['created', 'pending', 'unknown'])->where(fn ($q) => $q->whereNull('query_requested_at')->orWhere('query_requested_at', '<=', now()->subMinutes(5))))
            ->orderBy(PaymentAttempt::selectRaw('MIN(query_requested_at)')->whereColumn('order_id', 'orders.id')->whereIn('status', ['created', 'pending', 'unknown']))
            ->orderBy('id')->limit(max(1, min(100, (int) $this->option('limit'))))->get();
        $count = 0;
        foreach ($orders as $order) {
            if (microtime(true) >= $deadline) {
                break;
            }
            $reconciliation->refresh($order);
            $count++;
        }
        $this->info('Queried '.$count.' orders. Uncertain cases remain open.');

        return self::SUCCESS;
    }
}
