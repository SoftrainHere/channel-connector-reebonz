<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Console\Commands;

use App\Models\ChannelBrand;
use App\Models\ChannelCategory;
use Illuminate\Console\Command;
use Mxncommerce\ChannelConnector\Handler\ToChannel\ProductHandler;

class SetupChannelResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:setup-channel-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup channel resources';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if (!class_exists(ChannelBrand::class)) {
            return;
        }

        $brands = app(ProductHandler::class)->getChannelBrands();
        if(count($brands['data']['brands'])) {
            foreach ($brands['data']['brands'] as $channelBrand) {
                ChannelBrand::updateOrCreate(['id' => $channelBrand['brand_id']], [
                    'name' => $channelBrand['brand_name']
                ]);
            }
        }

        if (!class_exists(ChannelCategory::class)) {
            return;
        }
        $parent_id = 1;
        $categories = app(ProductHandler::class)->getChannelCategories($parent_id);
        if(count($categories['data']['categories'])) {
            foreach ($categories['data']['categories'] as $channelCategory) {
                ChannelCategory::updateOrCreate(['code' => $channelCategory['category_id']], [
                    'code' => $channelCategory['category_id'],
                    'category_fid' => $parent_id,
                    'category_name' => $channelCategory['category_name'],
                    'category_local_name' => $channelCategory['name_for_hangle'],
                ]);
            }
        }

        $parent_id = 4;
        $categories = app(ProductHandler::class)->getChannelCategories($parent_id);
        if(count($categories['data']['categories'])) {
            foreach ($categories['data']['categories'] as $channelCategory) {
                ChannelCategory::updateOrCreate(['code' => $channelCategory['category_id']], [
                    'code' => $channelCategory['category_id'],
                    'category_fid' => $parent_id,
                    'category_name' => $channelCategory['category_name'],
                    'category_local_name' => $channelCategory['name_for_hangle'],
                ]);

                if (!empty($channelCategory['sub_categories'])) {
                    foreach ($channelCategory['sub_categories'] as $channelSubCategory) {
                        ChannelCategory::updateOrCreate(['code' => $channelSubCategory['category_id']], [
                            'code' => $channelSubCategory['category_id'],
                            'category_fid' => $parent_id,
                            'parent_code' => $channelCategory['category_id'],
                            'category_name' => $channelSubCategory['category_name'],
                            'category_local_name' => $channelSubCategory['name_for_hangle'],
                        ]);

                        foreach ($channelSubCategory['sub_categories'] as $channelSubSubCategory) {
                            ChannelCategory::updateOrCreate(['code' => $channelSubSubCategory['category_id']], [
                                'code' => $channelSubSubCategory['category_id'],
                                'category_fid' => $parent_id,
                                'parent_code' => $channelSubCategory['category_id'],
                                'category_name' => $channelSubSubCategory['category_name'],
                                'category_local_name' => $channelSubSubCategory['name_for_hangle'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
