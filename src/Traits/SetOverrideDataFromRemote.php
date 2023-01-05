<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Models\BaseModel;
use App\Models\Features\Variant;
use App\Models\Override;
use Illuminate\Database\Eloquent\Model;

trait SetOverrideDataFromRemote
{
    public function setOverrideDataFromRemote(Model $model, array $payloadFromRemote): void
    {
        $metaBody = $payloadFromRemote[key($payloadFromRemote)];

        if ($model->{BaseModel::OVERRIDE} instanceof Override) {
            if (!empty($model->{BaseModel::OVERRIDE})) {
                $model->{BaseModel::OVERRIDE}->{Override::ID_FROM_REMOTE} = $metaBody['id'];
                $model->{BaseModel::OVERRIDE}->{Override::META_ID_FROM_REMOTE} = json_encode($metaBody);
                $model->{BaseModel::OVERRIDE}->save();
            }
        } else {
            $override = new Override();
            $override->overridable()->associate($model);
            $override->id_from_remote = empty($metaBody['id']) ? null : $metaBody['id'] ;
            $override->meta_id_from_remote = json_encode($metaBody) ?? null;
            $override->save();
        }
    }

    public function updateOverrideDataFromRemote(Model $model, array $payloadFromRemote): bool
    {
        if (
            empty($payloadFromRemote['data']) ||
            empty($payloadFromRemote['data']['product']) ||
            empty($payloadFromRemote['data']['product']['stocks'])
        ) {
            return false;
        }

        foreach ($payloadFromRemote['data']['product']['stocks'] as $stock) {
            if (!empty($stock['item_no'])) {
                $override = Override::whereOverridableId($stock['item_no'])
                    ->where('overridable_type', Variant::class)->first();
                if ($override?->overridable instanceof Variant) {
                    $override->id_from_remote = $stock['id'];
                    $override->save();
                }
            }
        }
        return true;
    }
}
