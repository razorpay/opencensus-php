<?php

namespace RZP\Jobs;

use App;
use RZP\Constants\Mode;
use RZP\Models\BankTransfer;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\Attribute\Service as AttributeService;
use RZP\Models\Merchant\InternationalIntegration\Service as MerchantInternationalIntegrationService;

class CrossBorderCommonUseCases extends Job
{
    const MODE = 'mode';

    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 300;

    const INTL_BANK_TRANSFER_SWIFT_SETTLEMENT = 'INTL_BANK_TRANSFER_SWIFT_SETTLEMENT';
    const MERCHANT_ONBOARD_NETWORK = 'MERCHANT_ONBOARD_NETWORK';
    const EMERCHANTPAY_ONBOARDING_VIA_MAF = 'EMERCHANTPAY_ONBOARDING_VIA_MAF';

    /**
     * @var string
     */
    protected $queueConfigKey = 'cross_border_use_case';

    /**
     * @var array
     */
    protected $payload;

    protected $mode;

    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    public $timeout = 300;

    public function __construct(array $payload)
    {
        $this->setMode($payload);

        parent::__construct($this->mode);

        $this->payload = $payload;
    }

    public function handle()
    {
        try {
            parent::handle();

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_INIT,[
                'payload'  => $this->payload,
            ]);

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];

            $action     = $this->payload['action'];

            switch($action)
            {
                case self::INTL_BANK_TRANSFER_SWIFT_SETTLEMENT:
                    (new BankTransfer\Service())->settlementFromCurrencyCloud($this->payload['body']);
                    break;
                case self::MERCHANT_ONBOARD_NETWORK:
                    (new AttributeService())->onboardMerchantOnNetworks($this->payload['body']);
                    break;
                case self::EMERCHANTPAY_ONBOARDING_VIA_MAF:
                    (new MerchantInternationalIntegrationService())->generateEmerchantpayMaf($this->mode, $this->payload['body']['merchant_id']);
                    break;
                default:
                    $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_INVALID_ACTION,[
                        'payload'  => $this->payload,
                        'message'  => 'invalid action provided'
                    ]);
            }

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_COMPLETED,[
                'payload'  => $this->payload,
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CROSS_BORDER_COMMON_USE_CASES_FAILED,[
                    'payload' => $this->payload,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $this->release($workerRetryDelay);

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_RELEASED, [
                'payload'               => $this->payload,
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DELETED, [
                'payload'           => $this->payload,
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);
        }
    }

    /**
     * Set mode for job.
     * @param $payload array
     *
     * @return void
     */
    protected function setMode(array $payload): void
    {
        if (array_key_exists(self::MODE, $payload) === true) {
            $this->mode = $payload[self::MODE];
        } else {
            $this->mode = Mode::LIVE;
        }
    }

}
