<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\Mapper;

use App\Exceptions\Api\NotDealableOrderException;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\ConfigurationValue;
use App\Models\Features\Product;
use App\Models\Features\Variant;
use App\Models\Override;
use Illuminate\Support\Carbon;
use Str;
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

        $total_discount_amount = 0;
        if ($payload['product_supply_price'] > $payload['product_selling_price']) {
            $total_discount_amount =
                ($payload['product_supply_price'] - $payload['product_selling_price']) *
                (int)$payload['quantity'];
        }

        $nameArray = self::extractName($payload['recipient'] ?? null);

        if((int)ConfigurationValue::getValue('use_business_identification_number') === 1) {
            $meta_data=json_encode(
                ['business_identification_number'=>ConfigurationValue::getValue('business_identification_number'),
                'business_identification_name'=>ConfigurationValue::getValue('business_identification_name')]);
        } else {
            $meta_data = null;
        }


        return [
            'channel_order_number' => (string)$payload['number'],
            'currency_code' => $currency_code,
            'total_order_amount' => (float)$payload['product_supply_price'] * (int)$payload['quantity'],
            'total_discount_amount' => $total_discount_amount,
            'customer_first_name' => $payload['order_user'],
            'customer_last_name' => $payload['order_user'],
            'customer_email' => ConfigurationValue::getValue('channel_default_customer_email'),
            'customer_phone' => $payload['phone'] ?? null,
            's_first_name' => $nameArray[1],
            's_last_name' => $nameArray[0],
            's_phone' => $payload['phone'] ?? null ,
            's_address_1' => $payload['address'] ?? null,
            's_city' => self::extractCity($payload['address'] ?? null),
            's_postal_code' => self::extractPostalCode($payload['address'] ?? null),
            's_country_code' => $country_code,
            's_additional_note' => $payload['extra_request'] ?? null,
            's_customs_clearance_code' => $payload['clearance_number'] ?? null,
            'b_first_name' => $payload['order_user'] ,
            'b_last_name' => $payload['order_user'] ,
            'b_address_1' => $payload['address'] ?? null,
            'b_city' => ' . ',
            'b_country_code' => $country_code,
            'meta_data' => $meta_data,
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
            'c_item_product_id' => $payload['product_id'],
            'c_item_variant_id' => $payload['stock_id'],
            'c_item_sku' => $payload['marketplace_product_code'],
            'c_item_title' => $payload['product_name'],
            'c_item_options' => json_encode($payload['product_option_name']),
            'c_item_currency_code' => $currency_code,
            'c_item_sales_price' => $payload['product_selling_price'],
            'c_item_supply_currency_code' => $currency_code,
            'c_item_supply_price' => $payload['product_supply_price'] ?? null,
            'c_item_recorded_at' => $payload['ordered_date'] ?? '',
            'cc_item_customs_currency_code' => $currency_code,
	        'cc_item_customs_value' => $payload['product_supply_price'],
        ];
    }

    private static function extractPostalCode(string|null $address):string|null
    {
        if (!$address) {
            return null;
        }

        preg_match('/^\[\d+]/', $address, $matches);
        if (!empty($matches[0])) {
            return str_replace(['[',']'],'', $matches[0]);
        } else {
            return null;
        }
    }

    private static function extractCity(string|null $address):string
    {
        if (!$address) {
            return '.';
        }

        $explodes = explode(' ', $address);
        return $explodes[1] ?? '.';
    }

    private static function extractName(string|null $fullName):array
    {
        if (!$fullName) {
            return [];
        }

        $nameArray = [];
        $len = Str::length($fullName);
        $nameArray[0] = Str::substr($fullName, 0, 1);
        $nameArray[1] = Str::substr($fullName, 1, $len);

        return $nameArray;
    }
}
