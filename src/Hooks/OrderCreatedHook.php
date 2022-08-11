<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Hooks;

use App\Models\Features\Order;
use Mxncommerce\ChannelConnector\Jobs\GetOrderFulfillment;

class OrderCreatedHook {

    /**
     * list up whatever you need to do something after order created in CC
     *
     * @param Order $orderSaved
     */
    public function __invoke(Order $orderSaved): void
    {
        GetOrderFulfillment::dispatch($orderSaved);
    }
}
