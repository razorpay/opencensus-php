<?php

namespace RZP\Models\PayoutLink;

use View;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Batch\Type;
use RZP\Http\RequestHeader;
use RZP\Constants\Entity as E;
use Illuminate\Support\Facades\Mail;
use RZP\Base\ConnectionType;
use RZP\Models\User\Core as UserCore;
use RZP\Exception\BadRequestException;
use RZP\Models\Batch\Core as BatchCore;
use RZP\Mail\PayoutLink\FailedInternal;
use RZP\Mail\PayoutLink\SuccessInternal;
use RZP\Mail\PayoutLink\SendLinkInternal;
use RZP\Mail\PayoutLink\ApprovalOtpInternal;
use RZP\Mail\PayoutLink\CustomerOtpInternal;
use RZP\Mail\PayoutLink\SendReminderInternal;
use RZP\Mail\PayoutLink\BulkApprovalOtpInternal;
use RZP\Models\Workflow\Service\Adapter\Constants;
use RZP\Mail\PayoutLink\SendProcessingExpiredInternal;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;
use RZP\Models\PayoutLink\Constants as PayoutLinkConstants;

class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;
    const SUCCESS = 'success';
    const FAILURE = 'failure';
    const PAYOUT_LINK_IDS = 'payout_link_ids';
    const MESSAGE = 'message';
    const UPDATE_SUCCESS = 'Update Success';

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

    public function checkIfPLServiceIsDown()
    {
        // todo temp fix https://jira.corp.razorpay.com/browse/RX-3668
        return;

        $mid = $this->merchant->getId();

        $variant = $this->app['razorx']->getTreatment($mid,
            Merchant\RazorxTreatment::RX_IS_PAYOUT_LINK_SERVICE_DOWN,
            $this->app['rzp.mode'] ?? 'live');

        if($variant == 'on')
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_SERVICE_UNDER_MAINTAINENCE,
                null,
                [
                    Entity::MERCHANT_ID     => $this->auth->getMerchantId()
                ]
            );
        }
    }

    public function checkIfMerchantOnAPI() : bool
    {
        // todo temp fix https://jira.corp.razorpay.com/browse/RX-3668
        return false;

        $mid = $this->merchant->getId();

        $variant = $this->app['razorx']->getTreatment($mid,
            Merchant\RazorxTreatment::RX_PAYOUT_LINK_MICROSERVICE,
            $this->app['rzp.mode'] ?? 'live');

        return !($variant == 'on');

        //return $this->merchant->isFeatureEnabled(Constants::X_PAYOUT_LINKS_MS) == false;
    }

    public function resendNotification(string $payoutLinkId, array $input)
    {
        $this->checkIfPLServiceIsDown();

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

    public function getStatus(string $payoutLinkId, array $input)
    {
        $this->checkIfPLServiceIsDown();

        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                               ->payout_link->findByPublicId($payoutLinkId);

            return [Entity::STATUS => $payoutLink->getStatus()];
        }
        else
        {
            $input['fetch_internal_status'] = true;

            $payoutLink = $this->app['payout-links']->fetch($payoutLinkId, $input);

            return [Entity::STATUS => $payoutLink['status']];
        }
    }

    /**
     * Api returns the current merchants on-boarding stats for Payout Link feature
     */
    public function onBoardingStatus()
    {
        $this->checkIfPLServiceIsDown();

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
        $this->checkIfPLServiceIsDown();

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
        $this->checkIfPLServiceIsDown();

        // un-setting the user-id here if it came in the request.
        // not throwing error coz don't want to break the API implementation for the merchant
        // discussion: https://razorpay.slack.com/archives/C012KKG1STS/p1629710183131700?thread_ts=1627835655.011700&cid=C012KKG1STS
        unset($input['user_id']);

        unset($input['userId']);

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

        $this->app['trace']->info(TraceCode::PAYOUT_LINK_CREATE_REQUEST_KEYS,
            [
                'input_keys' => array_keys($input),
            ]);

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
            else if($emailType === 'reminder')
            {
                $response = $this->sendReminderForPayoutLinkEmailInternal($input);
                return $response;
            }
            else if($emailType === 'processing_expired')
            {
                $response = $this->sendProcessingExpiredPayoutLinkEmailInternal($input);
                return $response;
            }
            else if($emailType === 'approval_otp')
            {
                $response = $this->sendApprovalOtpEmailInternal($input);
                return $response;
            }
            else if($emailType === 'bulk_approval_otp')
            {
                $response = $this->sendBulkApprovalOtpEmailInternal($input);
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

    public function sendApprovalOtpEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_APPROVE_OTP_EMAIL_INTERNAL_RULE, $input);

        $approvalOtpEmail = new ApprovalOtpInternal(
            $input['payout_link_details'],
            $input[Entity::TO_EMAIL],
            $input[Entity::OTP],
            $input['validity']
        );

        Mail::queue($approvalOtpEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function sendBulkApprovalOtpEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_BULK_APPROVE_OTP_EMAIL_INTERNAL_RULE, $input);

        $bulkApprovalOtpEmail = new BulkApprovalOtpInternal(
            $input['payout_links_count'],
            $input['total_amount'],
            $input[Entity::TO_EMAIL],
            $input[Entity::OTP],
            $input['validity']
        );

        Mail::queue($bulkApprovalOtpEmail);

        return [Entity::SUCCESS => Entity::OK];
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

    public function sendReminderForPayoutLinkEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_REMINDER_EMAIL_INTERNAL_RULE, $input);

        $sendReminderEmail = new SendReminderInternal(
            $input['payout_link_details'],
            $input[Entity::MERCHANT_ID],
            $input[Entity::TO_EMAIL]
        );

        Mail::queue($sendReminderEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function sendProcessingExpiredPayoutLinkEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_PROCESSING_EXPIRED_EMAIL_INTERNAL_RULE, $input);

        $sendReminderEmail = new SendProcessingExpiredInternal(
            $input['payout_link_details'],
            $input['settings'],
            $input[Entity::MERCHANT_ID],
            $input[Entity::TO_EMAIL]
        );

        Mail::queue($sendReminderEmail);

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

        $this->checkIfPLServiceIsDown();

        if($this->checkIfMerchantOnAPI() == true)
        {
            return $this->core->updateSettings($this->merchant, $input);
        }

        return $this->app['payout-links']->updateSettings($this->merchant->getPublicId(), $input);
    }

    public function getMerchantSupportSettings(Merchant\Entity $merchant)
    {
        return $this->core->getMerchantSupportSettings($merchant);
    }

    public function getSettings(string $merchantId = null)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            // $merchantId will not be null in case of admin auth, as it is part of the route
            // when called from proxy auth, this will be null
            $this->merchant = $this->repo->merchant->findByPublicId($merchantId);
        }

        $this->checkIfPLServiceIsDown();

        if($this->checkIfMerchantOnAPI() == true)
        {
            return $this->core->getSettings($this->merchant);
        }
        return $this->app['payout-links']->getSettings($this->merchant->getPublicId());
    }

    public function initiate(string $payoutLinkId, array $input)
    {
        $this->checkIfPLServiceIsDown();

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

    public function pullBulkPayoutStatus($payoutLinkId, array $input = [])
    {
        if (empty($input) === true)
        {
            return self::pullPayoutStatus($payoutLinkId);
        }

        $successIds = $failureIds = [];

        $payoutLinkIdsString = array_pull($input, self::PAYOUT_LINK_IDS, []);

        $payoutLinkIds = explode(',', $payoutLinkIdsString);

        foreach ($payoutLinkIds as $id)
        {
            try
            {
                $response = self::pullPayoutStatus($id);

                $message = array_pull($response, self::MESSAGE,"");

                if ($message === self::UPDATE_SUCCESS)
                {
                    array_push($successIds, $id);
                }
                else
                {
                    array_push($failureIds, $id . ' ' . $message);
                }
            }
            catch(\Exception $e)
            {
                array_push($failureIds, $id . ' ' . $e->getMessage());
            }
        }
        return [
            self::SUCCESS => $successIds,
            self::FAILURE => $failureIds
        ];
    }

    public function pullPayoutStatus($payoutLinkId)
    {
        list($mode, $merchant)  = $this->getModeAndMerchant($payoutLinkId);

        $payout = null;

        $this->merchant = $merchant;

        $this->checkIfPLServiceIsDown();

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

                    $payout = $this->repo->payout->connection($mode)->findByPublicId($payout['id']);
                }
            }
        }

        if ($payout !== null)
        {
            SourceUpdater::update($payout, null, $mode);

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
        $this->checkIfPLServiceIsDown();

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

        $entityClass::verifyIdAndSilentlyStripSign($payoutLinkId);

        list($mode, $merchantId) =  $this->app['payout-links']->getModeAndMerchant($payoutLinkId);

        $merchant = $this->repo->merchant->connection($mode)->find($merchantId);

        if ($merchant === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, ['attributes' => $payoutLinkId]);
        }

        return [$mode, $merchant];
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId, array $input)
    {
        $this->checkIfPLServiceIsDown();

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
        $this->checkIfPLServiceIsDown();

        if($this->checkIfMerchantOnAPI() == true)
        {
            $payoutLink = $this->repo
                ->payout_link
                ->findByPublicIdAndMerchant($payoutLinkId, $this->merchant);

            $payoutLink = $this->core->cancel($payoutLink);

            return $payoutLink->toArrayPublic();
        }

        return $this->app['payout-links']->cancel($payoutLinkId, $this->merchant->getId());
    }

    public function viewHostedPage($payoutLinkId)
    {
        $variant = $this->app['razorx']->getTreatment($this->merchant->getId(),
            Merchant\RazorxTreatment::RX_IS_PAYOUT_LINK_SERVICE_DOWN,
            $this->app['rzp.mode'] ?? 'live');

//        if($variant == 'on')
//        {
//            return View::make('payout_link.customer_hosted_maintenance');
//        }

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
        $this->checkIfPLServiceIsDown();

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
            return $this->app['payout-links']->fetch($publicId, $input);
        }
        catch(\Exception $e)
        {
            $entity = $this->entityRepo->findOrFailByPublicIdWithParams($id, $input, ConnectionType::REPLICA);

            return $entity->toArrayAdmin();
        }
    }

    // used as admin fetchMultiple for admin dashboard
    public function fetchMultiple(string $entityname, array $input): array
    {
        if(key_exists('merchant_id', $input) === false)
        {
            $entities = new Base\PublicCollection();
            return $entities->toArrayPublic();
        }

        $merchantId = $input['merchant_id'];
        $this->merchant = $this->repo->merchant->findByPublicId($merchantId);

        if($this->checkIfMerchantOnAPI() == true)
        {
            unset($input['merchant_id']);

            $entities = $this->entityRepo->fetch(
                $input,
                $this->merchant->getPublicId(),
                false,
                true);

            return $entities->toArrayPublic();
        }
        return $this->app['payout-links']->fetchMultiple($input);
    }

    public function fetchMerchantSpecific(string $id, array $input): array
    {
        $this->checkIfPLServiceIsDown();

        if($this->checkIfMerchantOnAPI() == true)
        {
            $entity = $this->entityRepo
                ->findByPublicIdAndMerchant($id, $this->merchant);

            return $entity->toArrayPublic();
        }

        if($this->auth->isProxyAuth() === true)
        {
            $input['user_id'] = $this->auth->getUser()->getId();

            $input['user_role'] = $this->auth->getUserRole();
        }

        return $this->app['payout-links']->fetch($id, $input, $this->merchant->getMerchantId());
    }

    public function fetchMultipleMerchantSpecific(array $input): array
    {
        $this->checkIfPLServiceIsDown();

        if($this->checkIfMerchantOnAPI() == true)
        {
            $entities = $this->entityRepo
                ->fetch($input, $this->merchant->getId());

            return $entities->toArrayPublic();
        }

        $input['merchant_id'] = $this->merchant->getId();

        if($this->auth->isProxyAuth() === true)
        {
            $input['user_id'] = $this->auth->getUser()->getId();

            $input['user_role'] = $this->auth->getUserRole();
        }

        return $this->app['payout-links']->fetchMultiple($input);
    }

    public function adminActions(array $input): array
    {
        $this->checkIfPLServiceIsDown();

        $jsonInput = array_pull($input, 'json_data', null);

        if ($jsonInput !== null) {

            $parsedData = json_decode($jsonInput, true);

            if ($parsedData == null)
            {
                return ['message' => 'json could not be decoded'];
            }

            // add actor details for BulkRejectPLs action
            if (array_key_exists(PayoutLinkConstants::ACTION_TYPE, $parsedData) === true && $parsedData[PayoutLinkConstants::ACTION_TYPE] === PayoutLinkConstants::BULK_REJECT_PLS_ACTION)
            {
                $this->preparePayloadForBulkRejectAction($parsedData);
            }

            $input['json_data'] = json_encode($parsedData, true);
        }

        return $this->app['payout-links']->adminActions($input);
    }

    private function preparePayloadForBulkRejectAction(array &$input)
    {
        $this->addActorDetails($input[PayoutLinkConstants::ADDITIONAL_DATA], true);
    }

    public function ownerBulkRejectPayoutLinks(array $input): array
    {
        (new Validator())->validateInput(Validator::OWNER_BULK_REJECT_PAYOUT_LINKS, $input);

        $bulkRejectPayload = $this->prepareOwnerBulkRejectPayload($input);

        $this->app['trace']->info(TraceCode::OWNER_BULK_REJECT_PAYOUT_LINKS_REQUEST,
            [
                'admin_action_payload' => $bulkRejectPayload,
            ]);

        return $this->app['payout-links']->adminActions($bulkRejectPayload);
    }

    public function getBatchSummary(string $batchId)
    {
        return $this->app['payout-links']->getBatchSummary($this->merchant->getId(), $batchId);
    }

    public function bulkResendNotification(array $input): array
    {
        return $this->app['payout-links']->bulkResendNotification($input);
    }

    protected function formatFundAccountsArray(array &$fundAccountsArray)
    {
        $oldFundAccountsItems = $fundAccountsArray["items"];

        $newFundAccountsItems = array_values($oldFundAccountsItems);

        $fundAccountsArray["items"] = $newFundAccountsItems;
    }

    private function prepareOwnerBulkRejectPayload(array $input): array
    {
        /*
         * Input: $input['payout_link_ids'] = ['poutlk_123', ...]
         * Output:
            {
                "action_type": "BulkRejectPLs",
                "additional_data": {
                    "payout_link_ids": "poutlk_1234,poutlk_789",
                    "actor_id": <owner-id>,
                    "actor_type": "owner",
                    "actor_property_key": "role",
                    "actor_property_value": "owner",
                    "owner_id": "MID",
                    "service": "rx_live",
                    "comment": "",
                }
            }
        */
        $payload = array();

        $payoutLinkIds = $input[PayoutLinkConstants::PAYOUT_LINK_IDS];

        $payoutLinks = join(',', $payoutLinkIds);

        $payload[PayoutLinkConstants::ACTION_TYPE] = PayoutLinkConstants::BULK_REJECT_PLS_ACTION;

        $additionalData = [
            PayoutLinkConstants::PAYOUT_LINK_IDS => $payoutLinks,
            Constants::COMMENT => $input[PayoutLinkConstants::USER_COMMENT] ?? '',
        ];

        $this->addActorDetails($additionalData, false);

        $payload[PayoutLinkConstants::ADDITIONAL_DATA] = $additionalData;

        $bulkRejectPayload['json_data'] = json_encode($payload);

        return $bulkRejectPayload;
    }

    private function addActorDetails(array &$additionalData, bool $isAdminAction)
    {
        $ba = app('basicauth');

        $user = $isAdminAction ? $ba->getAdmin() : $ba->getUser();

        $userRole = $isAdminAction ? Constants::ADMIN : Constants::OWNER;

        $additionalData += [
            Constants::ACTOR_ID => $user->getId(),
            Constants::ACTOR_TYPE => $userRole,
            Constants::ACTOR_PROPERTY_KEY => Constants::ROLE,
            Constants::ACTOR_PROPERTY_VALUE => $userRole,
            Constants::SERVICE => Constants::SERVICE_RX . $ba->getMode(),
            Constants::ACTOR_EMAIL => $user->getEmail(),
            Constants::ACTOR_NAME => $user->getName(),
        ];

        if ($isAdminAction === false)
        {
            $additionalData += [
                PayoutLinkConstants::MERCHANT_ID => $ba->getMerchantId()
            ];
        }
    }

    public function fetchPendingPayoutLinks(array $input): array
    {
        $this->app['trace']->info(TraceCode::FETCH_PENDING_PAYOUT_LINKS_REQUEST);

        (new Validator())->validateInput(Validator::FETCH_PENDING_PAYOUT_LINKS, $input);

        return $this->app['payout-links']->fetchPayoutLinksSummaryForMerchant($input, $this->merchant->getId());
    }
}
