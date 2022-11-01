<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Handler;

use App\Exceptions\Api\SaveToCentralException;
use App\GraphQL\Mutations\Configuration\ConfigurationValueMutator;
use App\Libraries\CustomException\CustomExceptionInterface;
use App\Models\Features\ConfigurationValue;
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
     * @param string $mutationType
     * @return JsonResponse
     * @throws SaveToCentralException
     */
    protected function requestMutation(string $endpoint, string $mutationType = 'post'): array|null
    {
        if (!$endpoint) {
            throw new SaveToCentralException(
                __('mxncommerce.channel-connector::channel_connector.errors.no_api_endpoint'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $response = Http::acceptJson()
            ->withToken(ConfigurationValue::getValue('channel_oauth_token'))
            ->{$mutationType}($endpoint, $this->payload['input'] ?? []);

        if ($response->status() === Response::HTTP_UNAUTHORIZED) {
            if(self::renewChannelToken()) {
                $this->requestMutation($endpoint, $mutationType);
            }
        }

        return $response->json();
    }

    public static function getFullChannelApiEndpoint(string $localPoint, array $payload = []): string
    {
        return config('channel_connector_for_remote.api_market_root')
            . trans('mxncommerce.channel-connector::channel_connector.api.' . $localPoint, $payload, 'en');
    }

    public static function getRenewTokenEndpoint(string $localPoint): string
    {
        return config('channel_connector_for_remote.api_root')
            . trans('mxncommerce.channel-connector::channel_connector.api.' . $localPoint, [], 'en');
    }

    public function getChannelBrands(string $searchKeyword = null): array
    {
        $url = self::getFullChannelApiEndpoint('get.brands');
        $response = Http::acceptJson()
            ->withToken(ConfigurationValue::getValue('channel_oauth_token'))
            ->get($url);

        if ($response->status() === Response::HTTP_UNAUTHORIZED) {
            if(self::renewChannelToken()) {
                $this->getChannelBrands($searchKeyword);
            }
        }

        return $response->json();
    }

    public function getChannelCategories($parentId = 1, bool $newCategory = true): array
    {
        $url = self::getFullChannelApiEndpoint('get.categories')
            . '?new_category=' . $newCategory
            . '&parent_id=' . $parentId;
        $response = Http::acceptJson()
            ->withToken(ConfigurationValue::getValue('channel_oauth_token'))
            ->get($url);

        if ($response->status() === Response::HTTP_UNAUTHORIZED) {
            if(self::renewChannelToken()) {
                ConfigurationValue::flushQueryCache();// otherwise use dont cache
                return $this->getChannelCategories($newCategory, $parentId);
            }
        }

        return $response->json();
    }

    public static function renewChannelToken(): bool
    {
        $url = self::getRenewTokenEndpoint('post.token');
        $account = config('channel_connector_for_remote.login_credential');
        $response = Http::post($url, $account);

        if ($response->status() !== Response::HTTP_OK) {
            $message = trans(
                'mxncommerce.channel-connector::channel_connector.errors.wrong_credential_for_channel'
            );
            app(CustomExceptionInterface::class)->handleException(
                [ $message ],
                Response::HTTP_UNAUTHORIZED
            );
            return false;
        }

        app(ConfigurationValueMutator::class)->upsert('', ['input' => [
            'code' => 'channel_oauth_token',
            'value' => $response->json()['access_token']
        ]]);

        return true;
    }
}
