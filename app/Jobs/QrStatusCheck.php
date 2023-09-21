<?php

namespace RZP\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;

use RZP\Trace\TraceCode;

/**
 * QrStatusCheck class is the handler for all QrStatusCheck jobs.
 * Queue name- {env}-api-qr-status-check-live
 * NOTE- There is no test mode queue for this on production. But, we allow test mode queue
 * on stage and QA to enable easier testing.
 *
 * This queue implements the ShouldBeUnique interface.
 * This means, Laravel ideally won't allow the dispatch of 2 messages with the same QR Code ID in the queue.
 * This is handled via a Redis-based mutex-like implementation.
 * For more info, check- https://laravel.com/docs/9.x/queues#unique-jobs
 */
class QrStatusCheck extends Job implements ShouldBeUnique
{
    protected $queueConfigKey = 'qr_status_check';

    protected $qrCodeId;

    protected $metricsEnabled = true;

    /**
     * @var int Each job will remain unique for 3 mins, i.e. 180secs
     *
     * After this, the redis key used to maintain the lock will expire.
     */
    public int $uniqueFor = 180;

    /**
     * @return string Returns the unique ID, i.e. the QR code ID, to be used to take locks and ensure that only unique
     * messages are present in the queue.
     */
    public function uniqueId()
    {
        return $this->qrCodeId;
    }

    public function __construct(string $mode, string $id)
    {
        // QR Code ID
        $this->qrCodeId = $id;

        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();

        // TODO: This handler does nothing for now. This should change once the complete handler logic is written.
        $this->trace->info(TraceCode::QR_STATUS_CHECK_HANDLER_INIT, ['id' => $this->qrCodeId]);
    }
}
