<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Helpers\ChannelConnectorFacade;
use App\Helpers\Unit;
use App\Models\BaseModel;
use App\Models\Features\Configuration;
use App\Models\Features\Variant;
use Exception;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\Variable;
use Symfony\Component\HttpFoundation\Response;

trait VariantTrait
{
    public function buildCreatePayload(Variant $variant, array $extra = []): static
    {
        $this->payload = [];
        $this->payload['input']['productId'] = $variant->product->override->id_from_remote;

        if ($variant->barcode) {
            $this->payload['input']['barcode'] = $variant->barcode;
        }

        $configurationMeta = json_decode(Configuration::first()->meta, true);

        $this->payload['input']['inventoryItem'] = ['tracked' => true];
        $this->payload['input']['inventoryQuantities'] = [
            'availableQuantity' => $variant->inventorySet->available_stock_qty,
            'locationId' => $configurationMeta['shopify']['locationId']
        ];

        if (!empty($variant->meta_data)) {
            $this->payload['input']['metafields'] = [
                'type' => "string",
                'key' => config('channel_connector_for_remote.metafield_variant_key_name'),
                'value' => self::buildProductVariantMetaField($variant),
                'namespace' => config('channel_connector.channel_identifier'),
            ];
        }

        $this->payload['input']['options'] = [];

        $options = json_decode($variant->options, true);
        foreach ($options as $option) {
            $this->payload['input']['options'][] = $option['value'];
        }

        $this->payload['input']['price'] = ChannelConnectorFacade::getFinalSupplyPrice($variant->priceSet);
        $this->payload['input']['compareAtPrice'] = $variant->priceSet->msrp;
        $this->payload['input']['sku'] = $variant->sku;
        $this->payload['input']['taxCode'] = $variant->tax_option;
        $this->payload['input']['weight'] = $variant->weight;
        $this->payload['input']['weightUnit'] = app(Unit::class)::getFullWeightUnitName($variant->weight_unit);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function buildUpdatePayload(Variant $variant, array $extra = []): static
    {
        $this->payload = [];
        $overridedData = isset($variant->override->fields_overrided) ?
            json_decode($variant->override->fields_overrided, true) : [];

        $this->payload['input']['id'] = $variant->override->id_from_remote;

        if ($variant->barcode) {
            $this->payload['input']['barcode'] = $variant->barcode;
        }

        $this->payload['input']['inventoryItem'] = ['tracked' => true];
        if (!empty($variant->meta_data)) {
            $this->payload['input']['metafields'] = [
                'type' => "string",
                'key' => config('channel_connector_for_remote.metafield_variant_key_name'),
                'value' => self::buildProductVariantMetaField($variant),
                'namespace' => config('channel_connector.channel_identifier'),
            ];

            if (!empty($variant->override->id_from_remote)) {
                if(empty($variant->override->meta_id_from_remote)) {
                    $this->setupOverrideData($variant);
                    $variant->load(BaseModel::OVERRIDE);
                }
            }

            $metaFromRemote = json_decode(
                $variant->override->meta_id_from_remote,
                true
            );

            if (empty($metaFromRemote['id'])) {
                throw new Exception(
                    trans('metafield_not_set', ['id' => $variant->id]), Response::HTTP_FORBIDDEN
                );
            }
            $this->payload['input']['metafields']['id'] = $metaFromRemote['metafields']['edges'][0]['node']['id'];
        }

        // todo: option has changed
        if (isset($overridedData['options'])) {
            $this->payload['input']['options'] = $overridedData['options'];
        } else {
            $variantOptions = [];
            $options = json_decode(
                stripslashes(buildValidJson($variant->options)) ?? '{}',
                true
            );
            foreach ($options as $option) {
                $variantOptions[] = $option['value'];
            }
            $this->payload['input']['options'] = $variantOptions;
        }

//        $this->payload['input']['price'] = $variant->priceSet->sales_price;
        $this->payload['input']['price'] = ChannelConnectorFacade::getFinalSupplyPrice($variant->priceSet);


        $this->payload['input']['compareAtPrice'] = $variant->priceSet->msrp;
        $this->payload['input']['sku'] = $variant->sku;
        $this->payload['input']['taxCode'] = $variant->tax_option;
        $this->payload['input']['weight'] = $variant->weight;
        $this->payload['input']['weightUnit'] = app(Unit::class)::getFullWeightUnitName($variant->{Variant::WEIGHT_UNIT});

        return $this;
    }

    /**
     * @param Variant $variant
     * @return array
     * @throws Exception
     */
    public function setupOverrideData(Variant $variant): array
    {
        $id = $variant->override->id_from_remote;
        $res = $this->requestQuery(self::typeProductVariant($id));

        if (!empty($res['errors'])) {
            throw new Exception(
                $res['errors'][0]['message'], Response::HTTP_FORBIDDEN
            );
        }

        $payloadFromRemote = ['productVariant' => $res['productVariant']];
        $this->setOverrideDataFromRemote($variant, $payloadFromRemote);
        return $res;
    }

    public function buildUpdateVariantImagePayload(Variant $variant, string $imageId = null): static
    {
        if (!isset($imageId)) {
            return $this;
        }
        $this->payload = [
            'input' => [
                'id' => $variant->override->id_from_remote,
                'imageId' => $imageId
            ]
        ];
//        $this->payload['input']['id'] = $variant->override->id_from_remote;
//        $this->payload['input']['imageId'] = $imageId;

        return $this;
    }

    protected static function typeProductVariant (string $id): Query
    {
        return self::resultQuery()->setArguments(['id' => $id]);
    }

    protected static function typeVariantCreate(): Mutation
    {
        return (new Mutation('productVariantCreate'))
            ->setVariables([new Variable('input', 'ProductVariantInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet([
                self::resultQuery(),
                (new Query('userErrors'))->setSelectionSet(['field','message'])
            ]);
    }

    protected static function typeVariantUpdate(): Mutation
    {
        return (new Mutation('productVariantUpdate'))
            ->setVariables([new Variable('input', 'ProductVariantInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet([
                self::resultQuery(),
                (new Query('userErrors'))->setSelectionSet(['field','message'])
            ]);
    }

    protected static function resultQuery(): Query
    {
        return (new Query('productVariant'))
            ->setSelectionSet([
                'id',
                (new Query('image'))->setSelectionSet(['id', 'originalSrc']),
                (new Query('inventoryItem'))
                ->setSelectionSet([
                    'id',
                    (new Query('inventoryLevels'))->setArguments(['first' => 1])
                    ->setSelectionSet([ self::edgesNode(['id', 'available']) ]),
                ]),
                self::getMetafields(),
            ]);
    }
}
