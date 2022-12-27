<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\Medium;
use App\Models\Features\Product;
use Illuminate\Support\Carbon;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Jobs\MediumUpdate;
use Throwable;

class MediumHandler extends ApiBase
{
    /**
     * @param Product $product
     * @return bool
     * @throws Throwable
     * @throws \App\Exceptions\Api\SaveToCentralException
     */
    public function created(Medium $medium): bool
    {
        if($medium->product->media->count() > 0) {
            return app(ProductHandler::class)->updated($medium->product);
        }
        return true;
    }

    /**
     * @param Product $product
     * @return bool
     * @throws Throwable
     * @throws \App\Exceptions\Api\SaveToCentralException
     */
    public function updated(Medium $medium): void
    {
        $dateNow = Carbon::now();
        MediumUpdate::dispatch($medium)
            ->delay($dateNow->addSeconds(60));
    }
}
