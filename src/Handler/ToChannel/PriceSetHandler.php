<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Exceptions\Api\WrongPayloadException;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\PriceSet;
use Exception;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\PriceSetTrait;
use Symfony\Component\HttpFoundation\Response;

class PriceSetHandler extends ApiBase
{
    use PriceSetTrait;

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
