<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Models\BaseModel;
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
            $override->id_from_remote = $metaBody['id'];
            $override->meta_id_from_remote = json_encode($metaBody) ?? null;
            $override->save();
        }
    }
}
