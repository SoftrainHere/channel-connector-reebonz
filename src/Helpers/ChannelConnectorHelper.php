<?php declare(strict_types = 1);

namespace Mxncommerce\ChannelConnector\Helpers;

use Illuminate\Database\Eloquent\Model;

class ChannelConnectorHelper
{
    public function getProductUrl(Model $model): string|null
    {
        return '';
    }

    public function getOptionJson(string $option): string
    {
        return json_encode([
            'option1' => [
                'name' => 'no name',
                'value' => $option
            ]
        ]);
    }

    public function buildValidJson(string $invalidJson): string
    {
        $pattern = ['/"{/', '/}"/'];
        $repl = ['{', '}'];
        $validJson = preg_replace($pattern, $repl, $invalidJson);
        return stripslashes($validJson);
    }

    public function getChannelCourier(string $trackingCompany): array|null
    {
        $lists = config('channel_connector_for_remote.delivery_company_code');
        return collect($lists)->where('name', $trackingCompany)->first();
    }

    public function getChannelOrderStatus(string $fulfillmentStatus): string
    {
        return match ($fulfillmentStatus) {
            'SUCCESS' => '15',
            'CANCELLED', 'ERROR', 'FAILURE', 'UNKNOWN' => '99',
            default => '11'
        };
    }
}
