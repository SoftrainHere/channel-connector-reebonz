<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Models\Features\Variant;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\Variable;

trait ShopifyGraphiqlCommonTrait
{
    public function buildCancelFulfillmentCreate(string $fulfillmentId): static
    {
        $this->payload = [
            'id' => $fulfillmentId
        ];
        return $this;
    }

    public function buildInventoryAdjustQuantity(string $inventoryLevelId, int $availableDelta): static
    {
        $this->payload = ['input' => [
            'inventoryLevelId' => $inventoryLevelId,
            'availableDelta' => $availableDelta
        ]];
        return $this;
    }

    public function buildProductAppendImages(Variant $variant): static
    {
        $this->payload = [
            'input' => [
                'id' => $variant->product->override->id_from_remote,
                'images' => [
                    'src' => !empty($variant->media) ? $variant->media[0]->src : null
                ]
            ]
        ];
        return $this;
    }

    public function buildRefundCreate(int $orderIdInt, array $canceledAmount, array $items): static
    {
        $this->payload = [];

        $orderId = config('channel_connector_for_remote.graphiql_id_header') . 'Order/' . $orderIdInt;

        $this->payload['input']['orderId'] = $orderId;
        $this->payload['input']['note'] = 'Item canceled by MXN channel-connector';
        $this->payload['input']['transactions'] = [
            'orderId' => $orderId,
            'kind' => 'REFUND',
            'amount' => $canceledAmount['canceled_order_amount'],
            'gateway' => 'exchange-credit',
        ];

        $this->payload['input']['refundLineItems'] = collect($items)?->map(function ($item) {
            return [
                'lineItemId' => $item['admin_graphql_api_id'],
                'quantity' => $item['quantity']
            ];
        })->toArray();
        return $this;
    }

    protected static function typeCancelFulfillment(): Mutation
    {
        return (new Mutation('fulfillmentCancel'))
            ->setVariables([new Variable('id', 'ID', true)])
            ->setArguments(['id' => '$id'])
            ->setSelectionSet([
                (new Query('fulfillment'))->setSelectionSet(['id']),
                (new Query('userErrors'))->setSelectionSet(['field','message'])
            ]);
    }

    protected static function typeInventoryAdjustQuantity(): Mutation
    {
        return (new Mutation('inventoryAdjustQuantity'))
            ->setVariables([new Variable('input', 'InventoryAdjustQuantityInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet(['available','id']);
    }

    protected static function typeProductAppendImages(): Mutation
    {
        return (new Mutation('productAppendImages'))
            ->setVariables([new Variable('input', 'ProductAppendImagesInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet([
                (new Query('newImages'))->setSelectionSet(['id', 'originalSrc']),
                (new Query('userErrors'))->setSelectionSet(['field', 'message'])
            ]);
    }

    protected static function typeRefundCreate(): Mutation
    {
        return (new Mutation('refundCreate'))
            ->setVariables([new Variable('input', 'RefundInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet([
                (new Query('refund'))
                ->setSelectionSet([
                    'id',
                    (new Query('order'))
                    ->setSelectionSet([
                        (new Query('currentSubtotalPriceSet'))
                        ->setSelectionSet([
                            (new Query('shopMoney'))->setSelectionSet(['amount'])
                        ]),
                        (new Query('currentTotalTaxSet'))
                        ->setSelectionSet([
                            (new Query('shopMoney'))->setSelectionSet(['amount'])
                        ]),
                        (new Query('currentTotalDiscountsSet'))
                        ->setSelectionSet([
                            (new Query('shopMoney'))->setSelectionSet(['amount'])
                        ])
                    ]),
                    (new Query('totalRefundedSet'))
                    ->setSelectionSet([
                        (new Query('shopMoney'))->setSelectionSet(['amount'])
                    ])
                ]),
                (new Query('userErrors'))->setSelectionSet(['field','message'])
            ]);
    }
}
