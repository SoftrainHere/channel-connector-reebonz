<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Traits;

use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\Variable;

trait OrderSetTrait
{
    /**
     * @param int $id
     * @return Query
     */
    protected static function typeOrderSet(int $id): Query
    {
        return (new Query('order'))
            ->setArguments(['id' => $id])
            ->setSelectionSet([
                'id',
                'description',
                'vendor',
                'title',
                'status',
                'productType',
                (new Query('images'))
                ->setArguments(['first' => 100])
                ->setSelectionSet([
                    (new Query('edges'))
                    ->setSelectionSet([
                        (new Query('node'))->setSelectionSet(['id', 'originalSrc'])
                    ])
                ])
                ,
                (new Query('metafield'))
                ->setArguments([
                    'key' => config('channel_connector_for_remote.metafield_key_name'),
                    'namespace' => config('channel_connector.channel_identifier'),
                ])
                ->setSelectionSet(['key','value'])
                ,
                (new Query('variants'))
                    ->setArguments(['first' => 100])
                    ->setSelectionSet([
                        (new Query('edges'))
                        ->setSelectionSet([
                            (new Query('node'))->setSelectionSet(['id', 'title', 'sku'])
                        ])
                    ])
            ]);
    }

    protected static function typeDescriptionSetCreate(): Mutation
    {
        return (new Mutation('descriptionSetCreate'))
            ->setVariables([new Variable('input', 'ProductInput', true)])
            ->setArguments(['input' => '$input'])
            ->setSelectionSet([
                (new Query('product'))
                ->setSelectionSet(
                    [
                        'id',
                        (new Query('variants'))->setArguments(['first' => 100])
                        ->setSelectionSet([
                            (new Query('edges'))
                            ->setSelectionSet([
                                (new Query('node'))->setSelectionSet(['id', 'title'])
                            ])
                        ])
                    ]
                )
            ]);
    }
}
