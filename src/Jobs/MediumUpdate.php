<?php declare(strict_types=1);

namespace Mxncommerce\ChannelConnector\Jobs;

use App\Jobs\Features\SendModelChangeToRemote;
use App\Models\Features\Medium;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MediumUpdate implements ShouldQueue, ShouldBeUnique
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

    private Medium $medium;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 50;

    public function uniqueId(): string
    {
        return  'ProductResend' . $this->medium->product->id;
    }

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Medium $medium)
    {
        $this->onQueue(config('queue.connections.database.queue_to_remote'));
        $this->medium = $medium;
    }

    public function handle(): void
    {
        SendModelChangeToRemote::dispatch('Product', 'updated', $this->medium->product);
    }
}
