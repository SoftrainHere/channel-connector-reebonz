<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Tests;

use App\Models\Features\Product;
use Mxncommerce\ChannelConnector\Handler\ToChannel\ProductHandler;
use Tests\TestCase;

class ReebonzTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @throws \Throwable
     */
    public function test_product_created_test()
    {
//        $res = app(ProductHandler::class)->created(Product::find(9));
        $this->assertTrue(true);
    }

    public function test_product_updated_test()
    {
//        app(ProductHandler::class)->updated(Product::find(11));
        $this->assertTrue(true);
    }

    public function test_price_set_created_test()
    {
//        app(PriceSetHandler::class)->created(PriceSet::find(17));
        $this->assertTrue(true);
    }

    public function test_inventory_set_created_test()
    {
//        app(InventorySetHandler::class)->created(InventorySet::find(17));
        $this->assertTrue(true);
    }

    public function test_check_product_from_europe_test()
    {
//        $code = app(ChannelConnectorFacade::class)::checkProductFromEurope('LU');
        $this->assertTrue(true);
    }
}
