<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Enums\OrderItemFulfillmentStatusChangedType;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\OrderItemFulfillment;
use App\Models\Override;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mxncommerce\ChannelConnector\Handler\ToChannel\OrderItemFulfillmentHandler;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/*
|---------------------------------------------------------------------
| Class for channel's fulfillment
|---------------------------------------------------------------------
|
| You have to implement this class
| to do something after fulfillment updated from connector
| like real fulfillment fulfilled in channel
|
*/
class OrderItemFulfillmentUpdated implements ShouldQueue
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
    public array|int $backoff = [60, 120];

    private ?OrderItemFulfillment $orderItemFulfillment;
    private string|null $fulfillmentStatus;

     /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(OrderItemFulfillment $orderItemFulfillment, ?string $fulfillmentStatus = null)
    {
        $this->onQueue(config('queue.connections.database.queue_to_remote'));
        $this->fulfillmentStatus = $fulfillmentStatus;
        $this->orderItemFulfillment = $orderItemFulfillment;
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(): void
    {
        $override = $this->orderItemFulfillment->order->override;
        if (!$override instanceof Override) {
            ChannelConnectorFacade::moveExceptionToCentral(
                [trans('errors.ffmt_not_connected', [
                    'order' => $this->orderItemFulfillment->order->id
                ])],
                Response::HTTP_NOT_FOUND,
            );
        }
        if ($this->fulfillmentStatus === OrderItemFulfillmentStatusChangedType::Cancelled->value) {
            app(OrderItemFulfillmentHandler::class)->cancelled($this->orderItemFulfillment);
        } else if ($this->fulfillmentStatus === OrderItemFulfillmentStatusChangedType::Success->value) {
            app(OrderItemFulfillmentHandler::class)->updated($this->orderItemFulfillment);
        } else if ($this->fulfillmentStatus === OrderItemFulfillmentStatusChangedType::ReSuccess->value) {
            // shouldn't be allowed in business...
            ChannelConnectorFacade::moveExceptionToCentral(
                [trans('errors.cancelled_ffmt_retried', [
                    'order_item_id' => $this->orderItemFulfillment->orderItem->id
                ])],
                Response::HTTP_NOT_FOUND,
            );
        }
    }
}
