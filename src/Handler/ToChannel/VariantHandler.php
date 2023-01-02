<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\Variant;
use Illuminate\Support\Carbon;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Jobs\VariantCreate;

class VariantHandler extends ApiBase
{
    /**
     * @param Variant $variant
     * @return bool
     */
    public function updated(Variant $variant): bool
    {
        VariantCreate::dispatch($variant)->delay(Carbon::now()->addSeconds(60));
        return true;
    }

    public function created(Variant $variant): bool
    {
        VariantCreate::dispatch($variant)->delay(Carbon::now()->addSeconds(60));
        return true;
    }
}
