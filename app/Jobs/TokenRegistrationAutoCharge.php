<?php


namespace RZP\Jobs;

use Razorpay\Trace\Logger;


use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Illuminate\Support\Facades\App;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\SubscriptionRegistration\Metric;

/**
 * Class TokenRegistrationAutoCharge
 * This queue job is for auto charging the customers where token.registration
 * was created with first charge amount.
 */
class TokenRegistrationAutoCharge extends Job
{
    const MUTEX_LOCK_TIMEOUT = 180;

    protected $tokenRegistration;

    protected $mutex;

    protected $repo;

    public $tries = 3;

    /**
     * Default timeout value for a job is 60s. Changing it to 180s
     * as Auto Charge takes more than 1 minute to complete in some cases.
     */
    public $timeout = 180;

    public function __construct(string $mode, SubscriptionRegistration\Entity $tokenRegistration )
    {
        parent::__construct($mode);

        $this->tokenRegistration = $tokenRegistration;
    }

    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        $this->mutex = $app['api.mutex'];

        $this->repo = $app['repo'];

        try
        {
            $this->repo->reload($this->tokenRegistration);

            $this->mutex->acquireAndRelease(
                $this->tokenRegistration->getPublicId(),
                function ()
                {
                    try
                    {
                        (new SubscriptionRegistration\Core())->processAutoCharge($this->tokenRegistration);

                        $this->trace->info(
                            TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_PAYMENT,
                            [
                                'token.registration_id' => $this->tokenRegistration->getId(),
                            ]
                        );
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->count(Metric::SUBSCRIPTION_REGISTRATION_AUTO_PAYMENT_FAILED,
                                            $this->tokenRegistration->getMetricDimensions(
                                                ['failure_reason' => $e->getCode()]));

                        $this->trace->traceException(
                            $e,
                            Logger::ERROR,
                            TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_FAILED,
                            [
                                'token_registration_id' => $this->tokenRegistration->getPublicId(),
                            ]
                        );

                        $this->tokenRegistration->setFailureReason($e->getCode());

                        $this->repo->saveOrFail($this->tokenRegistration);
                    }
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_TOKEN_REGISTRATION_OPERATION_IN_PROGRESS
            );

        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_FAILED,
                [
                    'token_registration_id' => $this->tokenRegistration->getPublicId(),
                ]
            );

            $this->delete();
        }


    }
}
