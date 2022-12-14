<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Enums\OrderItemCancellationStatusType;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\OrderItemCancellation;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class OrderItemCancellationHandler extends ApiBase
{
    use SetOverrideDataFromRemote;

    /**
     * @param OrderItemCancellation $orderItemCancellation
     * @return bool
     */
    public function created(OrderItemCancellation $orderItemCancellation): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        if ($orderItemCancellation->status === OrderItemCancellationStatusType::Approved->value) {
            try {
                if (empty($orderItemCancellation->orderItem->c_item_id)) {
                    return false;
                }
                $apiEndpoint = self::getFullChannelApiEndpoint(
                    'post.request_cancel',
                    ['ordered_item_id' => $orderItemCancellation->orderItem->c_item_id]
                );
                $response = $this->requestMutation($apiEndpoint);

                if ($response['result'] != 'success') {
                    app(SendExceptionToCentralLog::class)(
                        ['Got order-cancellation-created error from Reebonz', 'Got wrong response from reebonz'],
                        Response::HTTP_FORBIDDEN
                    );
                }

                $this->setOverrideDataFromRemote($orderItemCancellation, ['result' => 'success']);
            } catch (Exception $e) {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz order-cancellation-created sync error', $e->getMessage()],
                    $e->getCode()
                );
            }
        }

        return true;
    }

    /**
     * @param int $channelOrderItemId
     * @return bool
     */
    public function cancelBeforeSync(int $channelOrderItemId): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        try {
            $apiEndpoint = self::getFullChannelApiEndpoint(
                'post.request_cancel',
                ['ordered_item_id' => $channelOrderItemId]
            );
            $this->requestMutation($apiEndpoint);

        } catch (Exception $e) {
            app(SendExceptionToCentralLog::class)(
                ['Reebonz order-cancellation-created sync error', $e->getMessage()],
                $e->getCode()
            );
        }

        return true;
    }
}
