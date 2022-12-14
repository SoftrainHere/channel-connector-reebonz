<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Models\Features\Product;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mxncommerce\ChannelConnector\Handler\ToChannel\ProductHandler;

/*
|--------------------------------------------------------
| Class for order-item-cancellation from left-side of NMO
|--------------------------------------------------------
*/
class ProductDisabled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
    |--------------------------------------
    | Job configurations
    |--------------------------------------
    |
    | https://laravel.com/docs/9.x/queues
    |
    */
    public int $maxExceptions = 3;
    public int $timeout = 60;
    public int $tries = 3;
    public array|int $backoff = [300, 600];

    private ?Product $product;

    public function __construct(Product $product)
    {
        $this->onQueue(config('queue.connections.database.queue_to_remote'));
        $this->product = $product;
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        app(ProductHandler::class)->disabled($this->product);
    }
}
