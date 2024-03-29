<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Enums\VariantSalesStatusType;
use App\Enums\VariantStatusType;
use App\Exceptions\Api\NotDistributedProductException;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\InventorySet;
use Exception;
use Mxncommerce\ChannelConnector\Exceptions\Api\VariantNotActiveException;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\InventorySetHandlerTrait;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InventorySetHandler extends ApiBase
{
    use InventorySetHandlerTrait;

    /**
     * @param InventorySet $inventorySet
     * @param string $mutationType
     * @return bool
     */
    public function created(InventorySet $inventorySet, string $mutationType = 'put'): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        try {
            $inventorySet->refresh();

            if (!$inventorySet->product->media()->available()->count()) {
                return false;
            }

            if (empty($inventorySet->product->override->id_from_remote)) {
                throw new NotDistributedProductException();
            }

            if (!($inventorySet->variant->status===VariantStatusType::Active->value&&
                $inventorySet->variant->sales_status===VariantSalesStatusType::Enabled->value)) {
                throw new VariantNotActiveException();
            }
            $variantOverride = json_decode($inventorySet->variant->override->fields_overrided ?? '{}');
            if (count((array)$variantOverride)>0) {
                $statusVariant = $variantOverride?->status ?? null;
                $salesStatusVariant = $variantOverride?->sales_status ?? null;

                if (($statusVariant && $statusVariant !== VariantStatusType::Active->value) ||
                    ($salesStatusVariant && $salesStatusVariant !== VariantSalesStatusType::Enabled->value)) {
                    throw new VariantNotActiveException();
                }
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
        } catch (VariantNotActiveException $e) {
            app(SendExceptionToCentralLog::class)(
                [trans('mxncommerce.channel-connector::channel_connector.errors.variant_not_active', [
                    'variant_id' => $inventorySet->variant->id
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


    public function updated(InventorySet $inventorySet): bool
    {
        if (!$inventorySet->product->media()->available()->count()) {
            app(SendExceptionToCentralLog::class)(
                [trans('errors.not_distributed_product', [
                    'product_id' => $inventorySet->product->id,
                    'method' => 'InventorySetHandler->' . __FUNCTION__
                ])],
                Response::HTTP_FORBIDDEN,
            );
            return false;
        }
        return $this->created($inventorySet, 'put');
    }
}
