<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Jobs\Features\SendModelChangeToRemote;
use App\Models\Features\Variant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

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
    public int $uniqueFor = 300;

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'ProductResend' . $this->variant->product->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->variant->product->id))->dontRelease()];
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
