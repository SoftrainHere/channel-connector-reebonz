<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler;

use App\Exceptions\Api\SaveToCentralException;
use App\Helpers\ChannelConnectorFacade;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PrepareDataFromRemoteEvent
{
    private static string $mapperNamespaceRoot = '\Mxncommerce\ChannelConnector\Handler\FromChannel\\';

    /**
     * @param array $param
     * @throws SaveToCentralException
     */
    public function next(array $param): void
    {
        $className = ChannelConnectorFacade::makeClassNameWithEvent($param);
        $class = self::$mapperNamespaceRoot . $className;
        if (!class_exists($class)) {
            throw new SaveToCentralException(
                trans(
                    'mxncommerce.channel-connector::channel_connector.no_handler_for_remote',
                    ['class' => $class]
                ),
                Response::HTTP_NOT_IMPLEMENTED
            );
        }

        try {
            $result = app($class)($param['payload']);
            $msg = $param["change_type"] . ' ' . $param["action_type"];
            if (!$result) {
                $msg .= ' passed';
            }
            ChannelConnectorFacade::echoDev($msg);

        } catch (Throwable $exception) {
            ChannelConnectorFacade::moveExceptionToCentral($exception);
        }
    }
}
