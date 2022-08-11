<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler;

use App\Helpers\ChannelConnectorFacade;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SendDataToChannel
{
    private int $id;
    private static string $modelNamespaceRoot = '\App\Models\Features\\';
    private static string $mapperNamespaceRoot = '\Mxncommerce\ChannelConnector\Handler\ToChannel\\';
    private static string $syncModel;
    private static string $handler;
    private static string $method;

    /**
     * @param array $messageBody
     */
    public function setModel(array $messageBody): void
    {
        $this->id = $messageBody['record_id'];
        self::$method = Str::studly($messageBody['action_type']);
        self::$syncModel = self::$modelNamespaceRoot . Str::studly($messageBody['change_type']);
        self::$handler = self::$mapperNamespaceRoot . Str::studly($messageBody['change_type']) . 'Handler';
    }

    public function proceed(array $messageBody): bool
    {
        $this->setModel($messageBody);
        $model = app(self::$syncModel)::query()->find($this->id);
        if (!$model instanceof self::$syncModel) {
            ChannelConnectorFacade::moveExceptionToCentral(
                [trans('errors.no_model_exist', [
                    'id' => $this->id,
                    'class' => self::$syncModel,
                ])],
                Response::HTTP_NOT_FOUND,
            );
            return false;

        }

        if (class_exists(self::$handler)) {
            return app(self::$handler)->{self::$method}($model);
        } else {
            return true;
        }
    }
}
