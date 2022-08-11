<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Models\Features\OrderItemFulfillment;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mxncommerce\ChannelConnector\Handler\ToChannel\OrderItemFulfillmentHandler;

/*
|---------------------------------------------------------------------
| Class for channel's fulfillment
|---------------------------------------------------------------------
|
| You have to implement this class
| to do something after fulfillment created from connector
| like real fulfillment fulfilled in channel
|
*/
class OrderItemFulfillmentCreated implements ShouldQueue
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

    private ?OrderItemFulfillment $orderItemFulfillment;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(OrderItemFulfillment $orderItemFulfillment)
    {
        $this->onQueue(config('queue.connections.database.queue_to_remote'));
        $this->orderItemFulfillment = $orderItemFulfillment;
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        /**
         * This hook is a point right after order-fulfillment create from cc
         */
        app(OrderItemFulfillmentHandler::class)->created($this->orderItemFulfillment);
    }
}
