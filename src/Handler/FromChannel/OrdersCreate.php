<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\FromChannel;

use App\GraphQL\Validators\Features\Order\CreateOrderInputValidator;
use App\Helpers\ChannelConnectorFacade;
use App\GraphQL\Mutations\Features\Order\OrderAdd;
use App\Models\ChannelDeal;
use App\Models\Features\Configuration;
use App\Models\Features\Order;
use App\Models\Features\Variant;
use App\Models\Override;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Mxncommerce\ChannelConnector\Handler\Mapper\OrderItemMapper;
use Mxncommerce\ChannelConnector\Handler\Mapper\OrderMapper;
use Mxncommerce\ChannelConnector\Helpers\ChannelConnectorHelper;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrdersCreate
{
    /**
     * @param array $payload
     * @return bool
     * @throws Throwable
     */
    public function __invoke(array $payload): bool
    {
        if (Order::whereChannelOrderNumber($payload['id'])->exists()) {
            throw new MultipleRecordsFoundException(
                trans('errors.order_already_exist', ['order_id' => $payload['id']]),
                Response::HTTP_CONFLICT
            );
        }

        $orderPayload = [];
        if (empty($payload['customer'])) {
            if (!in_array(Str::lower(config('app.env')), ['production', 'scheduler'])) {
                echo 'No customer information for order ' . $payload['id'] . PHP_EOL;
            } else {
                Log::info('No customer information for order ' . $payload['id']);
            }
            return false;
        }

        $channelConfig = ChannelConnectorFacade::configuration();

        $orderPayload['input'] = app(OrderMapper::class)->getModelPayload($payload);
        $orderPayload['input']['order_items'] = [];
        $canceledLineItems = [];
        $canceledAmount = [];
        foreach ($payload['line_items'] as $lineItem) {
            $localVariant = $this->checkVariantHandled($lineItem, $channelConfig);
            if (!$localVariant instanceof Variant) {
                $canceledLineItems[] = $lineItem;
            } else {
                if (!($channelConfig->order->rules->send_order_with_none_stock_available ?? 0)) {
                    // even if cc has partial stock of item,
                    // let it canceled unless cc has enough stock of item
                    if (
                        (int)$localVariant->inventorySet->available_stock_qty < 1 ||
                        ($localVariant->inventorySet->available_stock_qty < $lineItem['quantity'])
                    ) {
                        $canceledLineItems[] = $lineItem;
                        continue;
                    }
                }

                $lineItem['created_at'] = $payload['created_at'];
                $orderItemPayload = app(OrderItemMapper::class)->getModelPayload(
                    $lineItem,
                    $localVariant,
                    $payload['id']
                );
                $orderPayload['input']['order_items'][] = $orderItemPayload;
            }
        }

        if (count($canceledLineItems)) {
            $canceledAmount = $this->recalculateOrder(
                $payload['id'],
                $canceledLineItems,
                $payload['total_shipping_price_set'],
                count($payload['line_items']) > count($canceledLineItems)
            );
        }

        if (!empty($canceledAmount['canceled_sub_total'])) {
            $orderPayload['input']['sub_total_order_amount'] -= $canceledAmount['canceled_sub_total'];
            $orderPayload['input']['total_discount_amount'] -= $canceledAmount['canceled_discount_amount'];
            $orderPayload['input']['total_tax_amount'] -= $canceledAmount['canceled_tax_amount'];

            // todo: do this later, policies not prepared yet
            $orderPayload['input']['total_order_amount'] -= $canceledAmount['canceled_order_amount'];
        }

        if (count($orderPayload['input']['order_items']) > 0) {
            Validator::make(
                $orderPayload['input'],
                app(CreateOrderInputValidator::class)->rules()
            )->validate();
            $order = app(OrderAdd::class)->create(null, $orderPayload);
            if (!$order instanceof Order) {
                // todo: order creation failed, what to do?
                // throw something
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * @param array $item
     * @param Configuration $configuration
     * @return Variant|null
     */
    private function checkVariantHandled(array $item, Configuration $configuration): Variant|null
    {
        $variant = Override::whereOverridableType(Variant::class)
            ->whereIdFromRemote(
                app(ChannelConnectorHelper::class)
                    ->getShopifyGlobalIdHeader('ProductVariant', (string)$item['variant_id'])
            )->first()->overridable;

        if (!$variant instanceof Variant) {
            ChannelConnectorFacade::moveExceptionToCentral(
                [trans('errors.order_cancelled_for.no_active_variant', [
                    'variant_id' => $variant->{Variant::ID}
                ])],
                Response::HTTP_NOT_FOUND,
            );
            return null;
        }

        if (!empty($variant->product->channelDeal->id)) {
            if ($variant->product->channelDeal->status === ChannelDeal::STATUS_INACTIVE) {
                return null;
            }
        }

        if (empty($variant->product->brand->channelDeal->id)) {
            if ($configuration->bind_new_product_to_channel_deals) {
                $channelDeal = new ChannelDeal();
                $channelDeal->channelDealable()->associate($variant->product->brand);
                $channelDeal->channel_dealable_id = $variant->product->brand->id;
                $channelDeal->save();
            } else {
                return null;
            }
        } else {
            if ($variant->product->brand->channelDeal->status === ChannelDeal::STATUS_INACTIVE) {
                return null;
            }
        }

        return $variant;
    }

    #[ArrayShape([
        'canceled_sub_total' => "float|int",
        'canceled_discount_amount' => "int|mixed",
        'canceled_tax_amount' => "int|mixed",
        'canceled_order_amount' => "float|int"
    ])]
    private function recalculateOrder(
        int $orderId,
        array $canceledLineItems,
        array $totalShippingPriceSet,
        bool $canceledItemPartially
    ): array {
        $configuration = ChannelConnectorFacade::configuration();
        $canceledAmount = [
            'canceled_sub_total' => 0,
            'canceled_discount_amount' => 0,
            'canceled_tax_amount' =>  0,
            'canceled_shipping_price_amount' => $totalShippingPriceSet['shop_money']['amount'] ?? 0,
            'canceled_order_amount' =>  0,
        ];

        foreach ($canceledLineItems as $payload) {
            $totalDiscount = collect($payload['discount_allocations'])->pluck('amount')->sum();
            $totalPriceOfCanceledOrder = $payload['price'] * $payload['quantity'] - $totalDiscount;
            $canceledAmount['canceled_sub_total'] += $totalPriceOfCanceledOrder;
            $canceledAmount['canceled_discount_amount'] += $totalDiscount;
            $canceledAmount['canceled_tax_amount'] += collect($payload['tax_lines'])->pluck('price')->sum();
            $canceledAmount['canceled_order_amount'] += $totalPriceOfCanceledOrder;
        }
        $canceledAmount['canceled_order_amount'] += $canceledAmount['canceled_tax_amount'];
        if (!$canceledItemPartially) {
            $canceledAmount['canceled_order_amount'] += $canceledAmount['canceled_shipping_price_amount'];
        }

        if ($configuration->refund->rules->use_channel_api_for_order_refund ?? null) {
            app(ShopifyBasicResource::class)->makeRefund($orderId, $canceledAmount, $canceledLineItems);
        }

        return $canceledAmount;
    }
}
