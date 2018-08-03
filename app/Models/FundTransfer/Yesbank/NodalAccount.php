<?php

namespace RZP\Models\FundTransfer\Yesbank;

use App;
use Config;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Attempt\Lock;
use RZP\Models\FundTransfer\Yesbank\Request\Transfer;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\StatusProcessor;

class NodalAccount extends NodalBase\NodalAccount
{
    protected $trace;

    protected $config;

    public function __construct(string $purpose = null)
    {
        parent::__construct($purpose);

        $this->initStats();
    }

    /**
     * Makes request to the bank for fund transfer for given attempts
     *
     * @param PublicCollection $attempts
     * @return array
     */
    public function process(PublicCollection $attempts): array
    {
        $transfer = new Transfer($this->purpose);

        $processedCount = 0;

        $lock = (new Lock($this->channel));

        $attempts = $lock->lockAttempts($attempts);

        foreach($attempts as $entity)
        {
            try
            {
                // Calling init will reset all the data of previous request
                $response = $transfer->init()
                                     ->setEntity($entity)
                                     ->makeRequest();

                $this->repo->saveOrFail($entity);

                $this->repo->saveOrFail($entity->source);

                $this->trackAttemptsInitiatedSuccess($this->channel, $this->purpose, $entity->getSourceType());
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_TRANSFER_REQUEST_FAILED,
                    [
                        'channel'       => $this->channel,
                        'entity_id'     => $entity->getId(),
                        'settlement_id' => $entity->getSourceId(),
                    ]);

                $lock->releaseAttempt($entity);

                $this->trackAttemptsInitiatedFailure($this->channel, $this->purpose, $entity->getSourceType());

                continue;
            }

            $processedCount++;

            try
            {
                (new StatusProcessor($response))->updateTransferStatus();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_TRANSFER_STATUS_UPDATE_FAILED,
                    $response
                );
            }
            finally
            {
                $lock->releaseAttempt($entity);
            }
        }

        $this->updateTransferStatus($processedCount);

        return $this->transferStatus;
    }
}
