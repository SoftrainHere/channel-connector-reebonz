<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\FromChannel;

use App\Exceptions\Api\NotDealableOrderException;
use App\GraphQL\Validators\Features\Order\CreateOrderInputValidator;
use App\GraphQL\Mutations\Features\Order\OrderMutator;
use App\Helpers\ChannelConnectorFacade;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Balance;
use App\Models\Features\ConfigurationValue;
use App\Models\Features\Order;
use App\Models\Features\Variant;
use App\Models\Override;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Mxncommerce\ChannelConnector\Handler\Mapper\OrderMapper;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
            // Order is not saved to override
//            $overrideModel = Override::whereIdFromRemote($payload['number'])
//                ->where('overridable_type', Order::class)->first();
//            if ($overrideModel instanceof Override) {
//                //:order_id_from_channel) has been already saved to cc with(:order_id)',
//                $message = trans('mxncommerce.channel-connector::channel_connector.errors.order_already_saved', [
//                    'order_id_from_channel' => $payload['number'] ?? 'NA',
//                    'order_id' => $order['stock_id'] ?? 'NA'
//                ]);
//                app(SendExceptionToCentralLog::class)(
//                    [$message],
//                    Response::HTTP_NOT_FOUND,
//                );
//                return false;
//            }

            $orderPayload['input'] = app(OrderMapper::class)->getModelPayload($payload);
            $orderPayload['input']['orderItems'][] = app(OrderMapper::class)->getModelItemPayload($payload);

            if (!count($orderPayload['input']['orderItems'])) {
                return false;
            }

            Validator::make(
                $orderPayload['input'],
                app(CreateOrderInputValidator::class)->rules()
            )->validate();

            if (ConfigurationValue::getValue('balance_enable')) {

                $channelBalance = Balance::whereCurrencyId(
                    ConfigurationValue::getValue('channel_default_currency')
                )->first();

                if(!$channelBalance instanceof Balance) {
                    return false;
                }

                $totalChannelOrderAmount = (int)$payload['product_supply_price'] * (int)$payload['quantity'];

                $variant = Override::whereIdFromRemote($payload['stock_id'])
                    ->where('overridable_type', Variant::class)
                    ->firstOrFail()->overridable;

                if ($variant->currency_id !== $channelBalance->currency_id) {
                    return false;
                }

                $variantUnitSupplyPrice = $variant->finalSupplyPrice;

                if (
                    empty($payload['product_supply_price']) ||
                    ((float)$variant->finalSupplyPrice !== (float)$payload['product_supply_price'])
                ) {
                    if (ConfigurationValue::getValue('balance_order_cancel_when_supplied_price_not_match')) {
                        $message = trans('mxncommerce.channel-connector::channel_connector.errors.supplied_price_not_match', [
                            'order_id_from_channel' => $payload['number'] ?? 'NA',
                            'supply_price_from_channel' => $payload['product_supply_price'] ?? 'NA',
                            'supply_price_from_system' => $variant->finalSupplyPrice ?? 'NA'
                        ]);
                        app(SendExceptionToCentralLog::class)(
                            [$message],
                            Response::HTTP_NOT_FOUND,
                        );
                        return false;
                    }

                    if (ConfigurationValue::getValue('balance_type_of_debit') === 'VALUE_OF_CURRENT_SYSTEM') {
                        $lastSuppliedSent = $variant->supplyPriceSentHistories->last();
                        $variantUnitSupplyPrice = $lastSuppliedSent->final_supply_price ?? $variant->finalSupplyPrice;
                        $totalChannelOrderAmount = (float)$variantUnitSupplyPrice * (int)$payload['quantity'];
                    }
                }

                if (!$totalChannelOrderAmount || $channelBalance->balance < $totalChannelOrderAmount) {
                    return false;
                }

                $orderPayload['input']['total_order_amount'] = $totalChannelOrderAmount;
                $orderPayload['input']['orderItems'][0]['c_item_supply_price'] = $variantUnitSupplyPrice;

                DB::beginTransaction();

                $order = app(OrderMutator::class)->create(null, $orderPayload);
                if (!$order instanceof Order) {
                    DB::rollBack();
                    return false;
                }

                /*
                 | ---------------------------------------------
                 | Deduct balance
                 | ---------------------------------------------
                */
                ChannelConnectorFacade::deductBalanceForOrder(
                    $totalChannelOrderAmount,
                    $channelBalance->id,
                    $order->orderItems[0]->id
                );
                DB::commit();
            } else  {
                $order = app(OrderMutator::class)->create(null, $orderPayload);
                if (!$order instanceof Order) {
                    return false;
                }
            }
            return true;
        } catch (NotDealableOrderException $e) {
            // todo leave some message on Central log
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}
