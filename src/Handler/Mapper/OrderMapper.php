<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\Mapper;

use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\Order;

class OrderMapper
{
    public function getModelPayload(array $payload): array
    {
        $currencyFromChannel = $payload['currency'] ?? 'USD';
//        $currency = collect(Currency::query()->get())->where('code', $currencyFromChannel)->first();
        $currency = ChannelConnectorFacade::getCurrencyByCode($currencyFromChannel);

        $countryFromChannel = $payload['shipping_address']['country_code'] ?? 'US';
//        $shippingCountry = collect(Country::query()->get())->where('code' , $countryFromChannel)->first();
        $shippingCountry = ChannelConnectorFacade::getCountryByCode($countryFromChannel);

        $billingFromChannel = $payload['billing_address']['country_code'] ?? 'US';
//        $billingCountry = collect(Country::query()->get())->where('code' , $billingFromChannel)->first();
        $billingCountry = ChannelConnectorFacade::getCountryByCode($billingFromChannel);

        $shippingTotalAmount = collect($payload['shipping_lines'])->sum('price');

        if (!empty($payload['customer']['first_name']) || !empty($payload['customer']['last_name'])) {
            $customerName = $payload['customer']['first_name'] ?? null ;
            if(!empty($payload['customer']['last_name'])) {
                $customerName .= ' ' . $payload['customer']['last_name'];
            }
        } else {
            $customerName = $payload['customer']['default_address']['name'] ?? null ;
        }

        return [
            Order::CHANNEL_ORDER_NUMBER => (string)$payload['id'],
            Order::CURRENCY_ID => !empty($currency['id']) ? $currency['id'] : 1 ,

            Order::SUB_TOTAL_ORDER_AMOUNT => (float)$payload['current_subtotal_price'],
            Order::TOTAL_DISCOUNT_AMOUNT => (float)$payload['current_total_discounts'],
            Order::TOTAL_TAX_AMOUNT => (float)$payload['current_total_tax'],
            Order::TOTAL_SHIPPING_AMOUNT => (float)$shippingTotalAmount ?? null,
            Order::TOTAL_ORDER_AMOUNT => (float)$payload['current_total_price'],
            Order::CUSTOMER_NAME => $customerName,
            Order::CUSTOMER_EMAIL => $payload['customer']['email'] ?? null,
            Order::CUSTOMER_PHONE => $payload['customer']['phone']
                ?? $payload['customer']['default_address']['phone'] ?? null,
            Order::S_NAME => $payload['shipping_address']['name'] ?? null ,
            Order::S_PHONE => $payload['shipping_address']['phone'] ?? null ,

            Order::S_MOBILE => $payload['shipping_address']['phone'] ?? null ,

            Order::S_COMPANY => $payload['shipping_address']['company'] ?? null ,
            Order::S_ADDRESS_1 => $payload['shipping_address']['address1'] ?? null,
            Order::S_ADDRESS_2 => $payload['shipping_address']['address2'] ?? null,
            Order::S_CITY => $payload['shipping_address']['city'] ?? null,
            Order::S_PROVINCE => $payload['shipping_address']['province'] ?? null,
            Order::S_POSTAL_CODE => $payload['shipping_address']['zip'] ?? null,
            Order::S_COUNTRY_ID => !empty($shippingCountry['id']) ? $shippingCountry['id'] : 1 ,
            Order::S_ADDITIONAL_NOTE => $payload['note'] ?? null,
            Order::S_CUSTOMS_CLEARANCE_CODE => null,
            Order::B_NAME => $payload['billing_address']['name'] ?? null ,
            Order::B_PHONE => $payload['billing_address']['phone'] ?? null ,
            Order::B_COMPANY => $payload['billing_address']['company'] ?? null ,
            Order::B_ADDRESS_1 => $payload['billing_address']['address1'] ?? null,
            Order::B_ADDRESS_2 => $payload['billing_address']['address2'] ?? null,
            Order::B_CITY => $payload['billing_address']['city'] ?? null,
            Order::B_PROVINCE => $payload['billing_address']['province'] ?? null,
            Order::B_POSTAL_CODE => $payload['billing_address']['zip'] ?? null,
            Order::B_COUNTRY_ID => !empty($billingCountry['id']) ? $billingCountry['id'] : 1 ,
            Order::META_DATA => null,
        ];
    }
}
