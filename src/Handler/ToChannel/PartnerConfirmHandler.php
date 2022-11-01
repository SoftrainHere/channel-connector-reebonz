<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\Order;
use App\Traits\WaitUntil;
use Exception;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;
use Symfony\Component\HttpFoundation\Response;

class PartnerConfirmHandler extends ApiBase
{
    use SetOverrideDataFromRemote;
    use WaitUntil;

    public function created(Order $order): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        try {
            $apiEndpoint = self::getFullChannelApiEndpoint(
                'post.partner_confirm_complete',
                [ 'ordered_item_id' => $order->orderItems[0]->c_item_id ]
            );
            $response = $this->requestMutation($apiEndpoint);

            if ($response['result'] != 'success') {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz partner-confirm error', 'got wrong response from reebonz'],
                    Response::HTTP_FORBIDDEN
                );
            }

        } catch (Exception $e) {
            app(SendExceptionToCentralLog::class)(
                ['Reebonz product sync error', $e->getMessage()],
                $e->getCode()
            );
        }

        return true;
    }
}
