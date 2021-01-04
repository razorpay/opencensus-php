<?php

namespace RZP\Models\BankTransfer\HdfcEcms;

use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use RZP\Models\BankTransferRequest;
use RZP\Exception\BadRequestValidationFailureException;

class Service extends BankTransfer\Service
{
    private $bankTransferRequestCore;

    private $utility;

    public function __construct()
    {
        parent::__construct();

        $this->bankTransferRequestCore = new BankTransferRequest\Core();

        $this->utility = new Utility();
    }

    public function saveAndProcessRequest(array $requestPayload)
    {
        $bankTransferRequest = null;

        $entityInput = null;

        $response = null;

        try
        {
            $entityInput = $this->utility->modifyInputDataToEntity($requestPayload);

            $bankTransferRequest = $this->bankTransferRequestCore->create(
                $entityInput,
                $this->provider,
                $requestPayload
            );

            (new Validator())->validateRequestPayload($requestPayload);

            $serviceResponse = $this->processBankTransfer($bankTransferRequest);

            $response = $this->utility->getHdfcEcmsResponse($requestPayload, '', 0, $serviceResponse);
        }
        catch (BadRequestValidationFailureException $ex)
        {
            $this->bankTransferRequestCore
                ->updateBankTransferRequest($entityInput[BankTransfer\Entity::REQ_UTR], false, $ex->getMessage(), $bankTransferRequest);

            $this->trace->traceException($ex);

            $response = $this->utility->getHdfcEcmsResponse($requestPayload, $ex->getMessage(), 1, null);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            $response = $this->utility->getHdfcEcmsResponse($requestPayload, $ex->getMessage(), 2, null);
        }

        $this->trace->info(TraceCode::BANK_TRANSFER_CALLBACK_RESPONSE, $response);

        return $response;
    }

    public function processBankTransfer(BankTransferRequest\Entity $bankTransferRequest)
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_ECMS_PROCESS_REQUEST,
            $bankTransferRequest->toArrayTrace()
        );

        $this->provider = $bankTransferRequest->getGateway();

        $this->validateProvider($bankTransferRequest->getUtr());

        $this->checkBlocksAndUpdateRequest($bankTransferRequest);

        $response = (new Core())->processBankTransfer($bankTransferRequest);

        $statusCode = StatusCode::getStatusCodeForEcms($response);

        return [
            Entity::RESPONSE_STATUS => $statusCode,
            Entity::RESPONSE_REASON => $response,
            Entity::TRANSACTION_ID  => $bankTransferRequest->getUtr() ?? '',
        ];
    }

}
