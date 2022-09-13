<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\Product;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\ProductTrait;
use Throwable;

class DescriptionSetHandler extends ApiBase
{
    use ProductTrait;

    /**
     * @param Product $product
     * @return bool
     * @throws Throwable
     * @throws \App\Exceptions\Api\SaveToCentralException
     */
    public function updated(Product $product): bool
    {
        return app(ProductHandler::class)->updated($product);
    }
}
