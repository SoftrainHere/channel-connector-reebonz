<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\Mapper;

use App\Exceptions\Api\NotDealableOrderException;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\ConfigurationValue;
use App\Models\Features\Product;
use App\Models\Features\Variant;
use App\Models\Override;
use Illuminate\Support\Carbon;
use Throwable;

class OrderMapper
{
    public function getModelPayload(array $payload): array
    {
        $country_code = ChannelConnectorFacade::getCountryById(
            (int)ConfigurationValue::getValue('channel_default_country')
        )->code;

        $currency_code = ChannelConnectorFacade::getCurrencyById(
            (int)ConfigurationValue::getValue('channel_default_currency')
        )->code;

        return [
            'channel_order_number' => (string)$payload['number'],
            'currency_code' => $currency_code,
            'total_order_amount' => (float)$payload['product_supply_price'],
            'customer_first_name' => $payload['order_user'],
            'customer_last_name' => $payload['order_user'],
            'customer_email' => ConfigurationValue::getValue('channel_default_customer_email'),
            'customer_phone' => $payload['phone'] ?? null,
            's_first_name' => $payload['recipient'] ?? null ,
            's_last_name' => $payload['recipient'] ?? null ,
            's_phone' => $payload['phone'] ?? null ,
            's_address_1' => $payload['address'] ?? null,
            's_city' => ' . ',
            's_country_code' => $country_code,
            's_additional_note' => $payload['extra_request'] ?? null,
            's_customs_clearance_code' => $payload['clearance_number'] ?? null,
            'b_first_name' => $payload['order_user'] ,
            'b_last_name' => $payload['order_user'] ,
            'b_address_1' => $payload['address'] ?? null,
            'b_city' => ' . ',
            'b_country_code' => $country_code,
            'meta_data' => null,
        ];
    }

    /**
     * @throws Throwable
     */
    public function getModelItemPayload(array $payload): array|null
    {
        $override = Override::whereIdFromRemote($payload['product_id'])
            ->where('overridable_type', Product::class)->first();
        throw_if(
            !$override->overridable instanceof Product,
            NotDealableOrderException::class
        );

        $overrideModelItem = Override::whereIdFromRemote($payload['stock_id'])
            ->where('overridable_type', Variant::class)
            ->first();
        throw_if(
            !$overrideModelItem->overridable instanceof Variant,
            NotDealableOrderException::class
        );

        $currency_code = ChannelConnectorFacade::getCurrencyById(
            (int)ConfigurationValue::getValue('channel_default_currency')
        )->code;

        return [
            'variant_id' => $overrideModelItem->overridable->id,
            'product_id' => $override->overridable->id,
            'channel_order_number' => (string)$payload['number'],
            'quantity' => $payload['quantity'],
            'c_item_id' => $payload['ordered_item_id'],
            'c_item_sku' => $payload['marketplace_product_code'],
            'c_item_title' => $payload['product_name'],
            'c_item_options' => json_encode($payload['product_option_name']),
            'c_item_currency_code' => $currency_code,
            'c_item_sales_price' => $payload['product_selling_price'],
            'c_item_supply_currency_code' => $currency_code,
            'c_item_recorded_at' => Carbon::now()->toDateTimeString(),
            'cc_item_customs_currency_code' => $currency_code,
	        'cc_item_customs_value' => $payload['product_supply_price'],
        ];
    }
}
