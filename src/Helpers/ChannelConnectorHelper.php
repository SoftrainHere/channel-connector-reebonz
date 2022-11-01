<?php declare(strict_types = 1);

namespace Mxncommerce\ChannelConnector\Helpers;

class ChannelConnectorHelper
{
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

    public function getChannelLogistics(string $trackingCompany): array|null
    {
        $lists = config('channel_connector_for_remote.logistics');
        return collect($lists)->first(function ($value, $key) use ($trackingCompany) {
            return strtolower($value['name']) == strtolower($trackingCompany);
        });
    }

    public function getChannelOrderStatus(string $orderStatus): array|null
    {
        $lists = config('channel_connector_for_remote.order_status');
        return collect($lists)->first(function ($value) use ($orderStatus) {
            return strtolower($value['name']) == strtolower($orderStatus);
        });
    }

    public function getChannelDeliveryStatus(string $deliveryStatus): array|null
    {
        $lists = config('channel_connector_for_remote.delivery_status');
        return collect($lists)->first(function ($value) use ($deliveryStatus) {
            return strtolower($value['name']) == strtolower($deliveryStatus);
        });
    }
}
