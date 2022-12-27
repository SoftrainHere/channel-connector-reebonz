<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\DescriptionSet;
use App\Models\Features\Product;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Throwable;

class DescriptionSetHandler extends ApiBase
{
    /**
     * @param Product $product
     * @return bool
     * @throws Throwable
     * @throws \App\Exceptions\Api\SaveToCentralException
     */
    public function created(DescriptionSet $descriptionSet): bool
    {
        if($descriptionSet->product->descriptionSets->count() > 1) {
            return app(ProductHandler::class)->updated($descriptionSet->product);
        }
        return true;
    }

    /**
     * @param Product $product
     * @return bool
     * @throws Throwable
     * @throws \App\Exceptions\Api\SaveToCentralException
     */
    public function updated(DescriptionSet $descriptionSet): bool
    {
        return app(ProductHandler::class)->updated($descriptionSet->product);
    }
}
