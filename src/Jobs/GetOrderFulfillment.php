<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Models\Features\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * We don't know the order fulfillment information
 * at the time the order is created from channel.
 * So to get that information, we need extra api request
 */
class GetOrderFulfillment implements ShouldQueue
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
    public bool $failOnTimeout = true;
    public int $maxExceptions = 3;
    public int $timeout = 60;
    public int $tries = 3;
    public int $backoff = 10;

    private Order $order;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->onQueue(config('queue.connections.database.queue_to_remote'));
        $this->order = $order;
    }

    public function handle(): void
    {
        // do something here after order created from  reebonz
    }
}
