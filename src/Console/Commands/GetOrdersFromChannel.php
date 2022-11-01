<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Console\Commands;

use App\Models\Features\ConfigurationValue;
use App\Models\Features\Product;
use App\Models\Features\Variant;
use App\Models\Override;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Mxncommerce\ChannelConnector\Handler\FromChannel\OrderCreate;
use Mxncommerce\ChannelConnector\Handler\ToChannel\OrderHandler;
use Mxncommerce\ChannelConnector\Helpers\ChannelConnectorHelper;
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
        for ($current_page=1; $current_page <= $totalRequest; $current_page++) {
            $param = [
                'order_status' => 'completed',
                'delivery_status ' => 'ready',
                'ordered_at_start' => $ordered_at_start,
                'ordered_at_end' => $ordered_at_end,
                'current_page' => $current_page,
            ];
            $response = app(OrderHandler::class)->list($param);
            if (!count($response['data']['orders'])) {
                break;
            }

            foreach ($response['data']['orders'] as $order) {
                if (self::isDealable($order)) {
                    app(OrderCreate::class)($order);
                }
            }
        }

        echo 'Done schedule' . PHP_EOL;
    }

    private static function isDealable(array $order): bool
    {
        $channelOrderStatus = app(ChannelConnectorHelper::class)
            ->getChannelOrderStatus($order['order_status']);
        if ($channelOrderStatus && $channelOrderStatus['code'] !== 'completed') {
            return false;
        }
        $model = Override::whereIdFromRemote($order['product_id'])
            ->where('overridable_type', Product::class)->first();

        if ($model instanceof Override) {
            $variantModel = Override::whereIdFromRemote($order['stock_id'])
                ->where('overridable_type', Variant::class)->first();

            if ($variantModel instanceof Override) {
                return true;
            }
        }

        return false;
    }
}
