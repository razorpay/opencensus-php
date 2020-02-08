<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\SourceUpdater;

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

    public function getStatus(string $payoutLinkId)
    {
        $payoutLink = $this->repo
                           ->payout_link->findByPublicId($payoutLinkId);

        return [Entity::STATUS => $payoutLink->getStatus()];
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

        $payoutLink = $this->core->initiate($payoutLink, $input);

        return $payoutLink->toArrayPublic();
    }

    public function pullPayoutStatus($payoutLinkId)
    {
        $payoutLink = $this->repo
                           ->payout_link->findByPublicId($payoutLinkId);

        $payout = $payoutLink->payout();

        if ($payout !== null)
        {
            SourceUpdater::update($payout);

            $response = [
                'message'   => 'Update Success',
                'payout_id' => $payout->getPublicId()
            ];
        }
        else
        {
            $response = [
                'message' => 'No associated payouts'
            ];
        }

        return $response;
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input)
    {
        $payoutLink = $this->repo
                           ->payout_link
                           ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $fundAccounts = $this->core->getFundAccountsOfContact($payoutLink, $input);

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

        $payoutLink = $this->core->cancel($payoutLink);

        return $payoutLink->toArrayPublic();
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
