<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MotorOrders;
use Illuminate\Console\Command;

/**
 * Asks insurers about orders still waiting on payment or policy issue. Covers customers who
 * pay but never come back to our return URL (closed tab, other device, no return URL from DIN).
 */
class PollPendingPolicies extends Command
{
    protected $signature = 'orders:poll-policies {--hours=48 : Only orders updated within this many hours}';

    protected $description = 'Check payment / policy status with the insurer for open motor orders.';

    public function handle(MotorOrders $orders): int
    {
        $open = Order::whereIn('state', MotorOrders::OPEN_STATES)
            ->where('updated_at', '>=', now()->subHours((int) $this->option('hours')))
            ->orderBy('id')
            ->get();

        $changed = 0;
        foreach ($open as $order) {
            $before = $order->state;
            $after = $orders->refresh($order)->state;
            if ($after !== $before) {
                $changed++;
                $this->line("{$order->reference}: {$before} -> {$after}");
            }
        }
        $this->info("Checked {$open->count()} open orders, {$changed} changed.");

        return self::SUCCESS;
    }
}
