<?php

declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Enums\VariantSalesStatusType;
use App\Enums\VariantStatusType;
use App\Exceptions\Api\ProductWithoutCategoryException;
use App\Exceptions\Api\ProductWithoutChannelBrandException;
use App\Helpers\ChannelConnectorFacade;
use App\Models\ChannelCategory;
use App\Models\Features\Category;
use App\Models\Features\ConfigurationValue;
use App\Models\Features\Country;
use App\Models\Features\Medium;
use App\Models\Features\Product;
use App\Models\Override;
use Mxncommerce\ChannelConnector\Helpers\ChannelConnectorHelper;
use Throwable;

trait ProductHandlerTrait
{
    /**
     * @param Product $product
     * @return $this
     * @throws Throwable
     */
    public function buildCreatePayload(Product $product): static
    {
        $this->payload = [];

        $this->payload['input']['created_from'] =
            ConfigurationValue::getValue('channel_connector_identifier_from_channel');

        $this->payload['input']['name'] = (string)$product->descriptionSetWithLanguage?->title;
        $this->payload['input']['code'] = (string)$product->getRepresentativeProperty('sku');
        $this->payload['input']['marketplace_code'] = $product->id;
        $this->payload['input']['available'] = 1;

        if (ConfigurationValue::getValue('use_brand_mapper')) {
            if (!count($product->vendorBrand->brand->channelBrand)) {
                throw new ProductWithoutChannelBrandException(null);
            }
        }

        $this->payload['input']['brand_id'] = $product->vendorBrand->brand->channelBrand[0]->id;
        $this->payload['input']['marketplace_price'] = ceil($product->representative_supply_price);
        $this->payload['input']['local_currency_price'] = ceil($product->representative_supply_price);
        $this->payload['input']['local_currency_code'] = config('channel_connector_for_remote.local_currency_code');
        $this->payload['input']['commission'] = config('channel_connector_for_remote.commission');
        $this->payload['input']['material'] = $product->getRepresentativeProperty('materials');
        // $this->payload['input']['color'] = '';
        // $this->payload['input']['model_name'] = '';
        // $this->payload['input']['season'] = '';

        if ($product->representativeVariant->count()) {
            $representativeVariant = $product->representativeVariant[0];
        } else {
            $representativeVariant = $product->variants
                ->where('status', VariantStatusType::Active->value)
                ->where('sales_status', VariantSalesStatusType::Enabled->value)
                ->first();
        }
        $this->payload['input']['country'] = Country::find($representativeVariant->coo_id)->name;

        // $this->payload['input']['product_feature'] = '';
        // $this->payload['input']['size_standard'] = '';
        $descriptionSet = $product->descriptionSetWithLanguage;
        $description = (string)$descriptionSet->description;

        if ($descriptionSet->override) {
            $fieldOverride = $descriptionSet->override->fields_overrided ?
                json_decode($descriptionSet->override->fields_overrided) : null ;
            if (isset($fieldOverride->description)) {
                $description = $fieldOverride->description;
            }
        }
        $this->payload['input']['description'] = $description;
        // $this->payload['input']['legal_info'] = '';
        // $this->payload['input']['product_notification'] = '';
        // $this->payload['input']['product_tip'] = '';
        // $this->payload['input']['size_info '] = '';
        if (empty($product->representativeCategory)) {
            throw new ProductWithoutCategoryException(null);
        }

        $channelCategoryPayload = $this->getChannelCategoryFormat($product->representativeCategory);
        if (!$channelCategoryPayload) {
            throw new ProductWithoutCategoryException(null);
        }

        $this->payload['input']['category_gender_id'] = $channelCategoryPayload['category_gender_id'];
        $this->payload['input']['category_master_id'] = $channelCategoryPayload['category_master_id'];
        $this->payload['input']['category_slave_id'] = $channelCategoryPayload['category_slave_id'];
        $this->payload['input']['category_slave_id2'] =  $channelCategoryPayload['category_slave_id2'];

        $this->payload['input']['image_main_url'] =
            config('channel_connector.nmo_image_root').stripslashes($product->media[0]->src);

//        if (empty(collect($product->media)->filter(function (Medium $medium){
//            return $medium['src'] !== config('channel_connector_for_remote.img_src');
//        })->all())) {
//            throw new ProductWithoutImageException(null);
//        }

        $this->payload['input']['detail_images'] = $product->media
            ->slice(0, config('channel_connector_for_remote.maximum_images'))
            ->map(function ($item) {
                if ($item->override instanceof Override) {
                    $imageOverride = json_decode($item->override->fields_overrided);
                    return [
                        'detail_image_url' => $imageOverride?->src ?? config('channel_connector.nmo_image_root').stripslashes((string)$item->src)
                    ];
                } else {
                    return [
                        'detail_image_url' => config('channel_connector.nmo_image_root').stripslashes((string)$item->src)
                    ];
                }
            });

        $this->payload['input']['stocks'] = $product->variants
            ->slice(0, config('channel_connector_for_remote.maximum_variants'))
            ->map(function ($item) {
                $options = json_decode(
                    app(ChannelConnectorHelper::class)->buildValidJson($item->options),
                    true
                );

                $optionStringForChannel = '';

                foreach ($options as $option) {
                    if (is_array($option)) {
                        $optionStringForChannel .= $option['name']  . '=' . $option['value'] .', ';
                    }
                }
                $available=$item['status']===VariantStatusType::Active->value&&
                    $item['sales_status']===VariantSalesStatusType::Enabled->value;
                return [
                    'option_name' => $optionStringForChannel,
                    'stock_count' => $available ? $item->inventorySet->available_stock_qty : 0,
                    'item_no' => $item->id
                ];
            });

        return $this;
    }

    /**
     * @param Product $product
     * @return $this
     * @throws Throwable
     */
    public function buildDisablePayload(Product $product): static
    {
        $this->payload = [];

        $this->payload['input']['created_from'] =
            ConfigurationValue::getValue('channel_connector_identifier_from_channel');

        $this->payload['input']['available'] =
            ChannelConnectorFacade::isSalesDisabled($product) ? 0 : 1;

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
            'OZ' => (float)number_format($weight * 0.0283495, 3),
            'LB' => (float)number_format($weight * 0.453592, 3),
            default => (float)$weight
        };
    }

    /**
     * Assuming that each cc-local category is connected with 2 channel-categories(Gender: 1, Category: 4)
     *
     * @param Category $localCategory
     * @return array|null
     */
    protected function getChannelCategoryFormat(Category $localCategory): array|null
    {
        $result = [
            'category_gender_id' => 0,
            'category_master_id' => 0,
            'category_slave_id' => 0,
            'category_slave_id2' => 0,
        ];

        if (!$localCategory->channelCategories || !count($localCategory->channelCategories)) {
            return null;
        }

        foreach ($localCategory->channelCategories as $channelCategory) {
            if ($channelCategory->category_fid === 1) {
                $result['category_gender_id'] = $channelCategory->code;
            } else {
                if ($channelCategory->is_last_category === 1) {
                    $result['category_slave_id2'] = $channelCategory->code;
                    $parentModel = ChannelCategory::whereCode($channelCategory->parent_code)->first();
                    $result['category_slave_id'] = $parentModel->code;
                    if ($parentModel->parent_code) {
                        $grandParentModel = ChannelCategory::whereCode($parentModel->parent_code)->first();
                        $result['category_master_id'] = $grandParentModel->code;
                    }
                } else {
                    // todo
                    // basically this is wrong, It should be last_category.
                }
            }
        }
        return $result;
    }
}
