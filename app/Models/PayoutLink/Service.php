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
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        return $this->core->updateSettings($merchant, $input);
    }

    public function getSettings(string $merchantId)
    {
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        return $this->core->getSettings($merchant);
    }

    public function initiate(string $payoutLinkId, array $input)
    {
        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        return $this->core->initiate($payoutLink, $input)->toArrayPublic();
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input)
    {
        $fundAccounts = $this->core->getFundAccountsOfContact($payoutLinkId, $input);

        return $fundAccounts->toArrayPublic();
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId, array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_CUSTOMER_OTP_REQUEST,
                           $input);

        (new Validator())->validateInput(Validator::GENERATE_OTP, $input);

        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        return $this->core->generateAndSendCustomerOtp($payoutLink, $input);
    }

    public function cancel(string $payoutLinkId): array
    {
        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        return $this->core->cancel($payoutLink)->toArrayPublic();
    }

    public function viewHostedPage($payoutLinkId)
    {
        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        return $this->core->viewHostedPage($payoutLink);
    }

    public function verifyCustomerOtp(string $payoutLinkId, array $input): array
    {
        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        return $this->core->verifyCustomerOtp($payoutLink, $input);
    }
}
