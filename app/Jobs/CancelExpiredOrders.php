<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CancelExpiredOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $order_id)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::find($this->order_id);

        Log::info("Đơn hàng " . $order->order_number . " đã hết hạn");

        if ($order && $order->status == "pending"){
            $order->update(['status' => 'cancelled']);
        }
    }
}
