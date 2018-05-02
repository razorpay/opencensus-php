<?php

namespace RZP\Models\FundTransfer\Rbl;

use App;
use Config;

use RZP\Models\FundTransfer\Rbl\Request\Beneficiary;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Rbl\Request\Transfer;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Models\FundTransfer\Rbl\Reconciliation\ResponseProcessor;

class NodalAccount extends NodalBase\NodalAccount
{
    protected $trace;

    protected $config;

    protected $transferStatus = [];

    public function __construct()
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

        $reconciler = new ResponseProcessor();

        $this->updateAttemptStatus($attempts);

        foreach($attempts as $entity)
        {
            try {
                $response = $transfer->setEntity($entity)
                                     ->makeRequest();

                $this->repo->saveOrFail($entity);

                $this->repo->saveOrFail($entity->source);
            }
            catch (\Exception $e)
            {
                $this->trace->info(
                    TraceCode::RBL_NODAL_TRANSFER_REQUEST_FAILED,
                    [
                        'entity_id'  => $entity->getId()
                    ]);

                continue;
            }

            $reconciler->reconcile($response, $transfer->getMode());

            $status = $this->isValidSuccessResponse($response);

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

    /**
     * Validates if the current request was executed successfully or not
     *
     * @param array $response
     *
     * @return bool
     */
    protected function isValidSuccessResponse(array $response): bool
    {
        $responseBody = $response['Single_Payment_Corp_Resp'];

        if ((isset($responseBody['Header']['Status']) === false) or
            ($responseBody['Header']['Status'] === Status::FAILURE))
        {
            $this->trace->error(TraceCode::RBL_NODAL_FAILURE_RESPONSE, $response);

            return false;
        }

        return true;
    }
}
