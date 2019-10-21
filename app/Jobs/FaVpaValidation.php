<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Payment\Processor\Vpa;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FundAccount\Validation\AccountStatus;
use RZP\Models\FundAccount\Validation\Processor\Vpa as VpaProcessor;

class FaVpaValidation extends Job
{
    use Vpa;

    protected $queueConfigKey = 'fa_vpa_validation';

    protected $favId;

    protected $repo;

    protected $app;

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

            $this->repo = $this->repoManager;

            $this->app = App::getFacadeRoot();

            $faValidation = $this->repoManager
                                 ->fund_account_validation
                                 ->findOrFail($this->favId);

            if ($faValidation === null)
            {
                $this->logAndDelete(['fav_id' => $this->favId],
                    TraceCode::FUND_ACCOUNT_VALIDATION_NOT_FOUND);

                return;
            }

            $vpaProcessor = new VpaProcessor($faValidation);

            $vpa = [ "vpa" => $faValidation->fundAccount->account->getAddress() ];

            try {
                $data = $this->validateVpa($vpa);

                $faValidation->setRegisteredName($data['customer_name']);

                if ($data['success'] === true)
                {
                    $vpaProcessor->markValidationAsCompleted(AccountStatus::ACTIVE);
                }
                else {
                    $vpaProcessor->markValidationAsCompleted(AccountStatus::INVALID);
                }
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

                $vpaProcessor->markValidationAsCompleted(AccountStatus::UNKNOWN);
            }
        }

        catch (\Throwable $e)
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
    }

    protected function logAndDelete(array $data,
        string $traceCode)
    {
        $this->trace->info($traceCode, $data);

        $this->delete();
    }
}
