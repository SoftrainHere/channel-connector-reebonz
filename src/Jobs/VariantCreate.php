<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Jobs\Features\SendModelChangeToRemote;
use App\Models\Features\Variant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * We don't know the order fulfillment information
 * at the time the order is created from channel.
 * So to get that information, we need extra api request
 */
class VariantCreate implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /*
    |--------------------------------------
    | Job configurations
    |--------------------------------------
    |
    | https://laravel.com/docs/9.x/queues
    |
    */
    public bool $failOnTimeout = true;
    public int $maxExceptions = 3;
    public int $timeout = 60;
    public int $tries = 3;
    public int $backoff = 10;

    private Variant $variant;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 90;

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return 'ProductResend' . $this->variant->product->id;
    }

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Variant $variant)
    {
        $this->onQueue(config('queue.connections.database.queue_to_remote'));
        $this->variant = $variant;
    }

    public function handle(): void
    {
        SendModelChangeToRemote::dispatch('Product', 'updated', $this->variant->product);
    }
}
