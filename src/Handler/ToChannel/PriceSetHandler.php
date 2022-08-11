<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Exceptions\Api\WrongPayloadException;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\PriceSet;
use Exception;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\PriceSetTrait;
use Symfony\Component\HttpFoundation\Response;

class PriceSetHandler extends ApiBase
{
    use PriceSetTrait;

    /**
     * @param PriceSet $priceSet
     * @return bool
     */
    public function created(PriceSet $priceSet): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);
        $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.price_set_update';

        try {
            $res = $this->buildCreatePayload($priceSet)
                ->requestMutation(config('channel_connector_for_remote.api_price_update'));

            $response = json_decode($res->getData());

            if (empty($response->list)) {
                ChannelConnectorFacade::moveExceptionToCentral(
                    [trans($error_namespace, [
                        'vendor_id' => $this->payload['input']['vendor_id'],
                        'product_id' => $this->payload['input']['prodinc'],
                    ])],
                    Response::HTTP_BAD_REQUEST,
                );
                return false;
            }

            foreach ($response->list as $list) {
                if ($list->result != '01') {
                    ChannelConnectorFacade::moveExceptionToCentral(
                        [trans($error_namespace, [
                            'vendor_id' => $this->payload['input']['vendor_id'],
                            'product_id' => $this->payload['input']['prodinc'],
                        ])],
                        Response::HTTP_BAD_REQUEST,
                    );
                }
            }
        } catch (WrongPayloadException $exception) {
            ChannelConnectorFacade::moveExceptionToCentral(
                [$exception->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (Exception $exception) {
            ChannelConnectorFacade::moveExceptionToCentral(
                [trans($error_namespace, [
                    'vendor_id' => $this->payload['input']['vendor_id'],
                    'product_id' => $this->payload['input']['prodinc'],
                ])],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return true;
    }

    public function updated(PriceSet $priceSet): bool
    {
        return $this->created($priceSet);
    }
}
