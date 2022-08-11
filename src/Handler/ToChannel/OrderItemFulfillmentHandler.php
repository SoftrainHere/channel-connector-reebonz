<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Enums\OrderItemFulfillmentStatusType;
use App\Exceptions\Api\SaveToCentralException;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\OrderItemFulfillment;

use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\OrderItemFulfillmentTrait;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;

class OrderItemFulfillmentHandler extends ApiBase
{
    use OrderItemFulfillmentTrait;
    use SetOverrideDataFromRemote;

    /**
     * @param OrderItemFulfillment $orderItemFulfillment
     * @return bool
     * @throws SaveToCentralException
     */
    public function created(OrderItemFulfillment $orderItemFulfillment): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        if (
            $orderItemFulfillment->status === OrderItemFulfillmentStatusType::Success->value &&
            $orderItemFulfillment->tracking_company &&
            $orderItemFulfillment->tracking_number
        ) {
            $this->buildCreatePayload($orderItemFulfillment)
                ->requestMutation(config('channel_connector_for_remote.api_order_status'));
        }

        return true;
    }

    /**
     * @param OrderItemFulfillment $orderItemFulfillment
     * @return bool
     * @throws SaveToCentralException
     */
    public function updated(OrderItemFulfillment $orderItemFulfillment): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        if (
            $orderItemFulfillment->status === OrderItemFulfillmentStatusType::Success->value &&
            $orderItemFulfillment->tracking_company &&
            $orderItemFulfillment->tracking_number
        ) {
            $this->buildCreatePayload($orderItemFulfillment)
                ->requestMutation(config('channel_connector_for_remote.api_order_status'));
        }

        return true;
    }

    /**
     * @param OrderItemFulfillment $orderItemFulfillment
     * @return bool
     */
    public function cancelled(OrderItemFulfillment $orderItemFulfillment): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);
        if (
            $orderItemFulfillment->status === OrderItemFulfillmentStatusType::Cancelled->value &&
            $orderItemFulfillment->tracking_company &&
            $orderItemFulfillment->tracking_number
        ) {
//            $this->buildCreatePayload($orderItemFulfillment)
//                ->requestMutation(config('channel_connector_for_remote.api_order_status'));
        }
        return true;
    }
}
