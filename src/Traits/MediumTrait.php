<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use App\Models\Features\Medium;
use GraphQL\Mutation;

trait MediumTrait
{
    use ShopifyGraphiqlCommonTrait;

    public function buildCreatePayload(Medium $medium): static
    {
        $this->payload = ['input' => []];
        $id = $medium->product->override->id_from_remote;
        $this->payload['input']['id'] = $id;
        $this->payload['input']['images'][] = [
            'src' => $medium->{Medium::SRC}
        ];

        return $this;
    }

    protected static function typeMediumCreate(): Mutation
    {
        return self::typeProductAppendImages();
    }
}
