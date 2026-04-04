<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductStockLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateProductStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?Product $product;

    /**
     * Create a new job instance.
     */
    public function __construct(?Product $product = null)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Gọi handle job product stock rồi");
            if ($this->product) {
                $log = ProductStockLog::snapshot($this->product);
                Log::info("StockLog: [{$this->product->name}] có thể bán {$log->max_quantity} sp lúc {$log->logged_at}");
            } else {
                Product::with('recipeDetails.ingredient')->each(function (Product $product) {
                    ProductStockLog::snapshot($product);
                });
                Log::info('StockLog: Đã snapshot toàn bộ sản phẩm');
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi tính toán StockLog: ' . $e->getMessage());
            throw $e;
        }
    }
}
