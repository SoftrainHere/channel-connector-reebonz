<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\PriceSet;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\PriceSetHandlerTrait;

class PriceSetHandler extends ApiBase
{
    use PriceSetHandlerTrait;

    /**
     * @param PriceSet $priceSet
     * @return bool
     */
    public function created(PriceSet $priceSet): bool
    {
        return true;
    }

    public function updated(PriceSet $priceSet): bool
    {
        return $this->created($priceSet);
    }
}
