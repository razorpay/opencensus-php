<?php

namespace RZP\Models\Invitation;

use Request;
use ApiResponse;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Mail;
use Illuminate\Support\Collection;

use OpenCensus\Trace\Propagator\ArrayHeaders;
use Psr\Http\Message\RequestInterface;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Http\Request\Requests;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\OrgWiseConfig;
use RZP\Models\Base;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Feature;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Models\User\AxisUserRole;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Mail\Invitation\Invite as InvitationMail;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Mail\Invitation\Razorpayx\Invite as RazorpayXInvitationMail;
use RZP\Mail\Invitation\Razorpayx\BankLmsInvite as BankLmsInvite;
use RZP\Mail\Invitation\Razorpayx\VendorPortalInvite as VendorPortalInvitationMail;
use RZP\Mail\Invitation\Razorpayx\IntegrationInvite as XAccountingIntegrationInviteMail;
use RZP\Trace\Tracer;
use RZP\Tests\P2p\Service\Base\Traits;

define('JOINING_INTEGRATION_INVITATION', 'joining_integration_invitation');

class Core extends Base\Core
{
    use Traits\ExceptionTrait;
    use Traits\DbEntityFetchTrait;

    /**
     * @var \RZP\Services\VendorPortal\Service
     */
    protected $vendorPortalService;

    /**
     * @var \RZP\Services\GenericAccountingIntegration\Service
     */
    protected $integrationInviteService;

    public function __construct()
    {
        parent::__construct();

        $this->vendorPortalService = $this->app['vendor-portal'];

        $this->integrationInviteService = $this->app['accounting-integration-service'];
    }

    public function create(array $input): Entity
    {
        $input[Entity::TOKEN] = str_random(40);

        $invitation = (new Entity);

        $invitation->merchant()->associate($this->merchant);

        $invitation->build($input);

        $senderName = $this->getSenderName($input);

        $invitedUser = $this->repo->user->getUserFromEmail(strtolower($input[Entity::EMAIL]));

        $allMerchantsForInvitedUser = optional($invitedUser)->merchants;

        if (empty($invitedUser) === false)
        {
            $variant = $this->app->razorx->getTreatment(
                $this->app['request']->getId(),
                Merchant\RazorxTreatment::SECOND_FACTOR_AUTH_PROJECT_EXP,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                $merchantCollections = $invitedUser->merchants()->get();

                //
                // if the invitedUser is restricted or
                // merchant is restricted and user is associated with any other merchant
                // then invitation action is not performed
                //
                if ((count($merchantCollections) > 0 and
                     $this->merchant->getRestricted() === true) or
                    $invitedUser->getRestricted() === true)
                {
                    $this->trace->info(
                        TraceCode::INVITATION_CREATE_FAILED, [
                        Entity::MERCHANT_ID                       => $this->merchant['id'],
                        'invited_user_merchant_count'             => count($merchantCollections),
                        'merchant_' . Merchant\Entity::RESTRICTED => $this->merchant->getRestricted(),
                        'user_' . Merchant\Entity::RESTRICTED     => $invitedUser->getRestricted(),
                    ]);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVITATION_CREATE_FAILED);
                }
            }

            // Associate user only if it exists
            $invitation->user()->associate($invitedUser);
        }

        $this->repo->saveOrFail($invitation);

        $this->trace->info(TraceCode::INVITATION_CREATE, $invitation->toArrayPublic());

        $invitedUserExists = (empty($invitedUser) === false);

        $isIntegrationInvite = false;

        if (empty($input[Entity::INVITATIONTYPE]) === false && $input[Entity::INVITATIONTYPE] == JOINING_INTEGRATION_INVITATION)
        {
            $integrationInvite = $this->createXAccountingIntegrationInvitation($input,false);

            $isIntegrationInvite = true;
        }

        $this->sendEmail($invitation, $senderName, $invitedUserExists, $allMerchantsForInvitedUser, $isIntegrationInvite);

        $this->pushSelfServeSuccessEventsToSegmentForMemberInvitation();

        return $invitation;
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function createBankLmsUserInvitation(array $input, Merchant\Entity $partnerMerchant): Entity
    {
        $this->merchant = $partnerMerchant;

        return $this->create($input);
    }

    public function createInvitationDraft(array $input): Entity
    {
        $input[Entity::TOKEN] = str_random(40);

        $input[Entity::IS_DRAFT] = 1;

        $invitation = (new Entity);

        $this->merchant = $this->repo->merchant->findByPublicId($input[Entity::MERCHANT_ID]);

        $invitation->merchant()->associate($this->merchant);

        unset($input[Entity::MERCHANT_ID]);

        $invitation->build($input);

        $invitedUser = $this->repo->user->getUserFromEmail(strtolower($input[Entity::EMAIL]));

        if (empty($invitedUser) === false)
        {
            // Associate user only if it exists
            $invitation->user()->associate($invitedUser);
        }

        $this->repo->saveOrFail($invitation);

        $this->trace->info(TraceCode::INVITATION_CREATE, $invitation->toArrayPublic());

        return $invitation;
    }

    public function createVendorPortalInvitation(array $input, string $contactId): Entity
    {
        $input[Entity::TOKEN] = str_random(40);

        $invitation = (new Entity);


        $vendorPortalMerchantId = $this->app['config']['applications.vendor_payments']['vendor_portal_merchant_id'];

        $vendorPortalMerchant = $this->getDbMerchantById($vendorPortalMerchantId);

        $invitation->merchant()->associate($vendorPortalMerchant);

        $invitation->build($input);

        $invitedUser = $this->repo->user->getUserFromEmail(strtolower($input[Entity::EMAIL]));

        if (empty($invitedUser) === false)
        {
            // Associate user only if it exists
            $invitation->user()->associate($invitedUser);
        }

        // Call vendor-payments to check if it is a valid invite
        $createParams = [
            'contact_id'   => $contactId,
            'invite_token' => $input[Entity::TOKEN],
        ];

        if (empty($invitedUser) === false)
        {
            $createParams['vendor_user_id'] = $invitedUser->getPublicId();
        }

        $this->vendorPortalService->createInvite($this->merchant, $createParams);

        $this->repo->saveOrFail($invitation);

        $invitedUserExists = (empty($invitedUser) === false);

        // Send email
        $this->sendVendorPortalInviteEmail($invitation, $contactId, $invitedUserExists);

        return $invitation;
    }

    public function resendVendorPortalInvitation(MerchantEntity $merchant, string $contactId): Entity
    {
        $params['contact_id'] = $contactId;

        $token = $this->vendorPortalService->getInviteToken($merchant, $params);

        $invitation = $this->fetchByToken($token['invite_token']);

        $invitedUser = $this->repo->user->getUserFromEmail(strtolower($invitation[Entity::EMAIL]));

        $invitedUserExists = (empty($invitedUser) === false);

        $this->sendVendorPortalInviteEmail($invitation, $contactId, $invitedUserExists);

        return $invitation;
    }

    public function fetchByToken(string $token): Entity
    {
        $invitation = $this->repo->invitation->fetchByToken($token);

        return $invitation;
    }

    public function list($product = Product::PRIMARY): array
    {
        $merchant = $this->merchant;

        return $this->repo->invitation->fetchInvitations($product, $merchant->getMerchantId());
    }

    public function listDraftInvitations($product): array
    {
        $merchant = $this->merchant;

        return $this->repo->invitation->listDraftInvitations($product, $merchant->getMerchantId());
    }

    public function edit(Entity $invitation, array $input): Entity
    {
        $invitation->edit($input);

        $this->repo->saveOrFail($invitation);

        $this->trace->info(TraceCode::INVITATION_EDIT, $invitation->toArrayPublic());

        return $invitation;
    }

    public function resend(Entity $invitation, array $input): Entity
    {
        $invitation->edit($input, 'resend');

        $senderName = $this->getSenderName($input);

        $invitedUser = $this->repo->user->getUserFromEmail($invitation->getEmail());

        $allMerchantsForInvitedUser = optional($invitedUser)->merchants;

        $invitedUserExists = (empty($invitedUser) === false);

        $this->sendEmail($invitation, $senderName, $invitedUserExists, $allMerchantsForInvitedUser);

        return $invitation;
    }

    public function acceptDraftInvitations(array $input)
    {
        $inviteIdArray = $input['invitation_ids'];

        foreach ($inviteIdArray as $inviteId) {

            $invitation = $this->repo->invitation->findByIdAndMerchant($inviteId, $this->merchant);

            $senderName = $this->getSenderName($input);

            $invitedUser = $this->repo->user->getUserFromEmail($invitation->getEmail());

            $allMerchantsForInvitedUser = optional($invitedUser)->merchants;

            $invitedUserExists = (empty($invitedUser) === false);

            $this->sendEmail($invitation, $senderName, $invitedUserExists, $allMerchantsForInvitedUser);

        }
        $arr = $this->merchant->invitations()->get()->callOnEveryItem('toArrayPublic');

        $updatedRows = $this->repo->invitation->updateIsDraftToFalse($inviteIdArray);

        $arr2 = $this->merchant->invitations()->get()->callOnEveryItem('toArrayPublic');

        if($updatedRows === count($inviteIdArray)){

            return 'Success';
        }

        else {
            return strval($inviteIdArray[0]).' '.strval($arr2[0]['id']).' '.strval($arr2[0]['is_draft']).' '.strval(count($arr)).'Some Internal server error occured'.strval(count($inviteIdArray)).'rows'.strval($updatedRows).strval('id').strval($arr[0]['id']).strval('is_draft').strval($arr[0]['is_draft']);
        }
    }

    /**
     * User can either accept or reject an invitation.
     * In both cases we need to delete the invitation.
     *
     * @param Entity $invitation
     * @param array  $input
     *
     * @return Entity
     */
    public function action(Entity $invitation, array $input): Entity
    {
        $invitation->edit($input, 'action');

        $action = $input[Entity::ACTION];

        $this->$action($invitation, $input[Entity::USER_ID]);

        $invitation->deleteOrFail();

        return $invitation;
    }

    /**
     * Accept an invitation.
     *
     * @param Entity $invitation
     * @param string $userId
     *
     * @throws Exception\BadRequestException
     */
    protected function accept(Entity $invitation, string $userId)
    {
        $updateParams = [
            Entity::ACTION           => User\Action::ATTACH,
            User\Entity::MERCHANT_ID => $invitation->getMerchantId(),
            User\Entity::ROLE        => $invitation->getRole(),
            Entity::PRODUCT          => $invitation->getProduct(),
        ];

        $user = $this->repo->user->findOrFailPublic($userId);

        $variant = $this->app->razorx->getTreatment(
            $this->app['request']->getId(),
            Merchant\RazorxTreatment::SECOND_FACTOR_AUTH_PROJECT_EXP,
            $this->mode
        );

        if (strtolower($variant) === 'on')
        {
            $merchantCollections = $user->merchants()->get();

            $merchantInvited = $this->repo->merchant->findOrFailPublic($invitation->getMerchantId());

            //
            // [ if the invitedUser is restricted (belongs to restricted merchant) ] or
            // [ merchant who has send the invitation becomes restricted
            //   and user is associated with any other merchant ]
            //   then invitation accept is not performed or will be failed.
            //
            if ((count($merchantCollections) > 0 and
                 $merchantInvited->getRestricted() === true) or
                $user->getRestricted() === true)
            {
                // delete the invitation
                $invitation->deleteOrFail();

                $this->trace->info(
                    TraceCode::INVITATION_ACCEPT_FAILED, [
                    Entity::MERCHANT_ID                       => $merchantInvited['id'],
                    'invited_user_merchant_count'             => count($merchantCollections),
                    'merchant_' . Merchant\Entity::RESTRICTED => $merchantInvited->getRestricted(),
                    'user_' . Merchant\Entity::RESTRICTED     => $user->getRestricted(),
                ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVITATION_ACCEPT_FAILED);
            }
        }

        $this->handleCallBacks($user, $invitation);

        try {
            $user = (new User\Core)->updateUserMerchantMapping($user, $updateParams);
        } catch (Exception\BadRequestException $ex) {

            // For the vendor portal merchant, we expect to have multiple invites for same user-merchant combination
            // We don't need to create a new entry in merchant_users table, we can just mark the invite as deleted.
            $vendorPortalMerchantId = $this->app['config']['applications.vendor_payments']['vendor_portal_merchant_id'];

            if (($ex->getCode() !== ErrorCode::BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS) ||
                ($invitation->getMerchantId() !== $vendorPortalMerchantId))
            {
                throw $ex;
            }
        }

        // We need to update the user in the invitation entity
        // Case 1: Invitation created for existing user
        //         In this case user_id would already be set
        // Case 2: Invitation created for non existent user
        //         In this case user_id would be not be set in create call,
        //         so we need to update the association on accepting the invite
        if ($invitation->getUserId() === null)
        {
            $invitation->user()->associate($user);
            $this->repo->saveOrFail($invitation);
        }

        $this->trace->info(
            TraceCode::INVITATION_ACCEPT,
            [
                'invitation' => $invitation->toArrayPublic(),
                'user_id'    => $userId
            ]);
    }

    /**
     * In case of reject we just need to delete the invitation which is done in calling function
     *
     * @param Entity $invitation
     * @param string $userId
     */
    protected function reject(Entity $invitation, string $userId)
    {
        $this->trace->info(
            TraceCode::INVITATION_REJECT,
            [
                'invitation' => $invitation->toArrayPublic(),
                'user_id'    => $userId
            ]);
    }

    protected function getSenderName(array $input)
    {
        if (empty($input[Entity::SENDER_NAME]) === true)
        {
            return $this->merchant->getName();
        }
        else
        {
            return $input[Entity::SENDER_NAME];
        }
    }

    protected function sendEmail(Entity $invitation, string $senderName, bool $invitedUserExists, Collection $allMerchantsForInvitedUser = null, bool $isIntegrationInvite = false)
    {
        $product = $invitation->getProduct();

        $org = OrgWiseConfig::getOrgDataForEmail($this->merchant);

        $this->trace->info(
            TraceCode::INVITATION_EMAIL,
            [
                'invitation_id' => $invitation->getId(),
                'sender_name'   => $senderName,
                'email'         => $invitation->getEmail(),
                'name'          => $this->merchant->getName(),
                'user_id'       => $invitation->getUserId(),
                'merchant_id'   =>  $this->merchant->getId(),
                'product'       => $product,
                'custom_code'   => $this->merchant->org->getCustomCode(),
            ]);

        if ($product === Product::PRIMARY)
        {

            $data = [
                'sender_name' => $senderName,
                'email'       => $invitation->getEmail(),
                'name'        => $this->merchant->getName(),
                'token'       => $invitation->getToken(),
                'user_id'     => $invitation->getUserId(),
                'merchant_id' => $this->merchant->getId(),
                'product'     => $product,
                'org'         => $org
            ];

            $invitationMail = new InvitationMail($data);

            Mail::queue($invitationMail);
        }
        elseif ($product === Product::BANKING)
        {
            $merchantIds = $this->repo->feature->findMerchantIdsHavingFeatures([Feature\Constants::RBL_BANK_LMS_DASHBOARD]);

            $isAnExistingUserOnX = $this->isExistingUserOnX($allMerchantsForInvitedUser);

            if (empty($merchantIds) === false && $this->merchant->getId() === $merchantIds[0])
                $inviteMailer = new BankLmsInvite($invitation->getId(), $senderName, $invitedUserExists, $isAnExistingUserOnX, $invitation->getRole());
            else
                $inviteMailer = new RazorpayXInvitationMail($invitation->getId(), $senderName, $invitedUserExists, $isAnExistingUserOnX, $invitation->getRole(), $isIntegrationInvite);

            Mail::queue($inviteMailer);
        }
    }

    protected function sendVendorPortalInviteEmail(Entity $invitation, string $contactId, bool $invitedUserExists)
    {
        $inviteMailer = new VendorPortalInvitationMail($invitation->getId(), $contactId, $invitedUserExists, $this->merchant);

        Mail::queue($inviteMailer);
    }

    private function handleCallBacks(User\Entity $user, Entity $invitation) {
        $product = $invitation[Entity::PRODUCT] ?? $this->app['basicauth']->getRequestOriginProduct();
        $merchantId = $invitation[Entity::MERCHANT_ID];

        if ($product === Product::BANKING)
        {
            if ($invitation[Entity::ROLE] == User\Role::VENDOR)
            {
                $this->vendorInvitationAcceptCallback($user, $invitation);
            }
            else
            {
                $this->trace->info(
                    TraceCode::CAPITAL_CARDS_INVITATION_ACCEPT_REQUEST,
                    [
                        'invitation' => $invitation->toArrayPublic(),
                        'user'    => $user->toArrayPublic()
                    ]);

                $this->invitationAcceptCallback($user->getId(), $user->getEmail(), $merchantId);

                $this->trace->info(
                    TraceCode::CAPITAL_CARDS_INVITATION_ACCEPT_RESPONSE,
                    [
                        'invitation' => $invitation->toArrayPublic(),
                        'user'    => $user->toArrayPublic()
                    ]);
            }
        }
    }

    public function vendorInvitationAcceptCallback(User\Entity $user, Entity $invitation)
    {
        $input = [
            'vendor_user_id' => $user->getPublicId(),
            'invite_token'   => $invitation->getToken(),
        ];

        $this->vendorPortalService->acceptInvite($input);
    }

    public function invitationAcceptCallback(string $userId, string $userEmail, string $merchantId) {
        $url = 'v1/addonapplications-accept';
        $method = 'PUT';
        $request = Request::instance();
        $body    = $request->all();
        $body['user_id'] = $userId;
        $body['email_id'] = $userEmail;
        $headers = [
            'X-Service-Name' => 'api',
            'X-Auth-Type' => 'internal',
            'x-merchant-id' => $merchantId
        ];
        $config = config('applications.capital_cards');
        $retryCount = 3;

        return $this->sendRequestAndParseResponse($url, $body, $headers, $method, $config, $retryCount, $retryCount);
    }

    private function sendRequestAndParseResponse(
        string $url,
        array $body,
        array $headers,
        string $method,
        array $config,
        int $retryOriginalCount,
        int $retryCount,
        array $options = [])
    {
        try
        {
            $baseUrl                 = $config['url'];
            $username                = $config['username'];
            $password                = $config['secret'];
            $timeout                 = $config['timeout'];
            $headers['Accept']       = 'application/json';
            $headers['Content-Type'] = 'application/json';
            $headers['X-Task-Id']    = $this->app['request']->getTaskId();
            $headers['Authorization'] = 'Basic '. base64_encode($username . ':' . $password);

            return $this->sendRequest($headers, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
        }
        catch (\Throwable $e)
        {
            if (($e instanceof \WpOrg\Requests\Exception) and
                ($this->checkRequestTimeout($e) === true) and
                ($retryCount > 0))
            {
                $this->trace->debug(TraceCode::CAPITAL_CARDS_INVITATION_ACCEPT_REQUEST, [
                    'request' => $url,
                ]);

                $retryCount--;

                return  $this->sendRequestAndParseResponse($url, $body, $headers, $method, $config, $retryOriginalCount, $retryCount);
            }
            {
                unset($headers['Authorization']);

                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::CAPITAL_CARDS_INVITATION_ACCEPT_REQUEST_FAILURE,
                    [
                        'body'       => $body,
                        'headers'    => $headers,
                        'retries'    => $retryOriginalCount - $retryCount
                    ]);
            }
        }
    }

    private function newRequest(array $headers, string $url, string $method, string $reqBody, string $contentType):
    RequestInterface
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $body = $streamFactory->createStream($reqBody);

        $req = $requestFactory->createRequest($method, $url);

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        return $req
            ->withBody($body)
            ->withHeader('Accept', $contentType)
            ->withHeader('Content-Type', $contentType);
    }

    private function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CAPITAL_CARDS_INVITATION_ACCEPT_REQUEST, [
            'url'     => $url,
            'method'  => $method,
        ]);

        $span = Tracer::startSpan(Requests::getRequestSpanOptions($url));
        $scope = Tracer::withSpan($span);
        $span->addAttribute('http.method', $method);

        $arrHeaders = new ArrayHeaders($headers);
        Tracer::injectContext($arrHeaders);
        $headers = $arrHeaders->toArray();

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $traceData = [
            'status_code'   => $resp->getStatusCode(),
        ];

        if ($resp->getStatusCode() >= 400)
        {
            $traceData['body'] = $resp->getBody();
        }

        $this->trace->info(TraceCode::CAPITAL_CARDS_INVITATION_ACCEPT_RESPONSE, $traceData);

        $span->addAttribute('http.status_code', $resp->getStatusCode());
        if ($resp->getStatusCode() >= 400)
        {
            $span->addAttribute('error', 'true');
        }

        $scope->close();

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    private function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        return ApiResponse::json($body, $code);
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Requests_Exception $e The caught requests exception
     *
     * @return boolean              true/false
     */
    protected function checkRequestTimeout(\WpOrg\Requests\Exception $e)
    {
        if ($e->getType() === 'curlerror')
        {
            $curlErrNo = curl_errno($e->getData());

            if ($curlErrNo === 28)
            {
                return true;
            }
        }
        return false;
    }

    private function pushSelfServeSuccessEventsToSegmentForMemberInvitation()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'New Member Invited';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    protected function isExistingUserOnX($allMerchantsForInvitedUser)
    {
        if (empty($allMerchantsForInvitedUser) === true)
        {
            return false;
        }

        foreach ($allMerchantsForInvitedUser as $merchantUserMap)
        {
            if (optional($merchantUserMap->pivot)->product === Constants::BANKING)
            {
                return true;
            }
        }

        return false;
    }

    public function createXAccountingIntegrationInvitation(array $input, bool $isSendMail): array
    {
        $user = $this->app['basicauth']->getUser();

        $data = [
            'to_email_id' => $input[Entity::EMAIL],
            'from_email_id' => $user->getEmail(),
            'merchant_id' => $this->merchant->getId(),
        ];

        $invite = $this->integrationInviteService->createOrUpdateInvitation($data);

        if ($isSendMail)
        {
            $senderName = $this->getSenderName($input);

            $this->sendXAccountingIntegrationInviteEmail($senderName,$input[Entity::EMAIL]);
        }

        return $invite;
    }

    public function resendXAccountingIntegrationInvites(string $toEmailId): array
    {

        $user = $this->app['basicauth']->getUser();

        $senderName = $this->merchant->getName();

        $invite = [
            "to_email_id" => $toEmailId,
            "from_email_id" => $user->getEmail(),
            "merchant_id" => $this->merchant->getId(),
        ];

        $this->sendXAccountingIntegrationInviteEmail($senderName,$toEmailId,true);

        return $invite;
    }

    protected function sendXAccountingIntegrationInviteEmail(string $senderName, string $toEmailId, bool $isReminder = false)
    {
        $inviteMailer = new XAccountingIntegrationInviteMail($senderName,$toEmailId,$isReminder);

        Mail::queue($inviteMailer);
    }

}
