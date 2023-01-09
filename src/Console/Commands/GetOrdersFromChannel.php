<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Console\Commands;

use App\Enums\ProductSalesStatusType;
use App\Enums\ProductStatusType;
use App\Enums\VariantSalesStatusType;
use App\Enums\VariantStatusType;
use App\Libraries\Dynamo\SendExceptionToCentralLog;
use App\Models\Features\ConfigurationValue;
use App\Models\Features\Product;
use App\Models\Features\Variant;
use App\Models\Override;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Mxncommerce\ChannelConnector\Handler\FromChannel\OrderCreate;
use Mxncommerce\ChannelConnector\Handler\ToChannel\OrderHandler;
use Mxncommerce\ChannelConnector\Handler\ToChannel\OrderItemCancellationHandler;
use Mxncommerce\ChannelConnector\Helpers\ChannelConnectorHelper;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GetOrdersFromChannel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:get-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get orders from channel with their API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $today = Carbon::now();
        $ordered_at_end = $today->toDateString();
        $ordered_at_start = $today->subDay(1)->toDateString();

        $totalRequest = ConfigurationValue::getValue('total_page_number_to_request_channel_api') ?? 10;
        $totalCount = 0;
        $totalNotDealable = 0;
        for ($current_page=1; $current_page <= $totalRequest; $current_page++) {
            $param = [
                'order_status' => 'completed',
                'delivery_status ' => 'ready',
                'ordered_at_start' => $ordered_at_start,
                'ordered_at_end' => $ordered_at_end,
                'current_page' => $current_page,
            ];
            $response = app(OrderHandler::class)->list($param);
            $count = count($response['data']['orders']);
            $totalCount += $count;
            if (!$count) {
                break;
            }

            foreach ($response['data']['orders'] as $order) {
                if (self::isDealable($order)) {
                    echo 'Processing ' . $order['number'] . PHP_EOL;
                    app(OrderCreate::class)($order);
                } else {
                    $message = trans('mxncommerce.channel-connector::channel_connector.errors.order_not_dealable', [
                        'order_id_from_channel' => $order['number'] ?? 'NA',
                        'variant_overrided_id' => $order['stock_id'] ?? 'NA'
                    ]);
                    app(SendExceptionToCentralLog::class)(
                        [$message],
                        Response::HTTP_NOT_FOUND,
                    );
                    $totalNotDealable++;

                    // todo: request order-cancellation to channel
                    app(OrderItemCancellationHandler::class)->cancelBeforeSync($order['ordered_item_id']);
                }
            }
        }

        echo 'Got ' . $totalCount . ' orders ' . $totalNotDealable . ' not dealable from scheduler' . PHP_EOL;
    }

    public static function isDealable(array $order): bool
    {
        $channelOrderStatus = app(ChannelConnectorHelper::class)
            ->getChannelOrderStatus($order['order_status']);
        if ($channelOrderStatus && $channelOrderStatus['code'] !== 'completed') {
            return false;
        }
        $model = Override::whereIdFromRemote($order['product_id'])
            ->where('overridable_type', Product::class)->first();

        if (!$model instanceof Override) {
            $message = trans('mxncommerce.channel-connector::channel_connector.errors.product_not_connected', [
                'product_id_from_channel' => $order['product_id'] ?? 'NA',
            ]);
            app(SendExceptionToCentralLog::class)(
                [$message],
                Response::HTTP_NOT_FOUND,
            );
            return false;
        }

        if (
            $model->overridable?->status !== ProductStatusType::Active->value ||
            $model->overridable?->sales_status !== ProductSalesStatusType::Enabled->value
        ) {
            return false;
        }

        $productOverride = json_decode($model->fields_overrided ?? '{}');
        $statusProduct = $productOverride?->status ?? null;
        $salesStatusProduct = $productOverride?->sales_status ?? null;

        if (
            ($statusProduct && $statusProduct !== ProductStatusType::Active->value) ||
            ($salesStatusProduct && $salesStatusProduct !== ProductSalesStatusType::Enabled->value)
        ) {
            return false;
        }

        $variantModel = Override::whereIdFromRemote($order['stock_id'])
            ->where('overridable_type', Variant::class)->first();

        if (!$variantModel instanceof Override) {
            $message = trans('mxncommerce.channel-connector::channel_connector.errors.variant_not_connected', [
                'variant_id_from_channel' => $order['stock_id'] ?? 'NA',
            ]);
            app(SendExceptionToCentralLog::class)(
                [$message],
                Response::HTTP_NOT_FOUND,
            );
            return false;
        }

        if (
            $variantModel->overridable?->status !== VariantStatusType::Active->value ||
            $variantModel->overridable?->sales_status !== VariantSalesStatusType::Enabled->value
        ) {
            return false;
        }

        $variantOverride = json_decode($variantModel->fields_overrided ?? '{}');
        $statusVariant = $variantOverride?->status ?? null;
        $salesStatusVariant = $variantOverride?->sales_status ?? null;

        if (
            ($statusVariant && $statusVariant !== VariantStatusType::Active->value) ||
            ($salesStatusVariant && $salesStatusVariant !== VariantSalesStatusType::Enabled->value)
        ) {
            return false;
        }

        return true;
    }
}
