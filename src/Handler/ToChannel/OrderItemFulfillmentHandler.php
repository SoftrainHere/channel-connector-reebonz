<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Enums\OrderItemFulfillmentStatusType;
use App\Exceptions\Api\SaveToCentralException;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\OrderItemFulfillment;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\OrderItemFulfillmentTrait;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;
use Symfony\Component\HttpFoundation\Response;
use Exception;

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
            try {
                $apiEndpoint = self::getFullChannelApiEndpoint('post.deliveries');
                $response = $this->buildCreatePayload($orderItemFulfillment)->requestMutation($apiEndpoint);

                if ($response['result'] != 'success') {
                    app(SendExceptionToCentralLog::class)(
                        ['Reebonz product-created error', 'got wrong response from reebonz'],
                        Response::HTTP_FORBIDDEN
                    );
                }

                $this->setOverrideDataFromRemote($orderItemFulfillment, [ 'result' => 'success']);
            } catch (Exception $e) {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz product sync error', $e->getMessage()],
                    $e->getCode()
                );
            }
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
            // possible?
        }
        return true;
    }
}
