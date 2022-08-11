<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Console\Commands;

use Illuminate\Console\Command;

class SetupChannelResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:setup-channel-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup channel resources';

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
        // Initial setup for channel resources
    }
}
