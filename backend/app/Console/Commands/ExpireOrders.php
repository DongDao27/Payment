<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ReconciliationService;
use Illuminate\Console\Command;

class ExpireOrders extends Command
{
    protected $signature = 'orders:expire {--limit=100}';

    protected $description = 'Expire due unpaid orders; preserve stock for uncertain payment';

    public function handle(ReconciliationService $reconciliation): int
    {
        $deadline = microtime(true) + 45;
        $count = 0;
        $orders = Order::where('lifecycle', 'open')->whereIn('payment_status', ['unpaid', 'pending'])->where('expires_at', '<=', now()->format('Y-m-d H:i:s.u'))->orderBy('id')->limit(max(1, min(500, (int) $this->option('limit'))))->get();
        foreach ($orders as $order) {
            if (microtime(true) >= $deadline) {
                break;
            }
            $reconciliation->close($order);
            $count++;
        }
        $this->info('Reviewed '.$count.' due orders. Unknown payments retain reservations.');

        return self::SUCCESS;
    }
}
