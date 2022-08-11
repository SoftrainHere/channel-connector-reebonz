<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\FromChannel;

use App\Enums\OrderAddressChangeRequesterType;
use App\Enums\OrderAddressChangeStatusType;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\Order;
use App\Models\Features\OrderAddressChange;

class OrdersUpdated
{
    public function __invoke(array $payload): bool
    {
        ChannelConnectorFacade::echoDev('OrdersUpdated->__invoke()');

        $order = Order::whereChannelOrderNumber($payload['id'])->first();
        if (!$order instanceof Order) {
            ChannelConnectorFacade::echoDev('No order exist for ' . __CLASS__ . ' number of ' . $payload['id']);
            return false;
        }

        $order =  Order::where(Order::CHANNEL_ORDER_NUMBER, $payload['id'])->first();
        if (!$order instanceof Order) {
            return false;
        }

        /*
         * We handle update-event only when order's shipping address changed
        */
        if (!empty($payload['shipping_address'])) {
            $changedAddress = $this->getLatestAddressIfChanged($order, $payload);
            if ($changedAddress instanceof OrderAddressChange) {
                $changedAddress->order_id = $order->id;
                $changedAddress->c_order_number = $payload['id'];
                $changedAddress->change_reason = 'CA';
                $changedAddress->s_country_id = ChannelConnectorFacade::getCountryByCode(
                    $payload['shipping_address']['country_code']
                )->id;
                $order->orderAddressChange()->save($changedAddress);
            }
        }

        return true;
    }

    private function getLatestAddressIfChanged(Order $order, array $payload): OrderAddressChange|null
    {
        $shippingAddress = $payload['shipping_address'];
        $updatedShippingAddress = serialize([
            's_name' => $shippingAddress['name'],
            's_phone' => $shippingAddress['phone'],
            's_mobile' => $shippingAddress['phone'],
            's_company' => $shippingAddress['company'],
            's_address_1' => $shippingAddress['address1'],
            's_address_2' => $shippingAddress['address2'],
            's_city' => $shippingAddress['city'],
            's_province' => $shippingAddress['province'],
            's_postal_code' => $shippingAddress['zip'],
            's_additional_note' => $payload['note'] ?? null,
        ]);

        $savedShippingAddress = serialize([
            's_name' => $order->s_name,
            's_phone' => $order->s_phone,
            's_mobile' => $order->s_mobile,
            's_company' => $order->s_company,
            's_address_1' => $order->s_address_1,
            's_address_2' => $order->s_address_2,
            's_city' => $order->s_city,
            's_province' => $order->s_province,
            's_postal_code' => $order->s_postal_code,
            's_additional_note' => $order->s_additional_note,
        ]);

        if ($updatedShippingAddress !== $savedShippingAddress) {
            $orderAddressChanges = new OrderAddressChange();
            $orderAddressChanges->status = OrderAddressChangeStatusType::Pending->value;
            $orderAddressChanges->requester_type = OrderAddressChangeRequesterType::Ca->value;
            $orderAddressChanges->s_name = $shippingAddress['name'];
            $orderAddressChanges->s_phone = $shippingAddress['phone'];
            $orderAddressChanges->s_mobile = $shippingAddress['phone'];
            $orderAddressChanges->s_company = $shippingAddress['company'];
            $orderAddressChanges->s_address_1 = $shippingAddress['address1'];
            $orderAddressChanges->s_address_2 = $shippingAddress['address2'];
            $orderAddressChanges->s_city = $shippingAddress['city'];
            $orderAddressChanges->s_province = $shippingAddress['province'];
            $orderAddressChanges->s_postal_code = $shippingAddress['zip'];
            $orderAddressChanges->s_additional_note = $payload['note'];

            return $orderAddressChanges;
        }

        return null;
    }
}
