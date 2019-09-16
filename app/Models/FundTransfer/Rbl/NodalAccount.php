<?php

namespace RZP\Models\FundTransfer\Rbl;

use App;
use Config;
use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Rbl\Request\Transfer;
use RZP\Models\FundTransfer\Rbl\Request\Beneficiary;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Models\FundTransfer\Rbl\Reconciliation\StatusProcessor;

class NodalAccount extends NodalBase\NodalAccount
{
    const IFSC_IDENTIFIER = IFSC::RATN;

    protected $config;

    protected $transferStatus = [];

    public function __construct(string $purpose = null)
    {
        parent::__construct($purpose);

        $this->channel = Channel::RBL;

        $this->initStats();

        $this->bankingEndTimeRtgs = Carbon::createFromTime(
                                                self::RTGS_CUTOFF_HOUR_MAX,
                                                self::RTGS_CUTOFF_MINUTE_MAX,
                                                0,
                                                Timezone::IST)
                                            ->getTimestamp();
    }

    public function addBeneficiary(array $input): array
    {
        $beneficiary   = new Beneficiary();

        $responseArray = $beneficiary->setInput($input)
                                     ->makeRequest();

        return $responseArray;
    }

    public function process(PublicCollection $attempts): array
    {
        $transfer = new Transfer($this->purpose);

        $processedCount = 0;

        foreach($attempts as $entity)
        {
            try
            {
                // Calling init will reset all the data of previous request
                $response = $transfer->init()
                                     ->setEntity($entity)
                                     ->makeRequest();

                $this->repo->saveOrFail($entity);

                $this->postFtaInitiateProcess($entity);

                $this->trackAttemptsInitiatedSuccess($this->channel, $this->purpose, $entity->getSourceType());
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_TRANSFER_REQUEST_FAILED,
                    [
                        'channel'    => $this->channel,
                        'entity_id'  => $entity->getId()
                    ]);

                $this->trackAttemptsInitiatedFailure($this->channel, $this->purpose, $entity->getSourceType());

                continue;
            }

            $processedCount++;

            (new StatusProcessor($response))->updateTransferStatus();

            // The reason we are not dispatching bulk recon job here is because
            // that is required only for VPA since we get the final status
            // in sync as part of the initiate request itself.
            // Hence, we are doing this only for Yesbank now (for VPA only).
        }

        $this->updateTransferStatus($processedCount);

        return $this->transferStatus;
    }
}
