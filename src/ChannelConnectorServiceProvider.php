<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Mxncommerce\ChannelConnector\Console\Commands\GetOrdersFromChannel;
use Mxncommerce\ChannelConnector\Console\Commands\InitializePackage;
use Mxncommerce\ChannelConnector\Console\Commands\SetupChannelResources;
use Mxncommerce\ChannelConnector\Helpers\ChannelConnectorHelper;

class ChannelConnectorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/channel_connector.php');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'mxncommerce.channel-connector');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mxncommerce.channel-connector');
        $this->registerCommands();
        $this->publishes([
            __DIR__ . '/../routes' => base_path('routes'),
            __DIR__ . '/../resources/lang' => resource_path('lang'),
            __DIR__ . '/../config/channel_connector_for_remote.php' => config_path('channel_connector_for_remote.php'),
        ]);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('command:get-orders')
                ->withoutOverlapping()
                ->runInBackground()
                ->everyFifteenMinutes()
            ;
        });
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/channel_connector_for_remote.php', 'channel_connector_for_remote'
        );
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InitializePackage::class,
                SetupChannelResources::class,
                GetOrdersFromChannel::class
            ]);
        }
        $this->app->singleton(ChannelConnectorHelper::class);
    }
}
