<?php

namespace RZP\Models\PayoutLink;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\SourceUpdater;
use RZP\Models\User\Core as UserCore;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Entity as PayoutEntity;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

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

    public function resendNotification(string $payoutLinkId, array $input)
    {
        $payoutLink = $this->repo
                            ->payout_link
                            ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

        $payoutLink = $this->core->updateNotificationInformation($payoutLink, $input);

        $this->core->sendLinkToCustomers($payoutLink);

        return;
    }

    public function getStatus(string $payoutLinkId)
    {
        $payoutLink = $this->repo
                           ->payout_link->findByPublicId($payoutLinkId);

        return [Entity::STATUS => $payoutLink->getStatus()];
    }

    /**
     * Api returns the current merchants on-boarding stats for Payout Link feature
     */
    public function onBoardingStatus()
    {
        $data = $this->getPayoutLinkSummary();

        $brandingCompleted = ($this->merchant->getBrandColor() !== null) and ($this->merchant->getLogoUrl() !== null);

        return [
            Entity::BRANDING_COMPLETED    => $brandingCompleted,
            Entity::LINK_CREATED          => empty(array_pull($data,'totalLinks')) !== true,
            Entity::LINK_PROCESSED        => empty(array_pull($data,'processedLinkCount')) !== true,
        ];
    }

    public function summary(array $input): array
    {

        $data = $this->getPayoutLinkSummary();

        return [
            Entity::TOTAL_COUNT           => (array_pull($data,'totalLinks'))?? 0,
            Entity::ISSUED_LINKS_COUNT    => (array_pull($data,'issuedLinkCount'))?? 0,
            Entity::ATTEMPTED_LINKS_COUNT => (array_pull($data,'attemptedLinkCount'))?? 0 ,
        ];
    }

    protected function getPayoutLinkSummary()
    {
        $data = $this->core->getTotalLinksByMerchant($this->auth->getMerchantId());

        $totalLinks = array_sum(array_except($data, [Entity::STATUS]));

        $processedLinkCount = array_pull($data, Status::PROCESSED);

        $issuedLinkCount = array_pull($data, Status::ISSUED);

        $attemptedLinkCount = array_pull($data, Status::ATTEMPTED);

        return array('totalLinks'=>$totalLinks, 'attemptedLinkCount'=> $attemptedLinkCount, 'issuedLinkCount'=>$issuedLinkCount, 'processedLinkCount'=>$processedLinkCount);
    }

    public function create(array $input): array
    {
        if ($this->auth->isStrictPrivateAuth() === false)
        {
            // if this is not strictly Private, then we enforce OTP verification
            // for payout-link creation

            //This is to handle the issue when the user does not provide X-Dashboard-User-Id in the headers
            if ($this->user == null)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_USER_NOT_FOUND,
                    null,
                    [
                        Entity::MERCHANT_ID     => $this->auth->getMerchantId()
                    ]
                );
            }

            $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

            (new UserCore())->verifyOtp($input + ['action' => 'create_payout_link'],
                                        $this->merchant,
                                        $this->user,
                                        $this->mode === Mode::TEST);

            $input = array_except($input, ['otp', 'token']);
        }

        $payoutLink = $this->core->create($input);

        return $payoutLink->toArrayPublic();
    }

    public function updateSettings(array $input, string $merchantId = null)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            // $merchantId will not be null in case of admin auth, as it is part of the route
            // when called from proxy auth, this will be null
            $this->merchant = $this->repo->merchant->findByPublicId($merchantId);
        }

        return $this->core->updateSettings($this->merchant, $input);
    }

    public function getSettings(string $merchantId = null)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            // $merchantId will not be null in case of admin auth, as it is part of the route
            // when called from proxy auth, this will be null
            $this->merchant = $this->repo->merchant->findByPublicId($merchantId);
        }

        return $this->core->getSettings($this->merchant);
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
