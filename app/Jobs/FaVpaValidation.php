<?php

namespace RZP\Jobs;

use Throwable;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Processor\Vpa as VpaProcessor;

class FaVpaValidation extends Job
{
    protected $queueConfigKey = 'fa_vpa_validation';

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

            $vpa = [ "vpa" => $faValidation->fundAccount->account->getAddress() ];

            try {
                $paymentService = PaymentService::getNewInstance();

                $data = $paymentService->validateVpa($vpa);

                $faValidation->setRegisteredName($data['customer_name']);

                $favStatus = $data['success'] === true ? AccountStatus::ACTIVE : AccountStatus::INVALID;
            }
            catch (GatewayErrorException $e)
            {
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::FUND_ACCOUNT_VALIDATION_VPA_TIMEOUT,
                    [
                        'fa_validation_id' => $this->favId
                    ]
                );

                $favStatus = AccountStatus::UNKNOWN;
            }

            $vpaProcessor->markValidationAsCompleted($favStatus);
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

            $this->delete();
        }
    }
}
