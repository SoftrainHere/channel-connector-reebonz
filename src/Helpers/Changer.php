<?php declare(strict_types = 1);

namespace Mxncommerce\ChannelConnector\Helpers;

class Changer
{
    public static function ChangedStatus(string $status): string
    {
        return match ($status) {
            'oz' => 'DRAFT',
            'lb' => 'ARCHIVED',
            default => 'ACTIVE'
        };
    }
}
