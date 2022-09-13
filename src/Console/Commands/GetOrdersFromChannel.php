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
     * @throws \Throwable
     */
    public function handle(): void
    {
        // todo: considering this to be Queue/Job
        $today = Carbon::now();
        $ordered_at_end = $today->toDateString();
        $ordered_at_start = $today->subDay(30)->toDateString();

        $order_status = 'completed';
//        dd($ordered_at_start, $ordered_at_end);

        $totalRequest = ConfigurationValue::getValue('total_page_number_to_request_channel_api') ?? 10;
        $ind = 1;
        for ($current_page=1; $current_page <= $totalRequest; $current_page++) {
            $param = [
                'ordered_at_start' => $ordered_at_start,
                'ordered_at_end' => $ordered_at_end,
                'current_page' => $current_page,
            ];
            $response = app(OrderHandler::class)->list($param);

            if (!count($response['data']['orders'])) {
                break;
            }

            foreach ($response['data']['orders'] as $order) {

                if (self::isDealable($order['product_id'], $order['stock_id'])) {
                    //                    echo $current_page . '  ' . $ind . '. ' . $order['number'] . ' ' . $order['product_id'] .  PHP_EOL;
                    print_r($order);
                    $ind++;
                    app(OrderCreate::class)($order);
                }
            }
        }

        echo 'Done schedule' . PHP_EOL;
    }

    private static function isDealable($productId, $stockId): bool
    {
        $model = Override::whereIdFromRemote($productId)
            ->where('overridable_type', Product::class)->first();

        if ($model instanceof Override) {
            $variantModel = Override::whereIdFromRemote($stockId)
                ->where('overridable_type', Variant::class)->first();

            if ($variantModel instanceof Override) {
                return true;
            }
        }

        return false;
    }
}
