<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\FromChannel;

use App\Exceptions\Api\NotDealableOrderException;
use App\GraphQL\Validators\Features\Order\CreateOrderInputValidator;
use App\GraphQL\Mutations\Features\Order\OrderMutator;
use App\Models\Features\Order;
use App\Models\Override;
use Exception;
use Illuminate\Support\Facades\Validator;
use Mxncommerce\ChannelConnector\Handler\Mapper\OrderMapper;
use Throwable;

/**
 * This method is for retrieving order data
 * through channel-api directly into CC
 */
class OrderCreate
{
    /**
     * @param array $payload
     * @return bool
     * @throws Throwable
     */
    public function __invoke(array $payload): bool
    {
        try {
            $overrideModel = Override::whereIdFromRemote($payload['number'])
                ->where('overridable_type', Order::class)->first();
            if ($overrideModel instanceof Override) {
                return false;
            }

            $orderPayload['input'] = app(OrderMapper::class)->getModelPayload($payload);
            $orderPayload['input']['orderItems'][] = app(OrderMapper::class)->getModelItemPayload($payload);

            if (count($orderPayload['input']['orderItems']) > 0) {
                Validator::make(
                    $orderPayload['input'],
                    app(CreateOrderInputValidator::class)->rules()
                )->validate();
                $order = app(OrderMutator::class)->create(null, $orderPayload);
                if (!$order instanceof Order) {
                    // todo: order creation failed, what to do?
                    // throw something
                    return false;
                }
                return true;
            }
        } catch (NotDealableOrderException $e) {
            // todo leave some message on Central log
            return false;
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

//    private function checkVariantHandled(array $item, Configuration $configuration): Variant|null
//    {
//        $variant = Override::whereOverridableType(Variant::class)
//            ->whereIdFromRemote(
//                app(ChannelConnectorHelper::class)
//                    ->getShopifyGlobalIdHeader('ProductVariant', (string)$item['variant_id'])
//            )->first()->overridable;
//
//        if (!$variant instanceof Variant) {
//            ChannelConnectorFacade::moveExceptionToCentral(
//                [trans('errors.order_cancelled_for.no_active_variant', [
//                    'variant_id' => $variant->{Variant::ID}
//                ])],
//                Response::HTTP_NOT_FOUND,
//            );
//            return null;
//        }
//
//        if (!empty($variant->product->channelDeal->id)) {
//            if ($variant->product->channelDeal->status === ChannelDeal::STATUS_INACTIVE) {
//                return null;
//            }
//        }
//
//        if (empty($variant->product->brand->channelDeal->id)) {
//            if ($configuration->bind_new_product_to_channel_deals) {
//                $channelDeal = new ChannelDeal();
//                $channelDeal->channelDealable()->associate($variant->product->brand);
//                $channelDeal->channel_dealable_id = $variant->product->brand->id;
//                $channelDeal->save();
//            } else {
//                return null;
//            }
//        } else {
//            if ($variant->product->brand->channelDeal->status === ChannelDeal::STATUS_INACTIVE) {
//                return null;
//            }
//        }
//
//        return $variant;
//    }
}
