<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\Variant;
use Illuminate\Support\Carbon;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Jobs\MediumUpdate;
use Mxncommerce\ChannelConnector\Jobs\VariantCreate;
use Throwable;

class VariantHandler extends ApiBase
{
    /**
     * @throws \App\Exceptions\Api\SaveToCentralException
     * @throws Throwable
     */
    public function updated(Variant $variant): bool
    {
        return app(ProductHandler::class)->updated($variant->product);
    }

    public function created(Variant $variant): void
    {
        $dateNow = Carbon::now();
        VariantCreate::dispatch($variant)->delay($dateNow->addSeconds(60));
    }
}
