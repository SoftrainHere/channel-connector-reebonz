<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Exceptions\Api\ProductWithoutCategoryException;
use App\Exceptions\Api\ProductWithoutChannelBrandException;
use App\Exceptions\Api\SaveToCentralException;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\Brand;
use App\Models\Features\ConfigurationValue;
use App\Models\Features\Product;
use App\Models\ResyncWaitingProduct;
use App\Traits\WaitUntil;
use Exception;
use Illuminate\Support\Facades\Http;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Traits\ProductTrait;
use Mxncommerce\ChannelConnector\Traits\SetOverrideDataFromRemote;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderHandler extends ApiBase
{
    public function list(array $condition): array
    {
        $url = self::getFullChannelApiEndpoint('get.ordered_items');
        if (count($condition)) {
            $url .= '?' . http_build_query($condition, '', '&');
        }

        $response = Http::acceptJson()
            ->withToken(ConfigurationValue::getValue('channel_oauth_token'))
            ->get($url);

        if ($response->status() === Response::HTTP_UNAUTHORIZED) {
            if(self::renewChannelToken()) {
                $this->list($condition);
            } else {
                // todo throw
                return [];
            }
        }

        return $response->json();
    }
}
