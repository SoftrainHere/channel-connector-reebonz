<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler;

use App\Exceptions\Api\SaveToCentralException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiBase
{
    public array $payload = [];
    protected string $apiRoot;

    public function __construct()
    {
        $this->apiRoot = config('channel_connector_for_remote.api_root');
    }

    protected function requestQuery(): array|object
    {
        // do some api call
        return [];
    }

    /**
     * @param string $endpoint
     * @return JsonResponse
     * @throws SaveToCentralException
     */
    protected function requestMutation(string $endpoint): JsonResponse
    {
        if (!$endpoint) {
            throw new SaveToCentralException(
                __('mxncommerce.channel-connector::channel_connector.errors.no_api_endpoint'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $payload = [
            'data' => json_encode($this->payload['input'])
        ];

        $response = Http::asForm()->post($this->apiRoot . $endpoint, $payload);
        return response()->json($response->body());
    }

    private static function getApiPassword()
    {
        // do some cache job. if it needs
        return config('channel_connector_for_remote.api_password');
    }
}
