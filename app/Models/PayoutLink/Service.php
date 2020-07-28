<?php

namespace RZP\Models\PayoutLink;

use View;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Feature\Constants;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Payout\SourceUpdater;
use RZP\Models\User\Core as UserCore;
use RZP\Exception\BadRequestException;
use RZP\Mail\PayoutLink\FailedInternal;
use RZP\Mail\PayoutLink\SuccessInternal;
use RZP\Mail\PayoutLink\SendLinkInternal;
use RZP\Mail\PayoutLink\CustomerOtpInternal;

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

    public function checkIfMerchantOnAPI() : bool
    {
        return $this->merchant->isFeatureEnabled(Constants::X_PAYOUT_LINKS_MS) == false;
    }

    public function resendNotification(string $payoutLinkId, array $input)
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                ->payout_link
                ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            $payoutLink = $this->core->updateNotificationInformation($payoutLink, $input);

            $this->core->sendLinkToCustomers($payoutLink);
        }
        else
        {
            $this->app['payout-links']->resendNotification($payoutLinkId, $input);
        }

        return;
    }

    public function getStatus(string $payoutLinkId)
    {

        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                               ->payout_link->findByPublicId($payoutLinkId);

            return [Entity::STATUS => $payoutLink->getStatus()];
        }
        else
        {
            $payoutLink = $this->app['payout-links']->fetch($payoutLinkId);

            return [Entity::STATUS => $payoutLink['status']];
        }
    }

    /**
     * Api returns the current merchants on-boarding stats for Payout Link feature
     */
    public function onBoardingStatus()
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $data = $this->getPayoutLinkSummary();

            $brandingCompleted = ($this->merchant->getBrandColor() !== null) and ($this->merchant->getLogoUrl() !== null);

            return [
                Entity::BRANDING_COMPLETED    => $brandingCompleted,
                Entity::LINK_CREATED          => empty(array_pull($data,'totalLinks')) !== true,
                Entity::LINK_PROCESSED        => empty(array_pull($data,'processedLinkCount')) !== true,
            ];
        }

        return $this->app['payout-links']->onBoardingStatus($this->auth->getMerchantId());
    }

    public function summary(array $input): array
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $data = $this->getPayoutLinkSummary();

            return [
                Entity::TOTAL_COUNT           => (array_pull($data, 'totalLinks')) ?? 0,
                Entity::ISSUED_LINKS_COUNT    => (array_pull($data, 'issuedLinkCount')) ?? 0,
                Entity::ATTEMPTED_LINKS_COUNT => (array_pull($data, 'attemptedLinkCount')) ?? 0,
            ];
        }

        return $this->app['payout-links']->summary($this->auth->getMerchantId());
    }

    protected function getPayoutLinkSummary()
    {
        $data = $this->core->getTotalLinksByMerchant($this->auth->getMerchantId());

        $totalLinks = array_sum(array_except($data, [Entity::STATUS]));

        $processedLinkCount = array_pull($data, Status::PROCESSED);

        $issuedLinkCount = array_pull($data, Status::ISSUED);

        $attemptedLinkCount = array_pull($data, Status::ATTEMPTED);

        return array('totalLinks'      => $totalLinks, 'attemptedLinkCount' => $attemptedLinkCount,
                     'issuedLinkCount' => $issuedLinkCount, 'processedLinkCount' => $processedLinkCount);
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

        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->core->create($input);

            return $payoutLink->toArrayPublic();
        }

        if($this->user != null)
        {
            $input['user_id'] = $this->user->getId();
        }

        return $this->app['payout-links']->create($this->merchant, $input);
    }

    /*
     * Todo will have to explore this to implement factory pattern https://razorpay.atlassian.net/browse/RX-2515
     */
    public function sendEmailInternal($input)
    {
        if (key_exists('email_type', $input) === true)
        {
            $emailType = array_pull($input, 'email_type');
            if($emailType === 'otp')
            {
                $response = $this->sendOtpEmailInternal($input);
                return $response;
            }
            else if($emailType === 'link')
            {
                $response = $this->sendPayoutLinkEmailInternal($input);
                return $response;
            }
            else if($emailType === 'success')
            {
                $response = $this->sendSuccessPayoutLinkEmailInternal($input);
                return $response;
            }
            else if($emailType === 'failure')
            {
                $response = $this->sendFailurePayoutLinkEmailInternal($input);
                return $response;
            }
            else
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYOUT_LINK_SEND_EMAIL_FAILED,
                    null,
                    []
                );
            }
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_SEND_EMAIL_FAILED,
                null,
                []
            );
        }
    }

    public function sendOtpEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_OTP_EMAIL_INTERNAL_RULE, $input);

        $customerOtpEmail = new CustomerOtpInternal(
            $input[Entity::MERCHANT_ID],
            $input[Entity::OTP],
            $input[Entity::TO_EMAIL],
            $input[Entity::PURPOSE]
        );

        Mail::queue($customerOtpEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function sendPayoutLinkEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_LINK_EMAIL_INTERNAL_RULE, $input);

        $sendLinkEmail = new SendLinkInternal(
            $input['payoutlinkresponse'],
            $input[Entity::MERCHANT_ID],
            $input[Entity::TO_EMAIL]
        );

        Mail::queue($sendLinkEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function sendSuccessPayoutLinkEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_SUCCESS_EMAIL_INTERNAL_RULE, $input);

        $sendLinkEmail = new SuccessInternal(
            $input['payoutlinkresponse'],
            $input['settings'],
            $input['payout_utr'],
            $input[Entity::MERCHANT_ID],
            $input[Entity::TO_EMAIL]
        );

        Mail::queue($sendLinkEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function sendFailurePayoutLinkEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_FAILURE_EMAIL_INTERNAL_RULE, $input);

        $sendLinkEmail = new FailedInternal(
            $input['payoutlinkresponse'],
            $input['settings'],
            $input[Entity::MERCHANT_ID],
            $input[Entity::TO_EMAIL]
        );

        Mail::queue($sendLinkEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function updateSettings(array $input, string $merchantId = null)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            // $merchantId will not be null in case of admin auth, as it is part of the route
            // when called from proxy auth, this will be null
            $this->merchant = $this->repo->merchant->findByPublicId($merchantId);
        }

        if($this->checkIfMerchantOnAPI() == true)
        {
            return $this->core->updateSettings($this->merchant, $input);
        }

        return $this->app['payout-links']->updateSettings($this->merchant->getPublicId(), $input);
    }

    public function getSettings(string $merchantId = null)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            // $merchantId will not be null in case of admin auth, as it is part of the route
            // when called from proxy auth, this will be null
            $this->merchant = $this->repo->merchant->findByPublicId($merchantId);
        }

        if($this->checkIfMerchantOnAPI() == true)
        {
            return $this->core->getSettings($this->merchant);
        }

        return $this->app['payout-links']->getSettings($this->merchant->getPublicId());
    }

    public function initiate(string $payoutLinkId, array $input)
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                                ->payout_link
                                ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            $payoutLink = $this->core->initiate($payoutLink, $input);

            return $payoutLink->toArrayPublic();
        }

        return $this->app['payout-links']->initiate($this->merchant, $input, $payoutLinkId);
    }

    public function pullPayoutStatus($payoutLinkId)
    {
        list($mode, $merchant)  = $this->getModeAndMerchant($payoutLinkId);

        $payout = null;

        $this->merchant = $merchant;

        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                               ->payout_link->findByPublicId($payoutLinkId);

            $payout = $payoutLink->payout();
        }
        else
        {
            $input = [
                'payout_link_id' => $payoutLinkId,
                'merchant_id'    => $merchant->getMerchantId(),
                'expand'         => ['payouts']
            ];

            $payoutLinks = $this->app['payout-links']->fetchMultiple($input);

            if(sizeof($payoutLinks['items']) > 0)
            {
                $payoutLink = $payoutLinks['items'][0];

                $payouts = $payoutLink['payouts'];

                if (sizeof($payouts['items']) > 0)
                {
                    $payout = $payouts['items'][0];

                    $payout = $this->repo->payout->findByPublicId($payout['id']);
                }
            }
        }

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
        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                ->payout_link
                ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            $fundAccounts = $this->core->getFundAccountsOfContact($payoutLink, $input);

            $fundAccountsArray = $fundAccounts->toArrayPublic();

            $this->formatFundAccountsArray($fundAccountsArray);

            return $fundAccountsArray;
        }

        return $this->app['payout-links']->getFundAccountsOfContact($payoutLinkId, $input);
    }

    public function getModeAndMerchant(string $payoutLinkId)
    {
        $entityClass = E::getEntityClass('payout_link');
        $entityId    = $entityClass::verifyIdAndSilentlyStripSign($payoutLinkId);

        // Try to retrieve merchant using LIVE mode
        $mode     = Mode::LIVE;
        $merchant = optional($this->repo->payout_link->connection($mode)->find($entityId))->merchant;

        // If we fail to retrieve merchant, try using TEST mode
        if ($merchant === null)
        {
            $mode     = Mode::TEST;
            $merchant = optional($this->repo->payout_link->connection($mode)->find($entityId))->merchant;
        }

        if ($merchant != null)
            return [$mode, $merchant];

        list($mode, $merchantId) =  $this->app['payout-links']->getModeAndMerchant($payoutLinkId);

        $merchant = $this->repo->merchant->connection($mode)->find($merchantId);

        if ($merchant === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, ['attributes' => $entityId]);
        }

        return [$mode, $merchant];
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId, array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_CUSTOMER_OTP_REQUEST,
                           $input);

        (new Validator())->validateInput(Validator::GENERATE_OTP, $input);

        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                               ->payout_link
                               ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            return $this->core->generateAndSendCustomerOtp($payoutLink, $input);
        }

        return $this->app['payout-links']->generateAndSendCustomerOtp($payoutLinkId, $input);
    }

    public function cancel(string $payoutLinkId): array
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                ->payout_link
                ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            $payoutLink = $this->core->cancel($payoutLink);

            return $payoutLink->toArrayPublic();
        }

        return $this->app['payout-links']->cancel($payoutLinkId);
    }

    public function viewHostedPage($payoutLinkId)
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                ->payout_link
                ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            return $this->core->viewHostedPage($payoutLink);
        }

        $data = $this->app['payout-links']->getHostedPageData($payoutLinkId, $this->merchant);

        return View::make('payout_link.customer_hosted', $data);
    }

    public function verifyCustomerOtp(string $payoutLinkId, array $input): array
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                ->payout_link
                ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            return $this->core->verifyCustomerOtp($payoutLink, $input);
        }

        return $this->app['payout-links']->verifyCustomerOtp($payoutLinkId, $input);
    }

    // used as admin fetch for admin dashboard
    public function fetch(string $entityname, string $id, array $input): array
    {
        $publicId = $id;
        $entityClass = E::getEntityClass('payout_link');
        $id = $entityClass::verifyIdAndSilentlyStripSign($id);

        try
        {
            $entity = $this->entityRepo->findOrFailByPublicIdWithParams($id, $input, true);
            return $entity->toArrayAdmin();
        }
        catch(\Exception $e)
        {
            return $this->app['payout-links']->fetch($publicId);
        }
    }

    // used as admin fetchMultiple for admin dashboard
    public function fetchMultiple(string $entityname, array $input): array
    {
        $originalCount = $input['count'];
        $originalSkip = $input['skip'];
        $input['count'] = $originalCount + $originalSkip;
        $input['skip'] = 0;

        $entities = $this->entityRepo->fetch(
            $input,
            null,
            false,
            true);

        $entities = $entities->toArrayPublic();

        $microserviceEntities = $this->app['payout-links']->fetchMultiple($input);

        $mergedEntities = array_merge($entities['items'], $microserviceEntities['items']);

        usort($mergedEntities, function($a, $b) {
            return $a['created_at'] < $b['created_at'];
        });

        $mergedEntities = array_slice($mergedEntities, $originalSkip, $originalCount);

//        $mergedEntities = array_unique($mergedEntities, SORT_REGULAR);

        $entities['count'] = $originalCount;

        $entities['items'] = $mergedEntities;

        return $entities;
    }

    public function fetchMerchantSpecific(string $id): array
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $entity = $this->entityRepo
                ->findByPublicIdAndMerchant($id, $this->merchant);

            return $entity->toArrayPublic();
        }

        return $this->app['payout-links']->fetch($id, $this->merchant->getMerchantId());
    }

    public function fetchMultipleMerchantSpecific(array $input): array
    {
        if($this->checkIfMerchantOnAPI() == true)
        {
            $entities = $this->entityRepo
                ->fetch($input, $this->merchant->getId());

            return $entities->toArrayPublic();
        }
        $input['merchant_id'] = $this->merchant->getId();
        return $this->app['payout-links']->fetchMultiple($input);
    }

    protected function formatFundAccountsArray(array &$fundAccountsArray)
    {
        $oldFundAccountsItems = $fundAccountsArray["items"];

        $newFundAccountsItems = array_values($oldFundAccountsItems);

        $fundAccountsArray["items"] = $newFundAccountsItems;
    }
}
