<?php

namespace RZP\Models\FundTransfer\Rbl;

use App;
use Config;

use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Rbl\Request\Transfer;
use RZP\Models\FundTransfer\Rbl\Request\Beneficiary;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Models\FundTransfer\Rbl\Reconciliation\StatusProcessor;

class NodalAccount extends NodalBase\NodalAccount
{
    protected $trace;

    protected $config;

    protected $transferStatus = [];

    public function __construct(string $purpose)
    {
        parent::__construct();

        $this->trace = App::getFacadeRoot()['trace'];

        $this->initStats();
    }

    public function addBeneficiary(array $input): array
    {
        $beneficiary   = new Beneficiary();

        $responseArray = $beneficiary->setInput($input)
                                     ->makeRequest();

        return $responseArray;
    }

    public function initiateTransfer(PublicCollection $attempts): array
    {
        $transfer   = new Transfer();

        $this->updateAttemptStatus($attempts);

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
            }
            catch (\Throwable $e)
            {
                $this->trace->info(
                    TraceCode::NODAL_TRANSFER_REQUEST_FAILED,
                    [
                        'channel'    => $this->channel,
                        'entity_id'  => $entity->getId()
                    ]);

                continue;
            }

            (new StatusProcessor($response))->updateTransferStatus();

            $status = $transfer->isValidSuccessResponse();

            $this->updateTransferStatus($status);
        }

        return $this->transferStatus;
    }

    protected function initStats()
    {
        $this->transferStatus = [
            strtolower(Status::SUCCESS)  => 0,
            strtolower(Status::FAILURE)  => 0
        ];
    }

    protected function updateTransferStatus(bool $status)
    {
        $key = strtolower(($status === true) ? Status::SUCCESS : Status::FAILURE);

        $this->transferStatus[$key]++;
    }
}
