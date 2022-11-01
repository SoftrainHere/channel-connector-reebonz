<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\Variant;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Throwable;

class VariantHandler extends ApiBase
{
    /**
     * @throws \App\Exceptions\Api\SaveToCentralException
     * @throws Throwable
     */
    public function updated(Variant $variant): bool
    {
        return app(ProductHandler::class)->updated($variant->product);
    }
}
