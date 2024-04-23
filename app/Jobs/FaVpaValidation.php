<?php

namespace RZP\Jobs;

use Throwable;
use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger;
use RZP\Services\RazorXClient;
use RZP\Models\FundAccount\Type;
use RZP\Models\Feature\Constants;
use RZP\Exception\LogicException;
use RZP\Exception\RuntimeException;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FundAccount\Validation\Status;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Core as FAVCore;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\FundAccount\Validation\Processor\Factory;
use RZP\Models\FundAccount\Validation\Constants as FavConstants;
use RZP\Models\FundAccount\Validation\Processor\Vpa as VpaProcessor;

class FaVpaValidation extends Job
{
    /**
     * @var string
     */
    protected $favId;

    protected $vpaInput;

    /**
     * Create a new job instance.
     *
     * @param string $mode
     * @param string $favId
     * @param string $vpaInput
     */
    public function __construct(string $mode, string $favId, array $vpaInput = [])
    {
        parent::__construct($mode);

        $this->favId = $favId;

        $this->vpaInput = $vpaInput;
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

            $startTime = microtime(true);

            $this->trace->info(
                TraceCode::FA_VPA_VALIDATION_WORKER_REQUEST,
                [
                    'worker_start_time' => $startTime,
                ]);

            if (empty($this->vpaInput) === false)
            {
                $vpaInput = [
                    'vpa'         => $this->vpaInput['vpa'],
                    'merchant_id' => $this->vpaInput['merchant_id']
                ];

                $data = $this->getVpaValidateResponse($vpaInput);

                $traceable = [
                    'merchant_id'    => $this->vpaInput['merchant_id'],
                    'fav_id'         => $this->vpaInput['fav_id'],
                    'success'        => $data['success'],
                    'account_status' => $data['account_status'],
                    'customer_name'  => $data['name'],
                    'fav_status'     => $data['fav_status'],
                ];

                $this->trace->info(
                    TraceCode::VPA_VALIDATION_FINAL_RESPONSE,
                    $traceable
                );

                $favCore = new FAVCore();

                $favCore->updateFavInMicroservice($this->favId, $data, FundAccountEntity::VPA);

            }
            else {

                /** @var Entity $faValidation */
                $faValidation = $this->repoManager
                    ->fund_account_validation
                    ->findOrFail($this->favId);

                $vpaProcessor = new VpaProcessor($faValidation);

                $fundAccount = $faValidation->fundAccount;

                $isPenniless = $faValidation->merchant->isFeatureEnabled(Constants::PENNILESS_VALIDATION);

                $this->trace->info(
                    TraceCode::VPA_VALIDATION_REQUEST_TO_PAYMENTS_SERVICE,
                    [
                        'account_type' => $fundAccount->getAccountType(),
                        'id' => $fundAccount->getId()
                    ]
                );

                if (($isPenniless === false) && ($fundAccount->getAccountType() !== Type::VPA)) {
                    throw new LogicException("Invalid fund account type");
                }

                if (($isPenniless === true) && ($fundAccount->getAccountType() === Type::BANK_ACCOUNT)) {
                    $accountNumber = $fundAccount->account->getAccountNumber();

                    $ifsc = $fundAccount->account->getIfscCode();

                    $vpaInput = [
                        'vpa' => $accountNumber . "@" . $ifsc . ".ifsc.npci",
                        'merchant_id' => $fundAccount->getMerchantId(),
                    ];
                } else {
                    $vpaInput = [
                        'vpa' => $fundAccount->account->getAddress(),
                        'merchant_id' => $fundAccount->getMerchantId(),
                    ];
                }

                $this->trace->info(
                    TraceCode::VPA_VALIDATION_REQUEST_TO_PAYMENTS_SERVICE,
                    $vpaInput
                );

                $data = $this->getVpaValidateResponse($vpaInput);

                if ((array_key_exists('fav_status', $data)) && ($data['fav_status'] === Status::COMPLETED)) {
                    $faValidation->setRegisteredName($data['name']);

                    $accountStatus = array_key_exists('account_status', $data) ? $data['account_status'] : null;

                    $name = array_key_exists('name', $data) ? $data['name'] : null;

                    $success = array_key_exists('success', $data) ? $data['success'] : null;

                    $traceable = [
                        'isPenniless' => $isPenniless,
                        'account_status' => $accountStatus,
                        'customer_name' => $name,
                        'fav_status' => $data['fav_status'],
                        'id' => $faValidation->getId(),
                        'success' => $success
                    ];

                    $this->trace->info(
                        TraceCode::VPA_VALIDATION_FINAL_RESPONSE,
                        $traceable
                    );

                if (($isPenniless === true) && ($fundAccount->getAccountType() === Type::BANK_ACCOUNT))
                {
                    $favCore = new FAVCore();

                    if (($success === true) &&
                        ($name != null) &&
                        ($favCore->isNameReceivedFromPennilessValid($name, $ifsc) === true) &&
                        ($accountStatus === AccountStatus::ACTIVE))
                    {
                        $this->trace->info(
                            TraceCode::BANK_ACCOUNT_VALIDATED_USING_VPA,
                            $traceable
                        );

                            $vpaProcessor->markValidationAsCompleted($data['account_status'],
                                errDesc: FavConstants::PENNILESS);
                        }
                        else
                        {
                            $this->handlePennilessVPAValidationFailure($faValidation, $traceable);
                        }
                    }
                    else
                    {
                        $vpaProcessor->markValidationAsCompleted($data['account_status']);
                    }
                }
                else
                {
                    $traceable = [
                        'fav_status' => $data['fav_status'],
                        'id' => $faValidation->getId()
                    ];

                    $this->trace->info(
                        TraceCode::VPA_VALIDATION_FINAL_RESPONSE,
                        $traceable
                    );

                    if (($isPenniless === true) && ($fundAccount->getAccountType() === Type::BANK_ACCOUNT)) {
                        $this->handlePennilessVpaValidationFailure($faValidation, $traceable);
                    } else {
                        $vpaProcessor->markValidationAsFailed();
                    }
                }
            }
        }
        catch (RuntimeException $e)
        {
            $this->handleFavException($e, TraceCode::FUND_ACCOUNT_VALIDATION_VPA_VALIDATE_FAILED);
        }
        catch (Throwable $e)
        {
            $this->handleFavException($e, TraceCode::FUND_ACCOUNT_VALIDATION_VPA_FAILED);
        }

        $this->trace->info(
            TraceCode::FA_VPA_VALIDATION_WORKER_RESPONSE,
            [
                'worker_start_time' => $startTime,
                'worker_end_time'    => microtime(true),
                'worker_total_time'  => (microtime(true) - $startTime) * 1000
            ]);

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

    protected function isNameReceivedFromPennilessValid(string $name = null, string $ifsc = null)
    {
        try
        {
            $benificiaryNameNotAllowedArray = (new Admin\Service)->getConfigKey([
                'key' => Admin\ConfigKey::PENNILESS_RESPONSE_BENE_NAME_BLACKLIST
            ]);

            foreach ($benificiaryNameNotAllowedArray as $beneName)
            {
                if(stripos($name, $beneName) !== false)
                {
                    $this->trace->info(TraceCode::VALIDATE_VPA_PENNILESS_INVALID_NAME,
                        [
                            "bene_substring_found" => $beneName,
                            "bank"                => substr($ifsc, 0, 4)
                        ]);

                    return false;
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::VALIDATE_VPA_PENNILESS_NAME_FAILURE,
                [
                    'fa_validation_id' => $this->favId,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * Defines how the job is handled in an event of worker timeout
     */
    protected function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE)
    {
        $this->trace->count(Metric::RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT, [
            'job_name'   => $this->getJobName() ?? '',
            'mode'       => $this->getMode() ?? '',
        ]);

        parent::beforeJobKillCleanUp($variant);

        $context = [
            'favId' => $this->favId,
        ];

        $this->handleWorkerTimeoutGracefully($context);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }

    /**
     * @param Entity $faValidation
     * @param $traceable
     * @return void
     */
    protected function handlePennilessVpaValidationFailure(Entity $faValidation, $traceable)
    {
        $this->trace->info(
            TraceCode::VPA_FAILED_TO_VALIDATE_BANK_ACCOUNT,
            $traceable
        );

        $bankAccountProcessor = Factory::getBankAccountProcessor($faValidation);

        $this->trace->info(
            TraceCode::SWITCHING_BACK_TO_PENNY_DROP,
            $traceable
        );

        $bankAccountProcessor->preProcessValidation();
    }

    protected function handleFavException(Throwable $e, string $traceCode)
    {
        if (empty($this->vpaInput) === false)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                $traceCode,
                [
                    'vpa' => $this->vpaInput['vpa'],
                    'merchant_id' => $this->vpaInput['merchant_id'],
                ]
            );

            $favCore = new FAVCore();

            $data = [
                'error'  => $e->getMessage()
            ];

            $favCore->updateFavInMicroservice($this->favId, $data, FundAccountEntity::VPA);
        }
        else
        {

            $faValidation = $this->repoManager
                ->fund_account_validation
                ->findOrFail($this->favId);

            $fundAccount = $faValidation->fundAccount;

            $isPenniless = $faValidation->merchant->isFeatureEnabled(Constants::PENNILESS_VALIDATION);

            if (($isPenniless === true) && ($fundAccount->getAccountType() === Type::BANK_ACCOUNT)) {
                $traceable = [
                    'id' => $faValidation->getId(),
                    'exception' => $e
                ];

                $this->handlePennilessVpaValidationFailure($faValidation, $traceable);
            } else {
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    $traceCode,
                    [
                        'fa_validation_id' => $this->favId
                    ]
                );
            }
        }
    }
}
