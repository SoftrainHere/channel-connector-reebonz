<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Exceptions\Api\NotDistributedProductException;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
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
    public function created(InventorySet $inventorySet, string $mutationType = 'post'): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        try {
            if (empty($inventorySet->product->override->id_from_remote)) {
                throw new NotDistributedProductException();
            }

            $apiEndpoint = self::getFullChannelApiEndpoint(
                'put.stock_update',
                [ 'product_id' => $inventorySet->product->override->id_from_remote ]
            );
            $response = $this->buildCreatePayload($inventorySet)->requestMutation($apiEndpoint, $mutationType);

            if ($response['result'] != 'success') {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz inventory-update error', 'got wrong response from Reebonz'],
                    Response::HTTP_FORBIDDEN
                );
            }
        } catch (NotDistributedProductException $e) {
            app(SendExceptionToCentralLog::class)(
                [trans('errors.not_distributed_product', [
                    'product_id' => $inventorySet->product->id
                ])],
                Response::HTTP_FORBIDDEN,
            );
        } catch (Exception $e) {
            app(SendExceptionToCentralLog::class)(
                ['Reebonz inventory sync error', $e->getMessage()],
                $e->getCode()
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
        return $this->created($inventorySet, 'put');
    }
}
