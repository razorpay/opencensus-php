<?php

namespace RZP\Models\FundTransfer\Rbl;

use App;
use Config;

use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Rbl\Request\Transfer;
use RZP\Models\FundTransfer\Rbl\Request\Beneficiary;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Models\FundTransfer\Rbl\Reconciliation\StatusProcessor;

class NodalAccount extends NodalBase\NodalAccount
{
    protected $config;

    protected $transferStatus = [];

    public function __construct(string $purpose = null)
    {
        parent::__construct($purpose);

        $this->channel = Channel::RBL;

        $this->initStats();
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
                        'channel'    => $this->channel,
                        'entity_id'  => $entity->getId()
                    ]);

                $this->trackAttemptsInitiatedFailure($this->channel, $this->purpose, $entity->getSourceType());

                continue;
            }

            $processedCount++;

            (new StatusProcessor($response))->updateTransferStatus();
        }

        $this->updateTransferStatus($processedCount);

        return $this->transferStatus;
    }
}
