<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Console\Commands;

use App\Models\ChannelBrand;
use App\Models\ChannelCategory;
use App\Models\Features\ConfigurationValue;
use Illuminate\Console\Command;
use Mxncommerce\ChannelConnector\Handler\ToChannel\ProductHandler;

class InitializePackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:init-package';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize this package(setup configuration and etc)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        ConfigurationValue::setValue([
            'code' => "channel_connector_identifier_from_channel",
            'value' => config('channel_connector_for_remote.channel_connector_identifier'),
            "value_type" => "STRING",
            "comment" => "identifier for channel's api(might be used as a unique api-identifier)"
        ]);
    }
}
