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
use App\Models\SupplyPriceSentHistory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
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
            $ordered_date= $payload['ordered_date'];
            $ordered_item_id= $payload['ordered_item_id'];
            $orderExist = Order::whereChannelOrderNumber($payload['number'])
                ->whereHas('orderItems', function (Builder $query) use ($ordered_date,$ordered_item_id) {
                    $query->where('c_item_recorded_at', $ordered_date);
                    $query->where('c_item_id', $ordered_item_id);
                })->count();

            if ($orderExist) {
                return false;
            }

            $orderPayload['input'] = app(OrderMapper::class)->getModelPayload($payload);
            $orderPayload['input']['orderItems'][] = app(OrderMapper::class)->getModelItemPayload($payload);

            if (!count($orderPayload['input']['orderItems'])) {
                return false;
            }

            $validator = Validator::make(
                $orderPayload['input'],
                app(CreateOrderInputValidator::class)->rules()
            );

            if ($validator->fails()) {
                $message = trans('mxncommerce.channel-connector::channel_connector.errors.order_validation_failed', [
                    'order_id_from_channel' => $payload['number'] ?? 'NA',
                ]);
                app(SendExceptionToCentralLog::class)(
                    [$message],
                    Response::HTTP_NOT_FOUND,
                );
                return false;
            }

            if (ConfigurationValue::getValue('balance_enable')) {
                $channelBalance = Balance::whereCurrencyId(
                    ConfigurationValue::getValue('channel_default_currency')
                )->first();

                if(!$channelBalance instanceof Balance) {
                    return false;
                }

                $variant = Override::whereIdFromRemote($payload['stock_id'])
                    ->where('overridable_type', Variant::class)
                    ->firstOrFail()->overridable;

                if ($variant->currency_id !== $channelBalance->currency_id) {
                    $message = trans('mxncommerce.channel-connector::channel_connector.errors.balance_currency_not_match', [
                        'channel_order_id' => $payload['number'],
                        'variant_currency_id' => $variant->currency_id,
                        'balance_currency_id' => $channelBalance->currency_id,
                    ]);
                    app(SendExceptionToCentralLog::class)(
                        [$message],
                        Response::HTTP_NOT_FOUND,
                    );
                    return false;
                }

                $lastSuppliedSent = $variant->supplyPriceSentHistories->last();
                if (!$lastSuppliedSent instanceof SupplyPriceSentHistory) {
                    $lastSuppliedSent = $variant->product->supplyPriceSentHistories->last();
                }

                if (empty($lastSuppliedSent->final_supply_price)) {
                    $message = trans('mxncommerce.channel-connector::channel_connector.errors.no_supply_price_history', [
                        'variant_id' => $variant->id,
                        'product_id' => $variant->product->id,
                    ]);
                    app(SendExceptionToCentralLog::class)(
                        [$message],
                        Response::HTTP_NOT_FOUND,
                    );
                    return false;
                }

                $supplyPriceHistory = (float)$lastSuppliedSent->final_supply_price;
                $supplyPriceCurrent = (float)$variant->product->representative_supply_price;

                $variantUnitSupplyPrice = $supplyPriceHistory;

                if ($supplyPriceHistory !== $supplyPriceCurrent) {
                    if ($supplyPriceHistory > $supplyPriceCurrent) {
                        $cond = ConfigurationValue::getValue('balance_channel_supply_price_more_than_connector');
                        if ( $cond === 'ORDER_CANCEL') {
                            $message = trans('mxncommerce.channel-connector::channel_connector.errors.supplied_price_not_match', [
                                'supply_price_history' => $supplyPriceHistory,
                                'supply_price_current' => $supplyPriceCurrent,
                            ]);
                            app(SendExceptionToCentralLog::class)(
                                [$message],
                                Response::HTTP_NOT_FOUND,
                            );
                            return false;
                        } elseif ($cond === 'DEDUCT_CURRENT') {
                            $variantUnitSupplyPrice = $supplyPriceCurrent;
                        }
                    } else {
                        $cond = ConfigurationValue::getValue('balance_channel_supply_price_less_than_connector');
                        if ($cond === 'ORDER_CANCEL') {
                            $message = trans('mxncommerce.channel-connector::channel_connector.errors.supplied_price_not_match', [
                                'supply_price_history' => $supplyPriceHistory,
                                'supply_price_current' => $supplyPriceCurrent,
                            ]);
                            app(SendExceptionToCentralLog::class)(
                                [$message],
                                Response::HTTP_NOT_FOUND,
                            );
                            return false;
                        } elseif ($cond === 'DEDUCT_CURRENT') {
                            $variantUnitSupplyPrice = $supplyPriceCurrent;
                        }
                    }
                }

                $totalChannelOrderAmount = $variantUnitSupplyPrice * (int)$payload['quantity'];
                if (!$totalChannelOrderAmount || $channelBalance->balance < $totalChannelOrderAmount) {
                    $message = trans('mxncommerce.channel-connector::channel_connector.errors.not_enough_balance', [
                        'balance_id' => $channelBalance->id,
                        'channel_order_id' => $payload['number']
                    ]);
                    app(SendExceptionToCentralLog::class)(
                        [$message],
                        Response::HTTP_NOT_FOUND,
                    );
                    return false;
                }

                $orderPayload['input']['total_order_amount'] = $totalChannelOrderAmount;
                $orderPayload['input']['orderItems'][0]['cc_item_supply_price'] = $variantUnitSupplyPrice;

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
