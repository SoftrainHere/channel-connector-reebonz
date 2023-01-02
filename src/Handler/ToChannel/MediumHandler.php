<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\Medium;
use Illuminate\Support\Carbon;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Jobs\MediumUpdate;

class MediumHandler extends ApiBase
{
    /**
     * @param Medium $medium
     * @return bool
     */
    public function created(Medium $medium): bool
    {
        if($medium->product->media->count() > 0) {
            MediumUpdate::dispatch($medium)->delay(Carbon::now()->addSeconds(60));
        }
        return true;
    }

    /**
     * @param Medium $medium
     * @return bool
     */
    public function updated(Medium $medium): bool
    {
        MediumUpdate::dispatch($medium)->delay(Carbon::now()->addSeconds(60));
        return true;
    }
}
