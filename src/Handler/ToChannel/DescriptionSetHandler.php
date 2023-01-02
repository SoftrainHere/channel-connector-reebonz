<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\ToChannel;

use App\Models\Features\DescriptionSet;
use Illuminate\Support\Carbon;
use Mxncommerce\ChannelConnector\Handler\ApiBase;
use Mxncommerce\ChannelConnector\Jobs\DescriptionSetUpsert;

class DescriptionSetHandler extends ApiBase
{
    /**
     * @param DescriptionSet $descriptionSet
     * @return bool
     */
    public function created(DescriptionSet $descriptionSet): bool
    {
        if($descriptionSet->product->descriptionSets->count() > 1) {
            DescriptionSetUpsert::dispatch($descriptionSet)->delay(Carbon::now()->addSeconds(60));
        }
        return true;
    }

    /**
     * @param DescriptionSet $descriptionSet
     * @return bool
     */
    public function updated(DescriptionSet $descriptionSet): bool
    {
        DescriptionSetUpsert::dispatch($descriptionSet)->delay(Carbon::now()->addSeconds(60));
        return true;
    }
}
