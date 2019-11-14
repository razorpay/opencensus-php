<?php

namespace RZP\Jobs;

use Throwable;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\FundAccount\Type;
use RZP\Exception\LogicException;
use RZP\Exception\RuntimeException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Processor\Vpa as VpaProcessor;

class FaVpaValidation extends Job
{
    /**
     * @var string
     */
    protected $favId;

    /**
     * Create a new job instance.
     *
     * @param string $mode
     * @param string $favId
     */
    public function __construct(string $mode, string $favId)
    {
        parent::__construct($mode);

        $this->favId = $favId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try
        {
            parent::handle();

            /** @var Entity $faValidation */
            $faValidation = $this->repoManager
                                 ->fund_account_validation
                                 ->findOrFail($this->favId);

            $vpaProcessor = new VpaProcessor($faValidation);

            $fundAccount = $faValidation->fundAccount;

            if ($fundAccount->getAccountType() !== Type::VPA)
            {
                throw new LogicException("Invalid fund account type");
            }

            // payment service requires merchant to be set
            app('basicauth')->setModeAndDbConnection($this->mode);
            app('basicauth')->setMerchantById($fundAccount->getMerchantId());

            $vpaInput = ['vpa' => $fundAccount->account->getAddress()];

            $data = $this->getVpaValidateResponse($vpaInput);

            $faValidation->setRegisteredName($data['name']);

            $vpaProcessor->markValidationAsCompleted($data['account_status']);
        }
        catch (RuntimeException $e) {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_VPA_VALIDATE_FAILED,
                [
                    'fa_validation_id' => $this->favId
                ]
            );
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_VPA_FAILED,
                [
                    'fa_validation_id' => $this->favId
                ]
            );
        }

        $this->delete();
    }

    /**
     * @param array $vpaInput
     *
     * @return array
     * @throws LogicException
     * @throws RuntimeException
     */
    protected function getVpaValidateResponse(array $vpaInput) : array
    {
        $data = [];

        try
        {
            $paymentService = new PaymentService();

            $response = $paymentService->validateVpa($vpaInput);

            if (($response === null) or
                ($response['customer_name'] === null) or
                ($response['success'] === null))
            {
                throw new LogicException("Mismatch in expected and returned array in vpa validate");
            }

            $data['account_status'] = $response['success'] === true ? AccountStatus::ACTIVE : AccountStatus::INVALID;

            $data['name'] = $response['customer_name'];
        }
        catch (GatewayErrorException $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_VPA_VALIDATE_TIMEOUT,
                [
                    'fa_validation_id' => $this->favId
                ]
            );

            $data['account_status'] = AccountStatus::INVALID;
        }

        return $data;
    }
}
