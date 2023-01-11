<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Exceptions\Api\ProductWithoutCategoryException;
use App\Exceptions\Api\ProductWithoutChannelBrandException;
use Mxncommerce\ChannelConnector\Exceptions\Api\ProductWithoutImageException;
use App\Exceptions\Api\SaveToCentralException;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\InventorySet;
use App\Models\Features\Product;
use App\Models\Features\Variant;
use App\Models\Features\VendorBrand;
use App\Models\ResyncWaitingProduct;
use App\Traits\SupplyPriceSentHistoryTrait;
use App\Traits\WaitUntil;
use Exception;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\ProductHandlerTrait;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProductHandler extends ApiBase
{
    use ProductHandlerTrait;
    use SetOverrideDataFromRemote;
    use SupplyPriceSentHistoryTrait;
    use WaitUntil;

    protected int $sleepCount = 0;

    /**
     * @param Product $product
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function created(Product $product): bool
    {
        ChannelConnectorFacade::echoDev(__CLASS__ . '->' .  __FUNCTION__);

        $product->refresh();
        $product->load(['variants']);

        if (
            !($product->vendorBrand instanceof VendorBrand) ||
            !count($product->variants) ||
            !count($product->descriptionSets)
        ) {
            $error = 'Product(' . $product->id . ') has been ignored';
            app(SendExceptionToCentralLog::class)(
                [$error],
                Response::HTTP_FORBIDDEN
            );
            return false;
        }

        try {
            $apiEndpoint = self::getFullChannelApiEndpoint('post.products');
            $response = $this->buildCreatePayload($product)->requestMutation($apiEndpoint);

            if ($response['result'] != 'success') {
                $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.product_creation_error';
                $error = trans($error_namespace, [
                    'product_id' => $product->id,
                    'message' => $response['message']
                ]);

                app(SendExceptionToCentralLog::class)(
                    [$error],
                    Response::HTTP_FORBIDDEN
                );
                return false;
            }

            $payloadFromRemote = [
                'product' => [
                    'id' => $response['product_id']
                ]
            ];
            $this->setOverrideDataFromRemote($product, $payloadFromRemote);

            if (count($response['stocks'])) {
                foreach ($response['stocks'] as $stock) {
                    $variant = Variant::find($stock['item_no']);
                    if (!$variant instanceof Variant) {
                        continue;
                    }

                    if (!$variant->inventorySet instanceof InventorySet) {
                        continue;
                    }

                    $this->setOverrideDataFromRemote($variant, [
                        'variant' => [
                            'id' => $stock['id']
                        ]
                    ]);
                }
            }

            /*
             | ------------------------------------------------------------
             | Upsert last History of supplied price of product to channel
             | ------------------------------------------------------------
             */
            ChannelConnectorFacade::upsertSupplyPriceSentHistory($product);
            $msg = 'Product(' . $product->id . ') with '.count($response['stocks']).
                ' variants and '.count($this->payload['input']['detail_images']).
                ' media has been sent from '.count($product->variants).'/'.count($product->media).' cc variant/media';
            app(SendExceptionToCentralLog::class)(
                [$msg],
                Response::HTTP_CREATED
            );

        } catch (ProductWithoutCategoryException $e) {
            $resyncWaitingProduct = ResyncWaitingProduct::whereProductId($product->id)->first();
            if ($resyncWaitingProduct instanceof ResyncWaitingProduct) {
                return true;
            }

            $resyncWaitingProduct->product_id = $product->id;
            $resyncWaitingProduct->save();
        } catch (ProductWithoutChannelBrandException $e) {
            app(SendExceptionToCentralLog::class)(
                [trans('errors.product_without_channel_brand', [
                    'product_id' => $product->id
                ])],
                Response::HTTP_FORBIDDEN,
            );
        } catch (ProductWithoutImageException $e) {
            app(SendExceptionToCentralLog::class)(
                [trans('mxncommerce.channel-connector::channel_connector.errors.product_without_image', [
                    'product_id' => $product->id
                ])],
                Response::HTTP_FORBIDDEN,
            );
        } catch (Throwable $e) {

            $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.product_creation_error';
            $error = trans($error_namespace, [
                'product_id' => $product->id,
                'message' => $e->getMessage()
            ]);

            app(SendExceptionToCentralLog::class)(
                [$error],
                Response::HTTP_FORBIDDEN
            );
        }

        return true;
    }

    /**
     * @param Product $product
     * @return bool
     * @throws SaveToCentralException
     * @throws Throwable
     */
    public function updated(Product $product): bool
    {
        try {
            $product->refresh();

            if (!$product->override?->id_from_remote) {
                $this->created($product);
                return true;
            }
            $apiEndpoint = self::getFullChannelApiEndpoint(
                'put.products',
                [ 'product_id' => $product->override->id_from_remote ]
            );
            $response = $this->buildCreatePayload($product)->requestMutation($apiEndpoint, 'put');

            if ($response['result'] != 'success') {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz product-created error', 'got wrong response from reebonz'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $this->updateOverrideDataFromRemote($product, $response);

            /*
             | ------------------------------------------------------------
             | Upsert last History of supplied price of product to channel
             | ------------------------------------------------------------
             */
            ChannelConnectorFacade::upsertSupplyPriceSentHistory($product);
            $msg = 'Product(' . $product->id . ') with '.count($this->payload['input']['stocks']).
                ' variants and '.count($this->payload['input']['detail_images']).
                ' media has been updated from '.count($product->variants).'/'.count($product->media).'
                cc variant/media';
            app(SendExceptionToCentralLog::class)(
                [$msg],
                Response::HTTP_CREATED
            );

        } catch (Exception $e) {
            app(SendExceptionToCentralLog::class)(
                ['Reebonz product sync error', $e->getMessage()],
                $e->getCode()
            );
        }
        return true;
    }

    /**
     * @param Product $product
     * @return bool
     * @throws Throwable
     */
    public function disabled(Product $product): bool
    {
        try {
            $apiEndpoint = self::getFullChannelApiEndpoint(
                'put.products',
                [ 'product_id' => $product->override->id_from_remote ]
            );
            $response = $this->buildDisablePayload($product)->requestMutation($apiEndpoint, 'put');

            if ($response['result'] != 'success') {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz product-created error', 'got wrong response from reebonz'],
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
