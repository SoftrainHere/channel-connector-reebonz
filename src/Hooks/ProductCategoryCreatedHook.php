<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Hooks;

use App\Helpers\ChannelConnectorFacade;
use App\Jobs\Features\SendModelChangeToRemote;
use App\Libraries\CustomException\CustomExceptionInterface;
use App\Models\Features\Category;
use App\Models\Features\Product;
use App\Models\Pivot\ProductOverridedCategory;
use Symfony\Component\HttpFoundation\Response;

class ProductCategoryCreatedHook {

    /**
     * @param ProductOverridedCategory $productOverridedCategory
     * @return void
     */
    public function __invoke(ProductOverridedCategory $productOverridedCategory): void
    {
        $product = Product::find($productOverridedCategory->product_id);
        if (ChannelConnectorFacade::isSalesDisabled($product)) {
            return;
        }

        if (!$product instanceof Product) {
            $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.no_product_found';
            $error = trans($error_namespace, [
                'product_id' => $productOverridedCategory->product_id,
            ]);

            app(CustomExceptionInterface::class)->handleException(
                [$error],
                Response::HTTP_NOT_FOUND
            );
            return;
        }

        $category = Category::find($productOverridedCategory->category_id);
        if (!$category instanceof Category) {
            $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.no_category_found';
            $error = trans($error_namespace, [
                'category_id' => $productOverridedCategory->category_id,
            ]);

            app(CustomExceptionInterface::class)->handleException(
                [$error],
                Response::HTTP_NOT_FOUND
            );
            return;
        }

        if (!count($category->channelCategories)) {
            $error_namespace = 'mxncommerce.channel-connector::channel_connector.errors.no_category_id_mapped_exist';
            $error = trans($error_namespace, [
                'category_id' => $productOverridedCategory->category_id,
                'product_id' => $productOverridedCategory->product_id
            ]);

            app(CustomExceptionInterface::class)->handleException(
                [$error],
                Response::HTTP_NOT_FOUND
            );
            return;
        }

        SendModelChangeToRemote::dispatch('Product', 'created', $product);
    }
}
