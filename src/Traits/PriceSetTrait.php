<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Exceptions\Api\WrongPayloadException;
use App\Helpers\ChannelConnectorFacade;
use App\Models\Features\PriceSet;
use App\Models\Override;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

trait PriceSetTrait
{
    /**
     * @param PriceSet $priceSet
     * @return $this
     * @throws Throwable
     */
    public function buildCreatePayload(PriceSet $priceSet): static
    {
        $priceSetOverride = null;
        if ($priceSet->override instanceof Override) {
            $priceSetOverride = json_decode($priceSet->override->fields_overrided);
        }
        $this->payload = [ 'input' => [] ];
        $this->payload['input']['vendor_id'] = ChannelConnectorFacade::configuration()->meta->vendor_id;

        $this->payload['input']['prodinc'] = (string)$priceSet->product->id;
        $this->payload['input']['currency_unit'] = $priceSet->currency->code;

        $this->payload['input']['supplyprice'] = (string)$priceSet->final_supply_price;
        $this->payload['input']['saleprice'] = (string)($priceSetOverride->sales_price ?? $priceSet->sales_price);
        $this->payload['input']['customerprice'] = (string)($priceSetOverride->msrp ?? $priceSet->msrp);

        if(empty($priceSet->product->categories[0])) {
            $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.no_category_in_product';
            $error = trans($error_namespace, [
                'product_id' => $priceSet->product->id,
            ]);
            throw new WrongPayloadException($error, Response::HTTP_BAD_REQUEST);
        }

        if(empty($priceSet->product->categories[0]->channelCategories[0]->code)) {
            $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.no_category_id_mapped_exist';
            $error = trans($error_namespace, [
                'category_id' => $priceSet->product->categories[0]->id,
                'product_id' => $priceSet->product->id,
            ]);
            throw new WrongPayloadException($error, Response::HTTP_BAD_REQUEST);
        }

        $this->payload['input']['category_id'] = $priceSet->product->categories[0]->channelCategories[0]->code;


        $this->payload['input']['euyn'] =
            ChannelConnectorFacade::checkProductFromEurope($priceSet->variant->countryOrigin->code) ? 'Y' : 'N';

        $this->payload['input'] = ['list' => [$this->payload['input']]];
        return $this;
    }

    public function buildUpdatePayload(PriceSet $priceSet): static
    {
        $priceSetOverride = null;
        if ($priceSet->override instanceof Override) {
            $priceSetOverride = json_decode($priceSet->override->fields_overrided);
        }
        $this->payload = [];
        $this->payload['input']['vendor_id'] = ChannelConnectorFacade::configuration()->meta->vendor_id;
        $this->payload['input']['prodinc'] = (string)$priceSet->product->id;
        $this->payload['input']['currency_unit'] = $priceSet->currency->name;

        $this->payload['input']['supplyprice'] = (string)$priceSet->final_supply_price;
        $this->payload['input']['saleprice'] = (string)($priceSetOverride->sales_price ?? $priceSet->sales_price);
        $this->payload['input']['customerprice'] = (string)($priceSetOverride->msrp ?? $priceSet->msrp);

        $this->payload['input']['category_id'] = 'M14830624|7';

        $this->payload['input']['euyn'] =
            ChannelConnectorFacade::checkProductFromEurope($priceSet->variant->countryOrigin->code) ? 'Y' : 'N';

        $this->payload['input'] = ['list' => [$this->payload['input']]];
        return $this;
    }

    protected static function convertProductStatus(string $status): string
    {
        return match (strtolower($status)) {
            'active' => '01',
            default => '04'
        };
    }

    protected static function getKGWeight($weight_unit, $weight): float
    {
        return match ($weight_unit) {
            'G' => round($weight / 1000),
            'OZ' => number_format($weight * 0.0283495, 3),
            'LB' => number_format($weight * 0.453592, 3),
            default => $weight
        };
    }
}
