<?php

namespace RZP\Jobs;

use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\UpdateMailingListByChunk;

class CreateMailingList extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const LIMIT = 500;

    public $timeout = 3600;

    protected $queueConfigKey = 'mailing_list_update';

    /**
     * @param string|void $mode
     */
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $repo = new Merchant\Repository;

            $total = $repo->getLiveMerchantCount();

            $noOfChunks = ceil($total / self::LIMIT);

            $this->trace->info(TraceCode::CREATE_MAILING_LIST_INITIATED, [
                'no_of_chunks'   =>  $noOfChunks,
            ]);

            for ($i = 0; $i <= $noOfChunks; $i++)
            {

                $offset = $i  * self::LIMIT;

                $merchantIdsInChunk = $repo->fetchMerchantIdsInChunk($offset, self::LIMIT);

                UpdateMailingListByChunk::dispatch($this->mode, $merchantIdsInChunk);

            }
        }
        catch (\Throwable $e)
        {
            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $this->delete();
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAILING_LIST_CREATE_ERROR
            );
        }
    }
}
