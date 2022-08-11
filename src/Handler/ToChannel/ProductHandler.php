<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Exceptions\Api\ProductWithoutCategoryException;
use App\Exceptions\Api\SaveToCentralException;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\Brand;
use App\Models\Features\Product;
use App\Models\ResyncWaitingProduct;
use App\Traits\WaitUntil;
use Exception;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\ProductTrait;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProductHandler extends ApiBase
{
    use ProductTrait;
    use SetOverrideDataFromRemote;
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

        $this->waitUntil('product creating...');

        if (
            !($product->brand instanceof Brand) ||
            !count($product->variants) ||
            !count($product->descriptionSets)
        ) {
            $this->waitUntil(Product::REL_BRAND, $this->sleepCount);
            return false;
        }

        try {
            $res = $this->buildCreatePayload($product)
                ->requestMutation(config('channel_connector_for_remote.api_create_product'));

            $response = json_decode($res->getData());
            if ($response->result != '01') {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz product-created error', 'got wrong response from reebonz'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $payloadFromRemote = [
                'product' => [
                    'id' => $response->product_id,
                    'supply_currency_code' => $this->payload['input']['currency_unit'],
                    'final_supply_price' => $product->representative_supply_price
                ]
            ];

            $this->setOverrideDataFromRemote($product, $payloadFromRemote);
        } catch (ProductWithoutCategoryException $e) {
            $resyncWaitingProduct = new ResyncWaitingProduct();
            $resyncWaitingProduct->product_id = $product->id;
            $resyncWaitingProduct->save();
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
     * @throws SaveToCentralException
     * @throws Throwable
     */
    public function updated(Product $product): bool
    {
        $res = $this->buildCreatePayload($product)
            ->requestMutation(config('channel_connector_for_remote.api_create_product'));
        try {
            $response = json_decode($res->getData());
            if ($response->result != '01') {
                app(SendExceptionToCentralLog::class)(
                    ['Reebonz product-updated error', 'got wrong response from reebonz'],
                    Response::HTTP_FORBIDDEN
                );
            }
        } catch (Exception $exception) {
            app(SendExceptionToCentralLog::class)(
                ['Reebonz product sync error', $exception->getMessage()],
                $exception->getCode()
            );
        }
        return true;
    }
}
