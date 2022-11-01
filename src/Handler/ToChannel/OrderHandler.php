<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\ConfigurationValue;
use Illuminate\Support\Facades\Http;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Symfony\Component\HttpFoundation\Response;

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
                sleep(2);
                return $this->list($condition);
            } else {
                // todo throw
                return [];
            }
        } else {
            return $response->json();
        }
    }
}
