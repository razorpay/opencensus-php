<?php

namespace RZP\Models\BankTransfer\HdfcEcms;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\BankTransfer;
use RZP\Models\VirtualAccount;
use RZP\Models\BankTransferRequest;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\BadRequestValidationFailureException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;

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

            $auth = $this->auth->getInternalApp();

            if($auth === 'hdfc_ecms')
            {
                $bankCode = VirtualAccount\Provider::getBankCode($this->provider);

                $bankAccount = $this->repo
                    ->bank_account
                    ->findVirtualBankAccountByAccountNumberAndBankCode($requestPayload['Virtual_Account_No'], $bankCode, true);

                if ($bankAccount !== null)
                {
                    $orgId = (new Merchant\Repository)->getMerchantOrg($bankAccount->getMerchantId());

                    $experimentName = BankTransferConstants::HDFC_ECMS_FUND_TRANS_EXPERIMENT_ID;

                    $splitzResult = $this->evaluateHdfcEcmsFundTransExperiment($orgId, $experimentName);

                    $mode = strtolower($requestPayload[Entity::TYPE]);

                    if ($mode === BankTransfer\Mode::FUND_TRANS and (isset($splitzResult) === true) and (strtolower($splitzResult) === 'enable'))
                    {
                        $entityInput[BankTransfer\Entity::MODE] = BankTransfer\Mode::FT;
                        $requestPayload[Entity::TYPE] = BankTransfer\Mode::FT;
                    }
                }
            }

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
    public function evaluateHdfcEcmsFundTransExperiment(string $orgId, string $experimentName)
    {
        $experimentId = $this->app['config']->get('app.'.$experimentName);

        $response = $this->app['splitzService']->evaluateRequest([
            'id'            => $orgId,
            'experiment_id' => $experimentId,
        ]);

        if (!empty($response['response']['variant']) && isset($response['response']['variant']['name'])) {
            return $response['response']['variant']['name'];
        }

        return '';
    }

}
