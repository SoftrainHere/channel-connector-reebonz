<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler\FromChannel;

use App\Helpers\ChannelConnectorFacade;
use Exception;
use GraphQL\Exception\QueryError;

class OrdersDelete
{
    public function __invoke(array $payload): bool
    {
        try {
            ChannelConnectorFacade::echoDev('OrdersDelete');
        } catch (QueryError $exception) {
            // todo : retry
            dd($exception);
        } catch (Exception $exception) {
            // todo : do something
            dd($exception);
        }

        return true;
    }
}
