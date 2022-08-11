<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\InventorySet;
use Exception;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\InventorySetTrait;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InventorySetHandler extends ApiBase
{
    use InventorySetTrait;

    /**
     * @param InventorySet $inventorySet
     * @return bool
     */
    public function created(InventorySet $inventorySet): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);
        $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.inventory_set_update';

        try {
            $res = $this->buildCreatePayload($inventorySet)
                ->requestMutation(config('channel_connector_for_remote.api_stock_update'));

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

    /**
     * @param InventorySet $inventorySet
     * @return void
     * @throws Throwable
     */
    public function updated(InventorySet $inventorySet): bool
    {
        return $this->created($inventorySet);
    }
}
