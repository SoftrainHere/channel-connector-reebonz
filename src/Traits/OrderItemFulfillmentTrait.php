<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Models\Features\OrderItemFulfillment;
use Mxncommerce\ChannelConnector\Helpers\ChannelConnectorHelper;

trait OrderItemFulfillmentTrait
{
    public function buildCreatePayload(OrderItemFulfillment $orderItemFulfillment): static
    {
        $this->payload['input']['list'] = [
            'orderno' => $orderItemFulfillment->channel_order_number,
            'orderidxnum' => $orderItemFulfillment->orderItem->c_item_id,
            'orderstatus' => app(ChannelConnectorHelper::class)
                ->getChannelOrderStatus($orderItemFulfillment->status),
            'qty' => $orderItemFulfillment->quantity,
            'dlvCode' => app(ChannelConnectorHelper::class)
                    ->getChannelCourier($orderItemFulfillment->tracking_company)['code'] ?? '',
            'dlvName' => $orderItemFulfillment->tracking_company,
            'expressno' => $orderItemFulfillment->tracking_number,
        ];

        $this->payload['input'] = ['list' => [ $this->payload['input']['list'] ]];
        return $this;
    }
}
