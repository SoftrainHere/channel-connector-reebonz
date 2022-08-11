<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\Mapper;

use App\Enums\OrderItemCancellationRequesterType;
use App\Enums\OrderItemCancellationStatusType;
use App\Models\Features\OrderItem;
use App\Models\Features\OrderItemCancellation;
use JetBrains\PhpStorm\ArrayShape;

class OrderItemCancellationMapper
{
    #[ArrayShape([
        OrderItemCancellation::STATUS => "string",
        OrderItemCancellation::REQUESTER_TYPE => "mixed|string",
        OrderItemCancellation::ORDER_ID => "int",
        OrderItemCancellation::ORDER_ITEM_ID => "int",
        OrderItemCancellation::VENDOR_ID => "int",
        OrderItemCancellation::PRODUCT_ID => "int",
        OrderItemCancellation::VARIANT_ID => "int",
        OrderItemCancellation::QTY => "mixed"
    ])]
    public function getModelPayload(array $payload, OrderItem $orderItem): array
    {
        return [
            OrderItemCancellation::STATUS => OrderItemCancellationStatusType::Pending->value,
            OrderItemCancellation::REQUESTER_TYPE =>
                $payload['requester_type'] ?? OrderItemCancellationRequesterType::Ca->value,
            OrderItemCancellation::ORDER_ID => $orderItem->order_id,
            OrderItemCancellation::ORDER_ITEM_ID => $orderItem->id,
            OrderItemCancellation::VENDOR_ID => $orderItem->vendor_id,
            OrderItemCancellation::PRODUCT_ID => $orderItem->product_id,
            OrderItemCancellation::VARIANT_ID => $orderItem->variant_id,
            OrderItemCancellation::QTY => $payload['quantity'],
        ];
    }
}
