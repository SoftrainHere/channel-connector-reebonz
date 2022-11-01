<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Models\Features\OrderItemCancellation;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mxncommerce\ChannelConnector\Handler\ToChannel\OrderItemCancellationHandler;

/*
|--------------------------------------------------------
| Class for order-item-cancellation from left-side of NMO
|--------------------------------------------------------
*/
class OrderItemCancellationCreated implements ShouldQueue
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

    private ?OrderItemCancellation $orderItemCancellation;

    /**
     * Create a new event instance.
     *
     * @param OrderItemCancellation $orderItemCancellation
     */
    public function __construct(OrderItemCancellation $orderItemCancellation)
    {
        $this->onQueue(config('queue.connections.database.queue_to_remote'));
        $this->orderItemCancellation = $orderItemCancellation;
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        /*
         * This hook is a point right after order-fulfillment create from cc
        */
        app(OrderItemCancellationHandler::class)->created($this->orderItemCancellation);
    }
}
