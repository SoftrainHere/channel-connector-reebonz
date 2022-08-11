<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\Variant;
use Exception;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\ProductTrait;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VariantHandler extends ApiBase
{
    use ProductTrait;
    use SetOverrideDataFromRemote;

    public function updated(Variant $variant): bool
    {
        $product = $variant->product;
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
