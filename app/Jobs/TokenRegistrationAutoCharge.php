<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Illuminate\Support\Facades\App;
use RZP\Models\SubscriptionRegistration;

/**
 * Class TokenRegistrationAutoCharge
 * This queue job is for auto charging the customers where token.registration
 * was created with first charge amount.
 */
class TokenRegistrationAutoCharge extends Job
{
    const MUTEX_LOCK_TIMEOUT = 600;

    protected $tokenRegistration;

    protected $mutex;

    protected $repo;

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

        $this->mutex->acquireAndRelease(
            $this->tokenRegistration->getPublicId(),
            function ()
            {
                $this->repo->reload($this->tokenRegistration);

                (new SubscriptionRegistration\Core())->processAutoCharge($this->tokenRegistration);

                $this->trace->info(
                    TraceCode::TOKEN_REGISTRATION_AUTO_CHARGE_PAYMENT,
                    [
                        'token.registration_id' => $this->tokenRegistration->getId()
                    ]
                );
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_TOKEN_REGISTRATION_OPERATION_IN_PROGRESS
        );
    }
}
