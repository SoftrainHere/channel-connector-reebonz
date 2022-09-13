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
        return collect($lists)->where('name', $trackingCompany)->first();
    }
}
