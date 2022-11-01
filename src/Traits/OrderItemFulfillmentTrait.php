<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Models\Features\OrderItemFulfillment;
use Mxncommerce\ChannelConnector\Helpers\ChannelConnectorHelper;

trait OrderItemFulfillmentTrait
{
    public function buildCreatePayload(OrderItemFulfillment $orderItemFulfillment): static
    {
        $logistic = app(ChannelConnectorHelper::class)
            ->getChannelLogistics($orderItemFulfillment->tracking_company);
        $this->payload['input'] = [
            'ordered_item_id' => $orderItemFulfillment->orderItem->c_item_id,
            'delivery_method_id' => $logistic ? $logistic['code'] : '',
            'tracking_code' => $orderItemFulfillment->tracking_number,
        ];

        return $this;
    }
}
