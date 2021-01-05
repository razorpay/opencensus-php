<?php

namespace RZP\Jobs;

use Throwable;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger;
use RZP\Models\FundAccount\Type;
use RZP\Exception\LogicException;
use RZP\Exception\RuntimeException;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Status;
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

            $this->trace->info(
                TraceCode::VPA_VALIDATION_REQUEST_TO_PAYMENTS_SERVICE,
                [
                    'account_type' => $fundAccount->getAccountType(),
                    'id'           => $fundAccount->getId()
                ]
            );

            if ($fundAccount->getAccountType() !== Type::VPA)
            {
                throw new LogicException("Invalid fund account type");
            }

            $vpaInput = [
                'vpa'         => $fundAccount->account->getAddress(),
                'merchant_id' => $fundAccount->getMerchantId(),
            ];

            $this->trace->info(
                TraceCode::VPA_VALIDATION_REQUEST_TO_PAYMENTS_SERVICE,
                $vpaInput
            );

            $data = $this->getVpaValidateResponse($vpaInput);

            if ((array_key_exists('fav_status', $data)) and
                ($data['fav_status'] === Status::COMPLETED))
            {
                $faValidation->setRegisteredName($data['name']);

                $accountStatus = array_key_exists('account_status', $data) ? $data['account_status'] : null;

                $name = array_key_exists('name', $data) ? $data['name'] : null;

                $success = array_key_exists('success', $data) ? $data['success'] : null;

                $traceable = [
                    'account_status'   =>  $accountStatus,
                    'customer_name'    =>  $name,
                    'fav_status'       =>  $data['fav_status'],
                    'id'               =>  $faValidation->getId(),
                    'success'          =>  $success
                ];

                $this->trace->info(
                    TraceCode::VPA_VALIDATION_FINAL_RESPONSE,
                    $traceable
                );

                $vpaProcessor->markValidationAsCompleted($data['account_status']);

            }
            else
            {
                $traceable = [
                    'fav_status'  =>  $data['fav_status'],
                    'id'          =>  $faValidation->getId()
                ];

                $this->trace->info(
                    TraceCode::VPA_VALIDATION_FINAL_RESPONSE,
                    $traceable
                );

                $vpaProcessor->markValidationAsFailed();
            }

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

            $customerName = $response['customer_name'] ?? null;

            $success= $response['success'] ?? null;

            $tracable = [
                'customer_name' => $customerName,
                'success'       => $success,
            ];

            $this->trace->info(TraceCode::VALIDATE_VPA_RESPONSE, $tracable);

            if (($response === null) or
                (($response['customer_name'] === null) and
                ($response['success'] === null)))
            {
                throw new LogicException("Mismatch in expected and returned array in vpa validate");
            }

            $data['account_status'] = $response['success'] === true ? AccountStatus::ACTIVE : AccountStatus::INVALID;

            $data['name'] = $response['customer_name'];

            $data['success'] = $response['success'];

            $data['fav_status'] = Status::COMPLETED;

        }
        catch (GatewayErrorException $e)
        {
            //gateway error as per payments api can mean some gateway error where we cannot get any response from the gateway
            //or invalid vpa for some gateways
            //The invalid vpa gateway errors are caught by payments api and only the other gateway errors are been thrown to us
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_VPA_VALIDATE_TIMEOUT,
                [
                    'fa_validation_id' => $this->favId
                ]
            );

            $data['fav_status'] = Status::FAILED;

        }
        catch (BadRequestException $e)
        {
            if ($e->getCode() != ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA) {

                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::FUND_ACCOUNT_VALIDATION_VPA_BAD_REQUEST,
                    [
                        'fa_validation_id' => $this->favId,
                    ]
                );

                throw $e;
            }

            $data['account_status'] = AccountStatus::INVALID;

            $data['name'] = null;

            $data['fav_status'] = Status::COMPLETED;

        }

        return $data;
    }
}
