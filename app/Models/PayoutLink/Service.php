<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;
    const OTP = 'otp';

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;


    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->payout_link;
    }

    public function updateSettings(string $merchantId, array $input)
    {
        return $this->core->updateSettings($merchantId, $input);
    }

    public function getSettings(string $merchantId)
    {
        return $this->core->getSettings($merchantId);
    }

    public function initiate(string $payoutLinkId, array $input)
    {
        return $this->core->initiate($payoutLinkId, $input);
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input)
    {
        return $this->core->getFundAccountsOfContact($payoutLinkId, $input);
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId, array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_CUSTOMER_OTP_REQUEST,
                           $input
        );

        return $this->core->generateAndSendCustomerOtp($payoutLinkId, $input);
    }

    public function cancel(string $payoutLinkId): Entity
    {
        return $this->core->cancel($payoutLinkId);
    }

    public function viewHostedPage($payoutLinkId)
    {
        return $this->core->viewHostedPage($payoutLinkId);
    }

    public function verifyCustomerOtp(string $payoutLinkId, array $input): array
    {
        return $this->core->verifyCustomerOtp($payoutLinkId, $input);
    }
}
