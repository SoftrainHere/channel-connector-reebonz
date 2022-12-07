<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Validators;

use App\Exceptions\Api\GraphQLException;
use App\Models\Features\Product;

class ProductPayloadValidation
{
    /**
     * @param Product $product
     * @return void
     * @throws GraphQLException
     */
    public function __invoke(Product $product): void
    {
//        $message = trans('errors.resend_validation_failed_reason', [
//            'reason' => 'Some reason',
//            'id' => $product->id,
//        ]);
//        throw new GraphQLException('Validation for resend failed', $message, 'validation');
    }
}
