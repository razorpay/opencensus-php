<?php

namespace RZP\Models\User;
use Request;
use Mail;
use Hash;
use Cache;
use Config;
use Carbon\Carbon;
use Lib\PhoneBook;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Jobs\LinkSubMerchant;
use RZP\Jobs\NotifyRas;
use RZP\Models\Base\PublicEntity;
use Illuminate\Hashing\BcryptHasher;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\User;
use RZP\Diag\EventCode;
use RZP\Models\User\Entity;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Models\OAuthToken;
use RZP\Models\Invitation;
use Razorpay\Trace\Logger;
use RZP\Constants\Country;
use RZP\Models\Admin\Admin;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Models\DeviceDetail;
use RZP\Mail\User as UserMail;
use RZP\Models\User\Constants;
use RZP\Services\HubspotClient;
use RZP\Models\Admin\AdminLead;
use RZP\Models\Merchant\Account;
use RZP\Models\Partner;
use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\BusinessDetail as MBD;
use RZP\Jobs\PartnerSubmerchantLinkingOauthJob;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\MerchantApplications\Repository as MerchantAppRepo;
use RZP\Jobs\PartnerSubmerchantLinkingReferralJob;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Models\DeviceDetail\Entity as DeviceDetailEntity;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\DeviceDetail\Constants as DeviceDetailConstants;
use RZP\Models\OAuthApplication\Constants as OAuthApplicationConstants;
use RZP\Models\User\RateLimitLoginSignup\Facade as LoginSignupRateLimit;
use RZP\Constants\Mode;
use RZP\Services\Dcs\Configurations\Constants as DcsConstants;
use RZP\User\Constants as UserConstants;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Environment;
use RZP\Models\Base\PublicCollection;

use function Clue\StreamFilter\append;

class Service extends Base\Service
{
    protected $core;

    protected $validator;

    protected $merchantService;

    protected $m2mReferralService;

    protected $pgosProxyController;

    protected $ba;

    public function __construct(Core $core = null, Validator $validator = null, Merchant\Service $merchantService = null,
                                Merchant\M2MReferral\Service $m2mReferralService = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->validator = $validator ?? new Validator();

        $this->merchantService = $merchantService ?? new Merchant\Service();

        $this->m2mReferralService = $m2mReferralService ?? new Merchant\M2MReferral\Service();

        $this->pgosProxyController = new MerchantOnboardingProxyController();

        $this->elfin = $this->app['elfin'];

        $this->ba = $this->app['basicauth'];
    }

    /**
     * This method creates user and merchant and sends segment event for resting password
     * This is only getting used for rbl co-created
     *
     * @throws BadRequestException
     * @throws \Throwable
     */
    public function registerInternal(array $input): array
    {
        $input[Entity::CAPTCHA_DISABLE] = User\Validator::DISABLE_CAPTCHA_SECRET;

        $response =  $this->register($input, 'create', false);

        $merchant = $this->repo->merchant->findByPublicId($response['id']);

        $this->merchantService->storeMerchantCaOnboardingFlow($merchant, Merchant\service::RBL_CO_CREATED);

        $this->user = $this->repo->user->find($response['user_id']);

        $this->setResetPasswordTokenAndSendEmail();

        return $response;
    }

    public function isUserOrgAllowedSegregatedLoginSignup(): bool
    {
        // Org ids will be added as they adopt USL
        $allowedOrgIds = [
            Org\Entity::RAZORPAY_ORG_ID,
            Org\Entity::HDFC_ORG_ID,
            Org\Entity::AXIS_ORG_ID,
            Org\Entity::YES_ORG_ID,
            Org\Entity::INDUS_ORG_ID,
            Org\Entity::IDFC_ORG_ID,
        ];

        $orgId = $this->app['basicauth']->getOrgId();

        if(empty($orgId) === true)
        {
            $orgId = Org\Entity::RAZORPAY_ORG_ID;
        }

        // Validate and process org ID
        try {
            Org\Entity::verifyIdAndSilentlyStripSign($orgId);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::ORG_ID_VERIFICATION_FAILED, [
                "orgId" => $orgId,
                "error" => $e->getMessage(),
            ]);
            return false;
        }

        if (in_array($orgId, $allowedOrgIds)) {
            $this->trace->info(TraceCode::USER_ORG_ALLOWED_IN_SEPARATED_LOGIN_SIGNUP, [
                "orgId"          => $orgId,
            ]);

            return true;
        }

        $this->trace->info(TraceCode::USER_ORG_NOT_ALLOWED_IN_SEPARATED_LOGIN_SIGNUP, [
            "orgId"          => $orgId,
        ]);

        return false;
    }

    public function register(array $input, string $operation = 'create', bool $sendConfirmation = true): array
    {
        (new Entity)->getValidator()->setStrictFalse()->validateInput('country_code', $input);

        $this->traceRegisterInput($input);

        $m2mReferralInput = $this->m2mReferralService->extractFriendBuyParams($input);

        $referrer = $input['ref'] ?? '';

        $businessName = $input['business_name'] ?? '';
        $userOnly = $input[Entity::USER_ONLY] ?? false;
        $shouldCreateOnlyUser = $userOnly && $this->isUserOrgAllowedSegregatedLoginSignup();

        $partnerIntent = $input[Merchant\Constants::PARTNER_INTENT] ?? false;

        $this->trace->count(Merchant\Metric::SIGNUP_TOTAL);

        $this->app->hubspot->trackSignupEvent($input);

        $partnerInvitation  = $this->handleUserInvitation($input);
        $user               = $partnerInvitation['user'];
        $invitation         = $partnerInvitation['invitation'];
        $invitationToken    = $partnerInvitation['invitationToken'];

        $signupCampaign = $input[DeviceDetail\Entity::SIGNUP_CAMPAIGN] ?? null;

        $signupSource = $input[DeviceDetail\Entity::SIGNUP_SOURCE] ?? null;

        $source = array_pull($input, 'source') ?? null;

        unset($input[DeviceDetail\Entity::SIGNUP_CAMPAIGN]);

        $heimdallTokenData = $this->handleHeimdallInvitation($input, $user);

        $countryCode = $input['country_code'] ?? 'IN';

        if (empty($input[Entity::OAUTH_PROVIDER]) === false)
        {
            $this->core->verifyOauthIdToken($input);
        }

        /**
         * $user would not be null in a very rare edge case here
         * which happens when two subsequent invitations without either being
         * accepted. Once the second one is accepted, this block
         * is ignored and the $user found above will be used
         */
        if (empty($user) === true)
        {
            if (empty($input[Entity::PASSWORD]) === false && empty($input[Entity::OAUTH_PROVIDER]) === true)
            {
                $input[Entity::PASSWORD_CONFIRMATION] = $input[Entity::PASSWORD];
            }

            $input[Entity::NAME] = $input[Entity::NAME] ?? '';

            if (isset($input[Entity::CONTACT_MOBILE]) === true)
            {
                if ($this->core()->checkIfMobileAlreadyExists($input[Entity::CONTACT_MOBILE]) === true)
                {
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS);
                }
                $input[Entity::SIGNUP_VIA_EMAIL] = 0;
            }
            else
            {
                $input[Entity::SIGNUP_VIA_EMAIL] = 1;
            }

            unset($input['ref']);

            unset($input['business_name']);

            if (empty($invitation) === false)
            {
                $input['invitation'] = $invitation;
            }

            $user = $this->create($input, $operation);
        }

        /**
         * User Email wil be confirmed as it is coming from oauth
         *  which is already confirmed by oauth provider
         */
        if (empty($input[Entity::OAUTH_PROVIDER]) === false)
        {
            // log user with timestamp for oauth provider.
            $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $this->trace->info(TraceCode::USER_OAUTH_PROVIDER_REGISTER,
                               ['email'            => $user[Entity::EMAIL] ?? null,
                                'user_id'          => $user[Entity::ID],
                                'currentTimestamp' => $currentTimestamp,
                                'oauth_provider'   => $input[Entity::OAUTH_PROVIDER]]);

            $this->confirm($user[Entity::ID]);
        }

        $sendUslSalesforceEvent = $this->shouldSendUslSalesforceEvent($user[Entity::ID]);

        if ($sendUslSalesforceEvent === true) {

            $utmParams = $this->getUtmParameters();

            $salesForcePayload = [
                'user_id' => $user[Entity::ID],
                'email' => $user[Entity::EMAIL] ?? 'NA',
                'contact_mobile' => $user[Entity::CONTACT_MOBILE] ?? 'NA',
                'utm_params' => $utmParams,
            ];

            $this->app->salesforce->sendUslCreateUserAndMerchantDetails($salesForcePayload);
        }

        /**
         * These two conditions are exclusive
         * One cannot accept an invitation and create a merchant account at the same time
         */
        if (empty($invitationToken) === false)
        {
            $this->acceptInvite($user, $invitation, $source);
            $data = ['login'=>true];
        }
        else if ($shouldCreateOnlyUser)
        {
            $this->trace->info(
                TraceCode::USER_ONLY_REGISTRATION,
                [
                    'userId'          => $user[Entity::ID],
                ]);

            $userEntity = $this->repo->user->findOrFailPublic($user[Entity::ID]);

            return $this->sendConfirmationMailIfApplicable($userEntity, null, true);
        }
        else
        {
            $input[DeviceDetail\Entity::SIGNUP_SOURCE] = $signupSource;

            $data = $this->createMerchant($user, $referrer, $businessName, $countryCode, $partnerIntent, $input, $heimdallTokenData, $sendConfirmation);

            $merchantId = $data['id'];

            if ($sendUslSalesforceEvent === true) {

                $merchantCreateSalesForcePayload = [
                    'user_id' => $user[Entity::ID],
                    'merchant_id' => $merchantId,
                    'country_code' => $countryCode,
                    'product' => $input[Merchant\Entity::PRODUCT] ?? $this->auth->getRequestOriginProduct(),
                ];

                $this->app->salesforce->sendUslCreateUserAndMerchantDetails($merchantCreateSalesForcePayload);
            }

            if (empty($signupCampaign) === false)
            {
                $ddInput = [
                    DeviceDetail\Entity::MERCHANT_ID        => $merchantId,
                    DeviceDetail\Entity::USER_ID            => $user['id'],
                    DeviceDetail\Entity::SIGNUP_CAMPAIGN    => $signupCampaign,
                ];

                (new DeviceDetail\Core)->createDeviceDetail($ddInput);
            }
            // Decomposition Plan: Once the merchant and user creation processes are decoupled,
            // the creation of a sales user associated with a newly created merchant will be handled as part of the merchant creation flow.
            if ($this->shouldUpdateUserMerchantMapping($signupCampaign))
            {
                $userMerchantMappingInputData = [
                    'action' => 'attach',
                    'role' => Role::RAZORPAY_SALES,
                    'merchant_id' => $merchantId,
                ];
                $loggedInUser = $this->app['basicauth']->getUser();
                $this->updateUserMerchantMapping($loggedInUser['id'], $userMerchantMappingInputData);

                if ($signupCampaign === DeviceDetailConstants::PARTNER_ASSISTED_ONBOARDING)
                {
                    $loggedInMerchant = $this->app['basicauth']->getMerchant();
                    (new Merchant\Service())->mapSubmerchant($loggedInMerchant, $merchantId);
                }
            }

            try {
                if (($operation == 'createOauth' or $input[Entity::SIGNUP_VIA_EMAIL] === 1)
                     and (empty($signupCampaign) === false))
                {
                    $merchant = $this->repo->merchant->findOrFail($merchantId);

                    $this->handlePGOSOnboardingForOAuthMerchants($merchant, $signupCampaign, $input, $user);
                }
            } catch (\Throwable $exception) {
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'message'       => "Error in handlePGOSOnboardingForOAuthMerchants()",
                    'merchant_id'   => $merchantId,
                    'error_message' => $exception->getMessage()
                ]);
            }


        }

        $signupMethod = Constants::PASSWORD;

        $this->signUpSuccess($user, $partnerIntent, $signupMethod,$m2mReferralInput);

        $referralCode = $input[Constants::PARTNER_REFERRAL_CODE];

        if ($referralCode !== null)
        {
            $this->processReferralCode($data['id'], $referralCode);

        }
        return $data;
    }

    public function shouldSendUslSalesforceEvent(string $user_id): bool
    {
        if (empty($user_id) === true)
        {
            return false;
        }

        try
        {
            $response = $this->app['splitzService']->evaluateRequest([
                'id'            => $user_id,
                'experiment_id' => $this->app['config']->get('app.send_usl_salesforce_event_exp_id'),
            ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, ['id' => $properties['id'] ?? null]);
            return false;
        }

        $variant = $response['response']['variant']['name'] ?? null;

        return $variant === 'enable';
    }

    public function isMerchantAllowedForMigration(string $merchant_id): bool
    {
        if (empty($merchant_id) === true)
        {
            return false;
        }

        try
        {
            $response = $this->app['splitzService']->evaluateRequest([
                'id'            => $merchant_id,
                'experiment_id' => $this->app['config']->get('app.user_role_migration_for_x_exp_id'),
            ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, ['id' => $properties['id'] ?? null]);
            return false;
        }

        $variant = $response['response']['variant']['name'] ?? null;

        return $variant === 'enable';
    }

    public function changeBankingUserRole(array $input): array
    {
        // currently any private auth can also be accessed via partner auth creds too.
        // incase request is made via partner auth creds, then we need to get merchant_id from different function
        // and if request came via private auth then other function
        $merchantId = $this->auth->isPartnerAuth() ? $this->auth->getPartnerMerchantId() : $this->auth->getMerchantId();

        if ($this->isMerchantAllowedForMigration($merchantId) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ROUTE_DISABLED);
        }

        $this->trace->info(TraceCode::USER_ROLE_FOR_X_MIGRATION_START, ['merchant_id' => $merchantId]);

        $ignoredUsers = [];
        $affectedUsers = [];
        $usersList = $input['users_list'] ?? [];

        foreach ($usersList as $user)
        {
            $merchant = $this->repo->merchant->find($user['merchant_id']);

            if (empty($merchant) === true)
            {
                $ignoredUsers[] = $user;
                $this->trace->info(TraceCode::USER_ROLE_FOR_X_MIGRATION_UPDATE_INVALID_INPUT, [
                    'user' => $user,
                    'reason' => 'MerchantNotFound',
                ]);
                continue;
            }

            $mapping = $this->repo->merchant->getMerchantUserMapping($user['merchant_id'], $user['user_id'], null, 'banking');

            if (empty($mapping) === true)
            {
                $ignoredUsers[] = $user;

                $this->trace->info(TraceCode::USER_ROLE_FOR_X_MIGRATION_UPDATE_INVALID_INPUT, [
                    'user' => $user,
                    'reason' => 'MappingNotFound',
                ]);

                continue;
            }

            $existingRole = $mapping->pivot->role;

            if ($existingRole === $user['role'])
            {
                $ignoredUsers[] = $user;

                $this->trace->info(TraceCode::USER_ROLE_FOR_X_MIGRATION_UPDATE_INVALID_INPUT, [
                    'user' => $user,
                    'reason' => 'ExistingRoleSameAsInput',
                ]);

                continue;
            }

            $update = [
                'user_id'     => $user['user_id'],
                'merchant_id' => $user['merchant_id'],
                'product'     => 'banking',
                'old_role'    => $existingRole,
                'new_role'    => $user['role'],
            ];

            $this->trace->info(TraceCode::USER_ROLE_FOR_X_MIGRATION_UPDATE, $update);

            $this->updateUserMerchantMapping($user['user_id'], [
                'action'      => 'update',
                'role'        => $user['role'],
                'merchant_id' => $user['merchant_id'],
                'product'     => 'banking',
            ]);

            $affectedUsers[] = $update;
        }

        return [
            'affected_users' => $affectedUsers,
            'ignored_users'  => $ignoredUsers,
        ];
    }

    protected function handleUserInvitation(array &$input): array
    {
        /*
         * If we have an invitation token, the user may have created an account
         * in the meantime. $user will be equal to the user with the same email
         * as the invited user
         */
        $invitationToken = $input['invitation'] ?? null;
        $invitation = null;
        $user = null;

        if (empty($invitationToken) === false)
        {
            $invitation = (new Invitation\Service)->fetchByToken($invitationToken);

            $user = $this->core->getUserFromEmail($invitation);

            // Since input would be lacking an email in case of registration via the invitation
            $input[Entity::EMAIL] = $invitation[Invitation\Entity::EMAIL];

            unset($input['invitation']);
        }

        return ['user' => $user, 'invitation' => $invitation, 'invitationToken' => $invitationToken];
    }

    protected function handleHeimdallInvitation(array &$input, &$user = null)
    {
        $heimdallInvitationToken = $input['merchant_invitation'] ?? null;

        $heimdallTokenData = null;

        if (empty($heimdallInvitationToken) === false)
        {
            // Check if this token is valid or not
            $heimdallTokenData = (new AdminLead\Service)->verify($heimdallInvitationToken);

            $input['token_data'] = $heimdallTokenData;

            if (isset($heimdallTokenData['id']) === true)
            {
                $tokenSignUpInput = [AdminLead\Entity::SIGNED_UP => 1];

                (new AdminLead\Service)->editInvitation(
                    $heimdallTokenData[AdminLead\Entity::ORG_ID], $heimdallTokenData[AdminLead\Entity::ID], $tokenSignUpInput);

                $this->isUserExistsForCustomInvite($heimdallTokenData, $user);
            }

            unset($input['merchant_invitation']);

            if(!isset($input[Merchant\Entity::COUNTRY_CODE]) &&
                isset($heimdallTokenData['form_data']) && isset($heimdallTokenData['form_data'][Merchant\Entity::COUNTRY_CODE])){
                $input[Merchant\Entity::COUNTRY_CODE] = $heimdallTokenData['form_data'][Merchant\Entity::COUNTRY_CODE];
            }
        }
        return $heimdallTokenData;

    }

    protected function signUpSuccess($user, $partnerIntent, $signupMethod,$m2mReferralInput=null, $merchantId = '')
    {
        if (empty($m2mReferralInput))
        {
            $isM2MReferral = false;
        }
        else
        {
            $isM2MReferral = $this->m2mReferralService->sendSignUpEventIfApplicable($user[Entity::ID], $m2mReferralInput);
        }

        $visitorId = $this->fetchVisitorIdFromCookie();

        $merchant = $this->repo->user->findOrFailPublic($user[Entity::ID])->getMerchantEntity($merchantId);

        if (empty($merchant) === true) return;

        if (empty($merchant) === false) {
            $customProperties = [
                Entity::EMAIL                          => $user[Entity::EMAIL] ?? null,
                Entity::VISITOR_ID                     => $visitorId,
                Merchant\Constants::PARTNER_INTENT     => $partnerIntent,
                'is_m2m_referral'                      => $isM2MReferral,
                'phone'                                => $user[Entity::CONTACT_MOBILE] ?? "",
                'easyOnboarding'                       => optional($merchant)->isSignupCampaign(DDConstants::EASY_ONBOARDING) === true,
                Merchant\Constants::PHANTOM_ONBOARDING => optional($merchant)->isSignupCampaign(DDConstants::PHANTOM_ONBOARDING) === true,
                Merchant\Constants::I18N_MY_ONBOARDING    => optional($merchant)->isSignupCampaign(DDConstants::I18N_MY_SIGNUP) === true,
                DeviceDetail\Constants::SINGAPORE_SIGNUP  => optional($merchant)->isSignupCampaign(DeviceDetail\Constants::SINGAPORE_SIGNUP) === true
            ];
        }

        if ($user[Entity::SIGNUP_VIA_EMAIL] == 0)
        {
            $signupMedium = Constants::CONTACT_MOBILE;
        }
        else
        {
            $signupMedium = Constants::EMAIL;
        }

        $this->trace->count(
            Metric::USER_SIGNUP,
            [
                Constants::METHOD => $signupMethod,
                Constants::MEDIUM => $signupMedium,
            ]
        );

        $merchant = $this->pushSegmentSignupEvent($user[Entity::ID], $customProperties);

        if (empty($merchant) === false) {
            $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_CREATE_ACCOUNT_SUCCESS, $merchant, null, $customProperties);

            $this->notifyRasOnSignup($merchant, $customProperties, $user);
        }
    }

    protected function notifyRasOnSignup($merchant, $customProperties, $user)
    {
        try
        {
            $rasAlertRequest = [
                'merchant_id'     => $merchant->getId(),
                'entity_type'     => 'merchant',
                'entity_id'       => $merchant->getId(),
                'category'        => Constants::RAS_SIGN_UP_CATEGORY,
                'source'          => Constants::RAS_SIGN_UP_SOURCE,
                'event_type'      => Constants::RAS_SIGN_UP_EVENT_TYPE,
                'event_timestamp' => (string) Carbon::now()->getTimestamp(),
                'data'            => [
                    'contact_email'      => $customProperties[Entity::EMAIL],
                    'contact_mobile'     => $user[Entity::CONTACT_MOBILE] ?? null,
                    'business_type'      => $merchant->merchantDetail->getBusinessType(),
                    'transaction_volume' => $merchant->merchantDetail->getTransactionVolume(),
                    'client_id'          => $customProperties[Entity::VISITOR_ID],
                    'client_ip'          => $this->app['request']->getClientIp(),
                ],
            ];

            NotifyRas::dispatch($this->mode, $rasAlertRequest);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::MERCHANT_SIGNUP_COMPLETE_RAS_NOTIFICATION_FAILED,
                [
                    'contact_email'      => $customProperties[Entity::EMAIL],
                    'contact_mobile'     => $user[Entity::CONTACT_MOBILE] ?? null,
                    'category'           => Constants::RAS_SIGN_UP_CATEGORY,
                    'event_type'         => Constants::RAS_SIGN_UP_EVENT_TYPE,
                ]
            );
        }
    }

    protected function acceptInvite($user, array $invitation = null, string $source = null)
    {
        $invitationAcceptInput = [
            Invitation\Entity::USER_ID => $user[Entity::ID],
            Invitation\Entity::ACTION  => 'accept',
            Invitation\Entity::EMAIL   => $user[Entity::EMAIL],
        ];

        if ($source === Invitation\Constants::ACCEPT_INVITE_SROUCE_VENDOR_PORTAL_V2)
        {
            (new Invitation\Service)->handleVendorPortalV2Invitation($invitation[Invitation\Entity::ID], $invitationAcceptInput);
        }
        else
        {
            (new Invitation\Service)->action($invitation[Invitation\Entity::ID], $invitationAcceptInput);
        }

        $this->confirm($user[Entity::ID]);

        $this->core->subscribeToMailingList($user);
    }

    protected function createMerchant(array $user, string $referrer, string $businessName, String $countryCode, bool $partnerIntent, array $input, $heimdallTokenData, bool $sendConfirmation, $isInternal=false): array
    {
        // set orgID from auth if it is not internal request
        // set orgID from payload if it is internal request
        $orgID = $this->auth->getOrgId();
        if ($isInternal)
        {
            $orgID = $input[Merchant\Entity::ORG_ID];
        }

        $requestedProduct =$input['product']??null;

        //For X on USL if requested product is banking_onboarding then setting the originproduct and
        //x_verify_email flag(used at multiple places for X) to true
        if($requestedProduct == DeviceDetailConstants::PRODUCT_BANKING_ONBOARDING) {

            $this->auth->setRequestOriginProduct(Product::BANKING);
            $input[Entity::X_VERIFY_EMAIL]="true";
        }

        $this->trace->info(TraceCode::USER_REGISTER, [
            'signup_source'          =>$input[DeviceDetail\Entity::SIGNUP_SOURCE],
            'signup_campaign'        =>$input[DeviceDetail\Entity::SIGNUP_CAMPAIGN],
            'merchant_product'       =>$this->auth->getRequestOriginProduct(),
            'x_verify_email'         =>$input[Entity::X_VERIFY_EMAIL] ?? null,
        ]);

        $merchantInputData = [
            Merchant\Entity::NAME          => $businessName,
            Merchant\Entity::SIGNUP_SOURCE => $input[DeviceDetail\Entity::SIGNUP_SOURCE] ??
                                              $this->auth->getRequestOriginProduct(),
            Merchant\Entity::COUNTRY_CODE  => $countryCode ?? 'IN',
            //Set by default OrgId to the OrgId from whether request is originated, Ex: Razorpay/Curlec,
            Merchant\Entity::ORG_ID        => $orgID,
        ];

        $merchantDetailInputData = [];

        if (isset($user[Entity::EMAIL]) === true)
        {
            $merchantInputData[Merchant\Entity::EMAIL]            = $user[Entity::EMAIL];
        }

        if (isset($user[Entity::CONTACT_MOBILE]) === true)
        {
            $merchantDetailInputData[Entity::CONTACT_MOBILE]      = $user[Entity::CONTACT_MOBILE];
        }

        $merchantInputData[Merchant\Entity::SIGNUP_VIA_EMAIL] = $user[Entity::SIGNUP_VIA_EMAIL] === 1 ? 1 : 0;

        if (isset($input[Merchant\Constants::PARTNER_INTENT]))
        {
            $merchantInputData[Merchant\Constants::PARTNER_INTENT] = $partnerIntent;
        }

        if (empty($heimdallTokenData) === false)
        {
            // Merchant belongs to the same org that the inviting admin does
            $merchantInputData[Merchant\Entity::ORG_ID] = $heimdallTokenData[AdminLead\Entity::ORG_ID];
            // Map merchant to the admin that generated his lead (invited merchant to sign up)
            $merchantInputData[Merchant\Entity::ADMINS] = [$heimdallTokenData[AdminLead\Entity::ADMIN_ID]];
            // set token data as we need it to validate merchant signup
            $merchantDetailInputData['token_data'] = $heimdallTokenData;
        }

        $sendOtpEmail = filter_var($this->app['request']->header(RequestHeader::X_SEND_EMAIL_OTP, false),
                                   FILTER_VALIDATE_BOOLEAN);

        // Remove this when signup experiment for X is ramped up as we can find the
        // template just from the product origin
        $isRequestFromXVerifyEmail = $this->isRequestFromXVerifyEmail($input);

        $inputData = ["isRequestFromXVerifyEmail" => $isRequestFromXVerifyEmail];

        $this->skipEmailUniquenessCheckForCustomInvite($user ,$merchantInputData, $inputData);

        return $this->createMerchantFromUser(
            $merchantInputData,
            $user,
            $referrer,
            $sendOtpEmail,
            $inputData,
            $sendConfirmation,
            $merchantDetailInputData
        );
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function registerWithOtp(array $input): array
    {
        //TODO: @kartik.sayani - Is this needed with otp signups?
        $invitationToken = $input['invitation'] ?? null;

        if (empty($invitationToken) === false)
        {
            $invitation = (new Invitation\Service)->fetchByToken($invitationToken);

            if (empty($invitation[Invitation\Entity::EMAIL]) === false)
            {
                // Since input would be lacking an email in case of registration via the invitation
                $input[Entity::EMAIL] = $invitation[Invitation\Entity::EMAIL];
            }
        }

        return $this->core->registerWithOtp($input);
    }

    public function sendOtpSalesforce(array $input): array
    {
        return $this->core->sendOtpSalesforce($input);
    }

    public function checkUserExists($input)
    {
        return $this->core->checkUserExists($input);
    }

    public function sendEmailOtp($input)
    {
        return $this->core->sendEmailOtp($input);
    }

    public function verifyEmailOtp($input)
    {
        return $this->core->verifyEmailOtp($input);
    }

    public function verifyOtpSalesforce(array $input): array
    {
        $verifySuccess = $this->core->verifySalesforceOtp($input);

        if($verifySuccess)
        {
            unset($input["contact_mobile"]);
            unset($input["token"]);
            unset($input["otp"]);

            $input['Verified__c'] = true;
            return $this->sendUserDetailsToSalesForceEvent($input);
        }
    }

    public function verifySignupOtp(array $input, string $operation = 'createOTPSignup'): array
    {
        (new Entity)->getValidator()->setStrictFalse()->validateInput('country_code', $input);

        $response = [];
        $this->trace->count(Merchant\Metric::SIGNUP_TOTAL);

        $m2mReferralInput = $this->m2mReferralService->extractFriendBuyParams($input);

        $signupCampaign = $input[DeviceDetail\Entity::SIGNUP_CAMPAIGN] ?? null;
        unset($input[DeviceDetail\Entity::SIGNUP_CAMPAIGN]);

        $isPhantomOnboardingFlow = Merchant\PhantomUtility::checkIfPhantomOnBoardingFlow($input);

        $partnerReferralCode = $input['partner_referral_code'] ??'';
        $sourceAppId         = $input['source_app_id'] ?? '';

        $isOauthReferral      = $input['oauth_referral']?? false;

        unset($input['partner_referral_code']);
        unset($input['source_app_id']);
        unset($input['oauth_referral']);

        $this->trace->info(TraceCode::PARTNER_REFERRAL_VERIFY_OTP_REQUEST, [
            'partner_referral_code' => $partnerReferralCode,
            'source_app_id'         => $sourceAppId,
            'oauth_referral'        => $isOauthReferral,
            'is_phantom'            => $isPhantomOnboardingFlow
        ]);

        $verifySuccess = $this->core->verifySignupOtp($input);

        unset($input[Entity::SKIP_SMS_REQUEST]);

        // If signup campaign is Assisted onboarding avoid creating api merchant, if workflow creation get failed will throw error
        if ($this->isAssistedOnboardingSignupCampaign($signupCampaign)){
            // Generate unique identifiers for the user and merchant
            $uniqueUserId = UniqueIdEntity::generateUniqueId();
            $uniqueMerchantId = UniqueIdEntity::generateUniqueId();

            $loggedInUserEmail = "";
            if ($this->app['basicauth'] !== null && $this->app['basicauth']->getUser() !== null){
                $loggedInUserEmail = $this->app['basicauth']->getUser()->getEmail();
            }

            $countryCode = $input['country_code'] ?? 'IN';

            // Store the generated unique IDs in the configuration
            Config::set(Constants::USER_ID, $uniqueUserId);
            Config::set(Constants::MERCHANT_ID, $uniqueMerchantId);

            try
            {
                $this->handlePGOSOnboardingForAssistedMerchant($uniqueMerchantId, $signupCampaign, $countryCode, $input, $uniqueUserId, $loggedInUserEmail);
            }
            catch (\Throwable $exception)
            {
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'message'        => "Error in handlePGOSOnboarding()",
                    'merchant_id'    => $uniqueMerchantId,
                    'signupCampaign' => $signupCampaign,
                    'error_message'  => $exception->getMessage()
                ]);

                throw new Exception\BadRequestValidationFailureException(ErrorCode::ASSISTED_WORKFLOW_CREATION_FAILED);
            }
        }

        list($merchant, $countryCode, $user) = $this->repo->transactionOnLiveAndTestAndAsv(function() use ($input, $signupCampaign, $m2mReferralInput, $verifySuccess, $operation, $isPhantomOnboardingFlow, &$response, $partnerReferralCode, $sourceAppId, $isOauthReferral) {

            if ($verifySuccess === true)
            {
                $userOnly = $input[Entity::USER_ONLY] ?? false;
                $shouldCreateOnlyUser = $userOnly && $this->isUserOrgAllowedSegregatedLoginSignup() && !$this->isAssistedOnboardingSignupCampaign($signupCampaign);
                $merchant = null;

                $referrer = $input['ref'] ?? '';
                $businessName = $input['business_name'] ?? '';

                $partnerIntent = $input[Merchant\Constants::PARTNER_INTENT] ?? false;

                $heimdallTokenData = $this->handleHeimdallInvitation($input);

                $countryCode = $input['country_code'] ?? 'IN';

                $input[Entity::NAME] = $input[Entity::NAME] ?? '';

                if (isset($input[Entity::CONTACT_MOBILE]) === true) {
                    $input[Entity::SIGNUP_VIA_EMAIL] = 0;
                } else {
                    $input[Entity::SIGNUP_VIA_EMAIL] = 1;
                }

                $businessDetailsInput = [];

                $paymentsAvenueInput = [
                    MBD\Constants::SOCIAL_MEDIA,
                    MBD\Constants::PHYSICAL_STORE,
                    MBD\Constants::WEBSITE_OR_APP,
                    MBD\Constants::OTHERS
                ];

                foreach ($paymentsAvenueInput as $payInput)
                {
                    if (isset($input[$payInput]) === true)
                    {
                        $businessDetailsInput[MBD\Entity::WEBSITE_DETAILS][$payInput] = $input[$payInput];
                        unset($input[$payInput]);
                    }
                }

                unset($input['ref']);

                unset($input['business_name']);

                unset($input['country_code']);

                $user = $this->create($input, $operation);

                $sendUslSalesforceEvent = $this->shouldSendUslSalesforceEvent($user[Entity::ID]);

                if ($sendUslSalesforceEvent === true) {

                    $utmParams = $this->getUtmParameters();

                    $salesForcePayload = [
                        'user_id' => $user[Entity::ID],
                        'email' => $user[Entity::EMAIL] ?? 'NA',
                        'contact_mobile' => $user[Entity::CONTACT_MOBILE] ?? 'NA',
                        'utm_params' => $utmParams,
                    ];

                    $this->app->salesforce->sendUslCreateUserAndMerchantDetails($salesForcePayload);
                }

                $userEntity = $this->repo->user->findByPublicId($user[Entity::ID]);

                $this->core->setContactMobileOrEmailVerify($input, $userEntity);

                if ($shouldCreateOnlyUser === true) {
                    $this->trace->info(
                        TraceCode::USER_ONLY_REGISTRATION,
                        [
                            'userId'          => $user[Entity::ID],
                        ]);
                }
                else
                {
                    $merchantData = $this->createMerchant($user, $referrer, $businessName, $countryCode, $partnerIntent, $input, $heimdallTokenData, false);

                    if ($sendUslSalesforceEvent === true) {

                        $merchantCreateSalesForcePayload = [
                            'user_id' => $user[Entity::ID],
                            'merchant_id' => $merchantData['id'],
                            'country_code' => $countryCode,
                            'product' => $input[Merchant\Entity::PRODUCT] ?? $this->auth->getRequestOriginProduct(),
                        ];

                        $this->app->salesforce->sendUslCreateUserAndMerchantDetails($merchantCreateSalesForcePayload);
                    }

                    $merchant = $this->repo->merchant->findOrFailPublic($merchantData['id']);

                    if (empty($signupCampaign) === false)
                    {
                        $deviceDetailInput = [
                            DeviceDetail\Entity::MERCHANT_ID => $merchantData['id'],
                            DeviceDetail\Entity::USER_ID => $user['id'],
                            DeviceDetail\Entity::SIGNUP_CAMPAIGN => $signupCampaign,
                            DeviceDetail\Entity::METADATA => [
                                DeviceDetailConstants::SERVICE => ($this->isAssistedOnboardingSignupCampaign($signupCampaign) ? DeviceDetailConstants::SERVICE_PGOS : DeviceDetailConstants::SERVICE_API)
                            ]
                        ];

                        (new DeviceDetail\Core)->createDeviceDetail($deviceDetailInput);
                    }

                    // Decomposition Plan: Once the merchant and user creation processes are decoupled,
                    // the creation of a sales user associated with a newly created merchant will be handled as part of the merchant creation flow.
                    if ($this->shouldUpdateUserMerchantMapping($signupCampaign))
                    {
                        $userMerchantMappingInputData = [
                            'action' => 'attach',
                            'role' => Role::RAZORPAY_SALES,
                            'merchant_id' => $merchantData['id'],
                        ];
                        $loggedInUser = $this->app['basicauth']->getUser();

                        $this->updateUserMerchantMapping($loggedInUser['id'], $userMerchantMappingInputData);

                        if ($signupCampaign === DeviceDetailConstants::PARTNER_ASSISTED_ONBOARDING)
                        {
                            $loggedInMerchant=  $this->app['basicauth']->getMerchant();
                            (new Merchant\Service())->mapSubmerchant($loggedInMerchant, $merchantData['id']);
                        }
                    }

                    if (empty($businessDetailsInput[MBD\Entity::WEBSITE_DETAILS]) === false)
                    {
                        (new Merchant\BusinessDetail\Service)->saveBusinessDetailsForMerchant($merchantData['id'], $businessDetailsInput);
                    }

                    $signupMethod = Constants::OTP;
                    $this->signUpSuccess($user, $partnerIntent, $signupMethod, $m2mReferralInput);
                    $this->processReferralCode($merchantData['id'], $partnerReferralCode);
                    $this->linkSubMerchantToPlatformPartnerWithRetry($merchantData['id'], $sourceAppId, $isOauthReferral);
                    $this->createSignupSourceForPhantom($isPhantomOnboardingFlow, $sourceAppId, $merchantData['id']);
                }

                $data = $this->get($user['id']);
                $response = $data;


                return array($merchant, $countryCode, $user);
            }
        });

        if (!$this->isAssistedOnboardingSignupCampaign($signupCampaign)) {
            try {
                if (empty($merchant) === false) {
                    $this->handlePGOSOnboarding($merchant, $signupCampaign, $countryCode, $input, $user);
                }
            } catch (\Throwable $exception) {
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'message' => "Error in handlePGOSOnboarding()",
                    'merchant_id' => $merchant->getId(),
                    'error_message' => $exception->getMessage()
                ]);
            }
        }

        return $response;
    }

    // a single merchant_id can have multiple products. hence a single value of workflow_type is not sufficient. for each product, the workflow_type
    // should be stored separately. as of now, this change is enforced only for one product but other products should also adopt this approach.
    public function shouldStoreProductSpecificWorkflowType($product): bool
    {
        return in_array($product, [DeviceDetailConstants::PRODUCT_PG_ONBOARDING, DeviceDetailConstants::CROSS_BORDER_ONBOARDING, DeviceDetailConstants::SUBMERCHANT_ONBOARDING]);
    }

    private function shouldOnboardViaPGOSForNonOAuthMerchants(MerchantEntity $merchant, $signupCampaign, $countryCode): bool
    {
        $shouldOnboardViaPGOS = false;

        if (in_array($signupCampaign, DeviceDetail\Constants::PGOS_ENABLED_SIGNUP_CAMPAIGNS))
        {
            return true;
        }

        $merchantCore = new Merchant\Core();

        if ($signupCampaign === DeviceDetail\Constants::EASY_ONBOARDING AND $countryCode === 'IN')
        {

            if ($merchantCore->isPOSSubMerchant($merchant))
            {
                return true;
            }
            else
            {
                $isPGOSLiveModeExperimentEnabledForMerchant = $this->pgosProxyController->isPGOSExperimentEnabledForMerchant(
                    $merchant->getId(), 'app.pgos_live_mode_experiment_id', 'enable',
                );

                if ($isPGOSLiveModeExperimentEnabledForMerchant)
                {
                    if ($merchantCore->isRegularMerchant($merchant))
                    {
                        return true;
                    }
                    else if (
                        $merchantCore->isRegularSubmerchant($merchant)
                        and $this->pgosProxyController->isPGOSEnabledForPGSubmerchant($merchant)
                    )
                    {
                        return true;
                    }
                }
            }
        }

        if ($signupCampaign === DeviceDetail\Constants::PHANTOM_ONBOARDING)
        {
            if ($merchantCore->isRegularSubmerchant($merchant))
            {
                $shouldOnboardViaPGOS = $this->pgosProxyController->isPGOSEnabledForPhantomSubmerchant($merchant);

                $this->trace->info(TraceCode::PHANTOM_SUBMERCHANT_PGOS, [
                    'merchant_id'       => $merchant->getId(),
                    'signup_campaign'   => $signupCampaign,
                    'onboard_via_pgos'  => $shouldOnboardViaPGOS,
                ]);

            }
        }

        return $shouldOnboardViaPGOS;
    }

    private function shouldOnboardViaPGOSForOAuthMerchants($merchant, $input, $signupCampaign): bool
    {
        $countryCode = $input[Merchant\Entity::COUNTRY_CODE] ?? 'IN';

        $workflowType = $input[DeviceDetail\Constants::WORKFLOW_TYPE] ?? '';

        return (($signupCampaign === DeviceDetail\Constants::EASY_ONBOARDING
            and (new Merchant\Core)->isRegularMerchant($merchant) === true
            and $countryCode === 'IN') || ($workflowType === DeviceDetailConstants::MODULAR_ONBOARDING ||
            $this->isAssistedOnboardingSignupCampaign($signupCampaign) ||
            $signupCampaign === DeviceDetailConstants::RIZE_INCORPORATION));
    }

    public function handlePGOSOnboarding(MerchantEntity $merchant, $signupCampaign, $countryCode, $input, $user)
    {
        $workflowType = $input[DeviceDetail\Constants::WORKFLOW_TYPE] ?? '';

        $this->trace->info(TraceCode::PGOS_ONBOARDING, [
            'merchant_id'    => $merchant->getId(),
            'workflowType'   => $workflowType,
            'signupCampaign' => $signupCampaign,
            'countryCode'    => $countryCode,
            'input'          => $input
        ]);

        if (empty(DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign]) === false)
        {
            $input[DeviceDetail\Constants::PRODUCT] = $input[DeviceDetail\Constants::PRODUCT] ?? (DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign][DeviceDetailConstants::PRODUCT] ?? '');
            $input[DeviceDetail\Constants::PLATFORM] = $input[DeviceDetail\Constants::PLATFORM] ?? (DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign][DeviceDetailConstants::PLATFORM] ?? '');
            $workflowType = $input[DeviceDetail\Constants::WORKFLOW_TYPE] ?? (DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign][DeviceDetailConstants::WORKFLOW_TYPE] ?? '');
        }

        $shouldOnboardViaPGOS = $this->shouldOnboardViaPGOSForNonOAuthMerchants($merchant, $signupCampaign, $countryCode);
        if ($workflowType === DeviceDetail\Constants::MODULAR_ONBOARDING)
        {
            $shouldOnboardViaPGOS = true;
        }
        if ($shouldOnboardViaPGOS === false)
        {
            return;
        }

        $product = $input[DeviceDetail\Constants::PRODUCT] ?? '';
        $platform = $input[DeviceDetail\Constants::PLATFORM] ?? DeviceDetail\Constants::PLATFORM_PG;

        // Create OBS Workflow For Merchant via PGOS.
        // Workflow will only be created for merchants who will be onboarded via PGOS
        try
        {
            $orgId = $this->auth->getOrgId();
            Org\Entity::silentlyStripSign($orgId);
            $createWorkflowRequestBody = [
                'account_id'                            => $merchant->getId(),
                'account_type'                          => "merchant",
                DeviceDetail\Entity::SIGNUP_SOURCE      => $input[DeviceDetail\Entity::SIGNUP_SOURCE] ??
                                                        $this->auth->getRequestOriginProduct(),
                DeviceDetail\Entity::SIGNUP_CAMPAIGN    => $signupCampaign,
                Merchant\Entity::COUNTRY_CODE           => $countryCode,
                'org_id'                                => $orgId,
                'user_id'                               => $user['id'],
                DeviceDetail\Constants::WORKFLOW_TYPE   => $workflowType,
                DeviceDetail\Constants::PRODUCT         => $product,
                DeviceDetail\Constants::PLATFORM        => $platform

            ];

            // sign up response is not driven by PGOS
            $response = $this->pgosProxyController->handleMerchantSignup($createWorkflowRequestBody, $merchant);
            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'merchant_id' => $merchant->getId(),
                'response'    => $response,
            ]);
        }
        catch (\Throwable $exception)
        {
            $shouldOnboardViaPGOS = false;
            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id'   => $merchant->getId(),
                'error_message' => $exception->getMessage()
            ]);
        }

        if (empty($response['workflow_id']) === true)
        {
            $shouldOnboardViaPGOS = false;
        }

        $workflowId = $response['workflow_id'] ?? '';

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantId($merchant->getId());

        $ddInput[DeviceDetail\Entity::METADATA] = $this->mergeJson($userDeviceDetail->getMetadata(), $this->getUserDeviceDetailsMetadata($shouldOnboardViaPGOS, $workflowType, $product));

        $userDeviceDetail->setAttribute('metadata', $ddInput['metadata']);
        $this->repo->user_device_detail->saveOrFail($userDeviceDetail);

        if ($shouldOnboardViaPGOS === false)
        {
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id' => $merchant->getId(),
                'message'     => "Reverting back the merchant onboarding service to API"
            ]);

            return;
        }

        if ((isset($input[Entity::CONTACT_MOBILE]) === true))
        {
            // update mobile number
            try
            {
                if (empty($workflowType) === false && $workflowType === DeviceDetailConstants::MODULAR_ONBOARDING)
                {
                    $modularPayload = [
                        'field_data' => [
                            Entity::CONTACT_MOBILE => $input[Entity::CONTACT_MOBILE],
                            DeviceDetailConstants::SIGNUP_SOURCE => DeviceDetailConstants::MOBILE,
                        ],
                        DeviceDetail\Constants::PRODUCT         => $product,
                        DeviceDetail\Constants::PLATFORM        => $platform,
                        Merchant\Entity::COUNTRY_CODE           => $countryCode,
                        DeviceDetail\Constants::ORG_ID          => $orgId,
                        DeviceDetail\Constants::VERSION_ID      => DeviceDetail\Constants::DEFAULT_VERSION,
                    ];

                    if (empty($input[DeviceDetail\Constants::CROSS_BORDER_FLOW]) === false) {
                        $modularPayload['field_data']['cross_border_flow'] = $input[DeviceDetail\Constants::CROSS_BORDER_FLOW];
                    }

                    // this response is not used in this flow
                    $response = $this->pgosProxyController->handlePGOSProxyRequests('onboarding_save', $modularPayload, $merchant, true);

                }
                else {
                    // merge input with detail input
                    $pgosPayload = [
                        'contact_mobile' => $input[Entity::CONTACT_MOBILE],
                        'merchant_id' => $merchant->getId(),
                    ];

                    // this response is not used in this flow
                    $response = $this->pgosProxyController->handlePGOSProxyRequests('merchant_activation_save', $pgosPayload, $merchant, true);

                }
                $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                    'response' => $response
                ]);

            }
            catch (\Throwable $exception) {
                // this should not introduce error counts as it is running in shadow mode
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id'   => $merchant->getId(),
                    'error_message' => $exception->getMessage()
                ]);
            }
        }
    }

    private function handlePGOSOnboardingForAssistedMerchant($merchantId, $signupCampaign, $countryCode, $input, $userId,$loggedInUserEmail)
    {
        $workflowType = $input[DeviceDetail\Constants::WORKFLOW_TYPE] ?? '';

        $this->trace->info(TraceCode::PGOS_ONBOARDING, [
            'merchant_id'    => $merchantId,
            'workflowType'   => $workflowType,
            'signupCampaign' => $signupCampaign,
            'input'          => $input
        ]);

        $product = $input[DeviceDetail\Constants::PRODUCT] ?? '';
        $platform = $input[DeviceDetail\Constants::PLATFORM] ?? DeviceDetail\Constants::PLATFORM_PG;

        // Create OBS Workflow For Merchant via PGOS.
        // Workflow will only be created for merchants who will be onboarded via PGOS
        try
        {
            $orgId = $this->auth->getOrgId();
            Org\Entity::silentlyStripSign($orgId);
            $createWorkflowRequestBody = [
                'account_id'                            => $merchantId,
                'account_type'                          => "merchant",
                DeviceDetail\Entity::SIGNUP_SOURCE      => $input[DeviceDetail\Entity::SIGNUP_SOURCE] ??
                    $this->auth->getRequestOriginProduct(),
                DeviceDetail\Entity::SIGNUP_CAMPAIGN    => $signupCampaign,
                Merchant\Entity::COUNTRY_CODE           => $countryCode,
                'org_id'                                => $orgId,
                'user_id'                               => $userId,
                DeviceDetail\Constants::WORKFLOW_TYPE   => $workflowType,
                DeviceDetail\Constants::PRODUCT         => $product,
                DeviceDetail\Constants::PLATFORM        => $platform,
                DeviceDetail\Constants::SALESEMAILID    => $loggedInUserEmail,

            ];

            // sign up response is not driven by PGOS
            $response = $this->pgosProxyController->handlePGOSProxyRequestsForAssistedMerchants(MerchantOnboardingProxyController::SALES_ASSISTED_MERCHANT_SIGN_UP,$createWorkflowRequestBody, $merchantId);

            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'merchant_id' => $merchantId,
                'response'    => $response,
            ]);
        }
        catch (\Throwable $exception)
        {
            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id'   => $merchantId,
                'error_message' => $exception->getMessage()
            ]);
            throw new Exception\BadRequestValidationFailureException(ErrorCode::ASSISTED_WORKFLOW_CREATION_FAILED);
        }

        if (empty($response['workflow_id']) === true or empty($response['modular_workflow_id']) === true )
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::ASSISTED_WORKFLOW_CREATION_FAILED);
        }

        if ((isset($input[Entity::CONTACT_MOBILE]) === true) or (isset($input[Entity::EMAIL]) === true))
        {
            // update mobile number
            try
            {
                // merge input with detail input
                if (isset($input[Entity::CONTACT_MOBILE]) === true) {
                    $pgosPayload = [
                        'contact_mobile' => $input[Entity::CONTACT_MOBILE],
                        'merchant_id' => $merchantId,
                    ];
                }
                else{
                    $pgosPayload = [
                        'contact_email' => $input[Entity::EMAIL],
                        'merchant_id' => $merchantId,
                    ];
                }
                // this response is not used in this flow
                $response = $this->pgosProxyController->handlePGOSProxyRequestsForAssistedMerchants('merchant_activation_save', $pgosPayload, $merchantId, true);

                $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                    'response' => $response
                ]);

            }
            catch (\Throwable $exception) {
                // this should not introduce error counts as it is running in shadow mode
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id'   => $merchantId,
                    'error_message' => $exception->getMessage()
                ]);
            }
        }
    }

    private function getUserDeviceDetailsMetadata($shouldOnboardViaPGOS, $workflowType, $product)
    {
        if ($shouldOnboardViaPGOS === false)
        {
            //Merchant onboarding to be continued with API as PGOS account creation failed
            return [
                DeviceDetailConstants::SERVICE => DeviceDetailConstants::SERVICE_API
            ];
        }

        $ddMetadata = [
            DeviceDetailConstants::SERVICE => DeviceDetailConstants::SERVICE_PGOS
        ];

        if ($workflowType !== DeviceDetailConstants::MODULAR_ONBOARDING)
        {
            return $ddMetadata;
        }

        if ($this->shouldStoreProductSpecificWorkflowType($product) === true)
        {
            $productSpecificWorkflowTypeKey = sprintf(DeviceDetailConstants::PRODUCT_WORKFLOW_TYPE_TEMPLATE, $product);

            $ddMetadata[DeviceDetailConstants::WORKFLOW_DETAILS][$productSpecificWorkflowTypeKey] = DeviceDetailConstants::MODULAR_ONBOARDING;
        }
        else
        {
            $ddMetadata[DeviceDetailConstants::WORKFLOW_TYPE] = DeviceDetailConstants::MODULAR_ONBOARDING;
        }

        return $ddMetadata;
    }

    private function handlePGOSOnboardingForOAuthMerchants($merchant, $signupCampaign, $input, $user)
    {
        $shouldOnboardViaPGOS = false;

        $countryCode = $input['country_code'] ?? 'IN';

        $workflowType = $input[DeviceDetail\Constants::WORKFLOW_TYPE] ?? '';

        //Determine whether onboarding should be done via PGOS or not
        //Not checking the experiment here because FE checks the experiment
        //All merchants who onboard via OAuth and FE sends signup campaign as EASY_ONBOARDING, needs to be onboarded via PGOS
        if ($this->shouldOnboardViaPGOSForOAuthMerchants($merchant, $input, $signupCampaign))
        {
            $shouldOnboardViaPGOS = true;
        }
        else
        {
            return;
        }
        if (empty(DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign]) === false)
        {
            $input[DeviceDetail\Constants::PRODUCT] = $input[DeviceDetail\Constants::PRODUCT] ?? (DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign][DeviceDetailConstants::PRODUCT] ?? '');
            $input[DeviceDetail\Constants::PLATFORM] = $input[DeviceDetail\Constants::PLATFORM] ?? (DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign][DeviceDetailConstants::PLATFORM] ?? '');
            $workflowType = $input[DeviceDetail\Constants::WORKFLOW_TYPE] ?? (DeviceDetailConstants::SIGNUP_CAMPAIGN_ONBOARDING_MAPPING[$signupCampaign][DeviceDetailConstants::WORKFLOW_TYPE] ?? '');
        }

        $product = $input[DeviceDetail\Constants::PRODUCT] ?? '';
        $platform = $input[DeviceDetail\Constants::PLATFORM] ?? DeviceDetail\Constants::PLATFORM_PG;

        // Create OBS Workflow For Merchant via PGOS.
        // Workflow will only be created for merchants who will be onboarded via PGOS
        try
        {
            $orgId = $this->auth->getOrgId();
            Org\Entity::silentlyStripSign($orgId);
            $createWorkflowRequestBody = [
                'account_id'                            => $merchant->getId(),
                'account_type'                          => "merchant",
                DeviceDetail\Entity::SIGNUP_SOURCE      => $input[DeviceDetail\Entity::SIGNUP_SOURCE] ??
                                                        $this->auth->getRequestOriginProduct(),
                DeviceDetail\Entity::SIGNUP_CAMPAIGN    => $signupCampaign,
                Merchant\Entity::COUNTRY_CODE           => $countryCode,
                'org_id'                                => $orgId,
                'user_id'                               => $user['id'],
                DeviceDetail\Constants::WORKFLOW_TYPE   => $workflowType,
                DeviceDetail\Constants::PRODUCT         => $product,
                DeviceDetail\Constants::PLATFORM        => $platform,
            ];

            // sign up response is not driven by PGOS
            $response = $this->pgosProxyController->handleMerchantSignup($createWorkflowRequestBody, $merchant);

            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'merchant_id' => $merchant->getId(),
                'response'    => $response,
            ]);
        }
        catch (\Throwable $exception)
        {
            $shouldOnboardViaPGOS = false;
            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id'   => $merchant->getId(),
                'error_message' => $exception->getMessage()
            ]);
        }

        if (empty($response['workflow_id']) === true)
        {
            $shouldOnboardViaPGOS = false;
        }

        $workflowId = $response['workflow_id'] ?? '';

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantId($merchant->getId());

        $ddInput[DeviceDetail\Entity::METADATA] = $this->mergeJson($userDeviceDetail->getMetadata(), $this->getUserDeviceDetailsMetadata($shouldOnboardViaPGOS, $workflowType, $product));

        $userDeviceDetail->setAttribute('metadata', $ddInput['metadata']);
        $this->repo->user_device_detail->saveOrFail($userDeviceDetail);

        if ($shouldOnboardViaPGOS === false)
        {
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id' => $merchant->getId(),
                'message'     => "Reverting back the merchant onboarding service to API"
            ]);

            return;
        }

        if ((isset($input[Entity::EMAIL]) === true))
        {
            // update email
            try
            {
                if ($workflowType === DeviceDetailConstants::MODULAR_ONBOARDING)
                {
                    $modularPayload = [
                        'field_data' => [
                            'contact_email' => $input[Entity::EMAIL],
                            DeviceDetailConstants::SIGNUP_SOURCE => Entity::EMAIL
                        ],
                        DeviceDetail\Constants::PRODUCT         => $product,
                        DeviceDetail\Constants::PLATFORM        => $platform,
                        Merchant\Entity::COUNTRY_CODE           => $countryCode,
                        DeviceDetail\Constants::ORG_ID          => $orgId,
                        DeviceDetail\Constants::VERSION_ID      => DeviceDetail\Constants::DEFAULT_VERSION,
                    ];

                    if (empty($input[DeviceDetail\Constants::CROSS_BORDER_FLOW]) === false) {
                        $modularPayload['field_data']['cross_border_flow'] = $input[DeviceDetail\Constants::CROSS_BORDER_FLOW];
                    }
                    // this response is not used in this flow
                    $response = $this->pgosProxyController->handlePGOSProxyRequests('onboarding_save', $modularPayload, $merchant, true);

                }
                else
                {
                    // merge input with detail input
                    $pgosPayload = [
                        'email' => $input[Entity::EMAIL],
                        'merchant_id' => $merchant->getId(),
                        'skip_email_uniqueness' => true,
                    ] ;

                    // this response is not used in this flow
                    $response = $this->pgosProxyController->handlePGOSProxyRequests('merchant_activation_save', $pgosPayload, $merchant, true);

                    $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                        'response' => $response
                    ]);
                }
            }
            catch (\Throwable $exception) {
                // this should not introduce error counts as it is running in shadow mode
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id'   => $merchant->getId(),
                    'error_message' => $exception->getMessage()
                ]);
            }
        }
    }

    protected function mergeJson($existingDetails, $newDetails)
    {
        if (empty($newDetails) === false)
        {
            foreach ($newDetails as $key => $value)
            {
                $existingDetails[$key] = $value;
            }
        }
        return $existingDetails;
    }

    /**
     * Pushes the SUBMERCHANT_SIGNUP event to Segment.
     *
     * @param Merchant\Entity $partnerMerchant The partner merchant entity.
     * @param string          $subMerchantId   The ID of the sub-merchant.
     * @param string          $referralCode    The referral code used.
     */
    public function processReferralCode(string $merchantId, string $referralCode, bool $withRetry = true): array
    {
        try
        {
            $this->trace->info(TraceCode::PROCESS_REFERRAL_CODE, [
                'merchant_id'   => $merchantId,
                'referral_code' => $referralCode
            ]);

            if (empty($referralCode) === true)
            {
                return [];
            }

            $referral = (new Merchant\Referral\Core)->fetchReferralByReferralCode($referralCode);

            if (empty($referral))
            {
                return [];
            }
            // dispatch create_signup_source
            $product = $this->auth->getRequestOriginProduct();
            $this->app->partnerships->createSubMSignupSource($referral->getMerchantId(), $merchantId, $product);

            $detailService = (new Merchant\Detail\Service());

            $referralInput = $detailService->getReferralInput($referral);

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            if ($withRetry) {
                $detailService->applyReferralPartnerWithRetry($merchant, $referralInput, true);
            } else {
                $detailService->applyReferralPartner($merchant, $referralInput, true);
            }

            $this->trace->count(Merchant\Metric::SUBMERCHANT_SIGNUP_LINKING_SUCCESS_TOTAL);

            return $referralInput;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                                         Logger::ERROR,
                                         TraceCode::SUBMERCHANT_SIGNUP_LINKING_JOB_DISPATCH_FAILURE,
                                         [
                                             'merchant_id'  => $merchantId,
                                             'referralCode' => $referralCode,
                                             'message'      => 'Error occurred while linking subM during signUp'
                                         ]);
            $this->trace->count(Merchant\Metric::SUBMERCHANT_SIGNUP_LINKING_FAILURE_TOTAL);
            return [];
        }
    }

    /**
     * linkSubMerchantToPlatformPartnerWithRetry links a submerchant to a pure platform
     * partner's application by first trying it synchronously.
     * If that fails then it queues a job to retry asynchronously.
     *
     * @param string      $merchantId
     * @param string|null $sourceAppId
     * @param bool        $isOauthReferral
     *
     * @return void
     */
    private function linkSubMerchantToPlatformPartnerWithRetry(
        string $merchantId,
        string $sourceAppId = null,
        bool $isOauthReferral = false,
    ): void
    {
        try
        {
            if ( $isOauthReferral === false || (empty($sourceAppId) === true) )
            {
                return;
            }

            $merchantApp = (new MerchantAppRepo)->fetchMerchantApplication(
                $sourceAppId,
                Merchant\Constants::APPLICATION_ID,
            );

            $input = [
                Merchant\Constants::PARTNER_ID       => $merchantApp[0][Merchant\Constants::MERCHANT_ID],
                Merchant\Constants::APPLICATION_ID   => $sourceAppId,
            ];

            $accessMapService = new Merchant\AccessMap\Service();

            $accessMapService->mapOAuthApplication($merchantId, $input);

        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e,
                Logger::ERROR,
                TraceCode::SUBM_SIGNUP_LINKING_PP_REFERRAL_FAILURE,
                [
                    'merchant_id'  => $merchantId,
                    'message'      => 'Error occurred while linking subM during signUp for pp referral flow'
                ]);

            PartnerSubmerchantLinkingOauthJob::dispatch($this->mode, $merchantId, $sourceAppId);
        }
    }

    private function createSignupSourceForPhantom(bool $isPhantomOnboardingFlow, string $sourceAppId, string $merchantId)
    {
        try
        {
            if ($isPhantomOnboardingFlow && empty($sourceAppId) == false)
            {
                // dispatch create_signup_source
                $merchantApp = (new MerchantAppRepo)->fetchMerchantApplication($sourceAppId, Merchant\Constants::APPLICATION_ID);
                $product     = $this->auth->getRequestOriginProduct();

                $this->app->partnerships->createSubMSignupSource($merchantApp[0][Merchant\Constants::MERCHANT_ID], $merchantId, $product);
            }
        } catch(\Exception $e)
        {
            $this->trace->traceException($e,
                                         Logger::ERROR,
                                         TraceCode::CREATE_SIGNUP_SOURCE_FAILURE,
                                         [
                                             'sourceAppId'  => $sourceAppId,
                                             'message'      => 'Error occurred while creating signup source'
                                         ]);
            $this->trace->count(Partner\Metric::PRTS_CREATE_SIGNUP_SOURCE_PUSH,['success'=> false]);
        }
    }


    protected function pushSegmentSignupEvent($userId, $customProperties)
    {
        $user = $this->repo->user->findOrFailPublic($userId);

        $merchant = $user->getMerchantEntity();

        if(empty($merchant) === false)
        {
            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $customProperties, SegmentEvent::SIGNUP_SUCCESS);
        }

        return $merchant;
    }

    protected function traceRegisterInput($input) {
        $notLogKeys = [
            Entity::PASSWORD,
            Entity::PASSWORD_CONFIRMATION,
            Entity::REMEMBER_TOKEN,
            Entity::CONFIRM_TOKEN,
            Entity::CONTACT_MOBILE,
            Entity::CAPTCHA,
            Constants::ID_TOKEN,
        ];

        $logData = $input;

        foreach ($notLogKeys as $key)
        {
            unset($logData[$key]);
        }

        $this->trace->info(TraceCode::USER_REGISTER, $logData);
    }

    /**
     * @param array $merchantInputData
     * @param array $userData
     * @param string $referrer
     * @param array  $inputData
     *
     * @param bool $sendOtpEmail
     * @param bool $sendConfirmation
     * @return array
     */
    public function createMerchantFromUser(
        array $merchantInputData,
        array $userData,
        string $referrer = '',
        bool $sendOtpEmail = false,
        array $inputData = [],
        bool $sendConfirmation = true,
        array $merchantDetailInputData = []
    )
    {
        $createMerchantMetadata = [
            Merchant\Entity::SKIP_EMAIL_UNIQUENESS_CHECK    =>  $inputData[Merchant\Entity::SKIP_EMAIL_UNIQUENESS_CHECK],
        ];
        $merchantData = $this->merchantService->create($merchantInputData, $merchantDetailInputData, $createMerchantMetadata);

        unset($merchantDetailInputData['token_data']);

        if (empty($referrer) === false)
        {
            $tagInputData = [
                'tags' => [Merchant\Constants::PARTNER_REFERRAL_TAG_PREFIX . $referrer],
            ];

            $this->merchantService->addTags($merchantData['id'], $tagInputData);
        }

        $userMerchantMappingInputData = [
            'action'      => 'attach',
            'role'        => 'owner',
            'merchant_id' => $merchantData['id'],
        ];

        $this->updateUserMerchantMapping($userData['id'], $userMerchantMappingInputData);

        $user = $this->repo->user->findOrFailPublic($userData['id']);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantData['id']);

        $data = [
            "id"                => $merchant->getId(),
            "name"              => $merchant->getName(),
            "email"             => $user->getEmail(),
            "contact_mobile"    => $user->getContactMobile(),
            "user_id"           => $user->getId()
        ];

        if($sendConfirmation === true)
        {
            $data = $this->sendConfirmationMailIfApplicable($user, $merchant, $sendOtpEmail, $inputData);
        }

        if ($this->auth->isProductBanking())
        {
            $utmParams = [];
            $this->addUtmParameters($utmParams);

            // Storing presign up information for X.
            $this->merchantService->storeRelevantPreSignUpSourceInfoForBanking($utmParams, $merchant);
        }

        return $data;
    }

    // Checks if the request to register user or resend verification link came from new signup flow for X (v2)
    // Remove this when signup experiment for X is ramped up.
    protected function isRequestFromXVerifyEmail($input) :bool
    {
        $xVerifyEmail = $input[Entity::X_VERIFY_EMAIL] ?? "false";

        $requestFromXVerifyEmail = ($xVerifyEmail === "true");

        return $requestFromXVerifyEmail;
    }

    /**
     * @param Entity          $user
     * @param Merchant\Entity $merchant
     * @param bool            $sendOtpEmail
     * @param array           $inputData
     *
     * @return array
     */
    protected function sendConfirmationMailIfApplicable(Entity $user, Merchant\Entity $merchant = null, bool $sendOtpEmail = false, array $inputData = [])
    {
        $requestOriginProduct = $this->auth->getRequestOriginProduct();

        $response = [];

        $customProperties = [Entity::EMAIL       => $user->getEmail(),
                             Entity::MERCHANT_ID => $user->getMerchantId()];

        // Remove this when signup experiment for X is ramped up.
        $isRequestFromXVerifyEmail = $inputData['isRequestFromXVerifyEmail'] ?? false;

        // If User is New Signed up with new auth flow and
        // Already Not confirmed and product is PG.
        // Or if the request is coming from new signup flow for X (v2)

        if ((($requestOriginProduct !== Product::BANKING) or
             ($isRequestFromXVerifyEmail === true)) and
              $sendOtpEmail and
             ($user->getConfirmedAttribute() === false))
        {
            $data = $this->sendOtpEmailVerification($user, $merchant, [], $inputData);

            $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_SEND_VERIFICATION_EMAIL_OTP_SUCCESS, $merchant, null, $customProperties);

            if (empty($merchant) === false) {
                $this->app['segment-analytics']->pushIdentifyAndTrackEvent($merchant, $customProperties, SegmentEvent::SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS);
            }

            // Add the response of token from Raven Service
            $response['token'] = $data['token'];
        }
        // Remove this else when signup experiment for X is ramped up.
        else
        {
            $this->sendConfirmationMail($user);

            $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_SEND_VERIFICATION_EMAIL_SUCCESS, $merchant, null, $customProperties);
        }

        $response['id']      = $merchant?->getId();
        $response['name']    = $merchant?->getName();
        $response['email']   = $user->getEmail();
        $response['user_id'] = $user->getId();

        return $response;
    }

    public function sendOtpEmailVerification(Entity $user, Merchant\Entity $merchant = null, array $merchantData = [], array $inputData = [])
    {
        $merchantData['medium'] = 'email';

        // Remove this when signup experiment for X is ramped up.
        // We can make use of product.
        $isRequestFromXVerifyEmail = $inputData['isRequestFromXVerifyEmail'] ?? false;

        $merchantData['action'] = ($isRequestFromXVerifyEmail === true) ? 'x_verify_email' : 'verify_email';

        $this->trace->info(
            TraceCode::USER_EMAIL_OTP_SEND,
            [
                'merchantId'      => $merchant?->getId(),
                'userId'          => $user->getId(),
            ]);

        return $this->core()->sendOtp($merchantData, $merchant, $user);
    }

    /**
     * @param Entity $user
     *
     * @return array
     */
    public function sendConfirmationMail(Entity $user)
    {
        // Only send the confirmation email if the user isn't already confirmed
        if ($user->getConfirmedAttribute() === false)
        {
            $orgId = $this->auth->getOrgId();

            $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

            $org['hostname'] = $this->auth->getOrgHostName();

            $requestOriginProduct = $this->auth->getRequestOriginProduct();

            // confirmation mail for RazorpayX is different. Handling it here based on the OriginProduct
            if ($requestOriginProduct === Product::BANKING)
            {
                $confirmationMail = new UserMail\RazorpayX\AccountVerification($user->getId());
            }
            else
            {
                $confirmationMail = new UserMail\AccountVerification($user, $org, $requestOriginProduct);
            }

            Mail::queue($confirmationMail);
        }
        else
        {
            // if user is already confirmed then sending confirm as true.
            return ['confirm' => true];
        }

        return ['success' => true];
    }

    public function create(array $input, string $operation = 'create'): array
    {
        $user = $this->core->create($input, $operation);

        return $user->toArrayPublic();
    }

    public function edit(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = $this->core->edit($user, $input);

        return $user->toArrayPublic();
    }

    /**
     * Edit user action for logged in user (via Dashboard headers).
     *
     * @param  array  $input
     * @return array
     */
    public function editSelf(array $input): array
    {
        /* @var BasicAuth $ba */
        $ba = $this->app['basicauth'];
        $routeName = $this->app['request.ctx']->getRoute() ?? null;
        $user = $ba->getUser();

        // if it's a RX dashboard user, and mobile is either not set or not verified, then verify otp via email
        // before updating mobile number (hotfix)
        if ($ba->getProduct() === Product::BANKING
            && $ba->isProxyAuth()
            && isset($input[Entity::CONTACT_MOBILE])
            && (empty($user->getContactMobile()) === true or $user->isContactMobileVerified() === false))
        {
            (new Validator())->setStrictFalse()->validateInput(Validator::UPDATE_UNVERIFIED_MOBILE_NUMBER_OTP, $input);
            $userCore = new Core;

            $userCore->verifyOtp($input + ['action' => $input['action'], 'medium' => 'email'],
                                 $ba->getMerchant(),
                                 $user,
                                 $this->mode === Mode::TEST);

            $input = array_except($input, ['otp', 'token', 'action']);
        }

        $this->core()->edit($this->user, $input);

        return $this->user->toArrayPublic();
    }

    public function editInternal(string $id, array $input)
    {
        (new Validator())->validateInput(Validator::EDIT_USER_INTERNAL, $input);

        $this->repo->transactionOnLiveAndTestAndAsv(function() use ($id, $input)
        {
            $updatedName = $input[Entity::NAME];

            unset($input[Entity::NAME]);

            if (!empty($updatedName))
            {
                $this->edit($id, [
                    Entity::NAME => $updatedName,
                ]);
            }

            if (!empty($input[Entity::ACTION]))
            {
                $this->updateMerchantManageTeam($id, $input);
            }
        });

        $user = $this->repo->user->findOrFail($id);

        return $user->toArrayPublic();
    }

    public function confirm(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = $this->core->confirm($user);

        return $user->toArrayPublic();
    }

    public function confirmUserByData(array $input): array
    {
        $user = null;

        (new Entity)->getValidator()->validateInput('confirm', $input);

        // need to validate if it is only a confirm_token or an email
        if (empty($input[Entity::CONFIRM_TOKEN]) === false)
        {
            $user = $this->repo->user->findByToken($input[Entity::CONFIRM_TOKEN]);
        }
        else if (empty($input[Entity::EMAIL]) === false and $this->auth->isAdminAuth() === true)
        {
            $user = $this->repo->user->findByEmail($input[Entity::EMAIL]);
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
        }

        $user = $this->core->confirm($user);

        $data = $user->toArrayPublic();

        $this->core->subscribeToMailingList($data);

        return $data;
    }

    public function changePasswordWithOtpVerification(array $input): array
    {
        $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        $this->core->verifyOtp($input + ['action' => 'change_password'],
            $this->merchant,
            $this->user,
            $this->mode === Mode::TEST);

        $changePasswordInput = array_except($input, ['otp', 'token']);

        return $this->changePassword($changePasswordInput);
    }

    public function changePassword(array $input): array
    {
        $user = $this->user;
        $userId = $user->getId();

        LoginSignupRateLimit::validateKeyLimitExceeded(
            $userId,
            Constants::CHANGE_PASSWORD_RATE_LIMIT_SUFFIX,
            Constants::CHANGE_PASSWORD_RATE_LIMIT_TTL,
            Constants::CHANGE_PASSWORD_RATE_LIMIT_THRESHOLD
        );

        $user->getValidator()->validateInput('changePassword', $input);

        $this->core->setNewPassword($user, $input);

        $this->revokeTokenOnPasswordChange($user);

        return $user->toArrayPublic();
    }

    public function checkUserHasSetPassword(): array
    {
        $user = $this->user;

       return $this->core->checkUserHasSetPassword($user);
    }

    /**
     * @throws BadRequestException
     */
    public function patchUserPassword(array $input) : array
    {
        $user = $this->auth->getUser();

        if($user->getPassword() !== null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PASSWORD_ALREADY_SET,
            null,
                [
                    'internal_error_code'    => ErrorCode::BAD_REQUEST_PASSWORD_ALREADY_SET,
                ]);
        }

        $user->getValidator()->validateInput(Constants::SET_PASSWORD, $input);

        return $this->core->patchUserPassword($user, $input);
    }

    public function sendUserDetailsToSalesForceEvent(array $input) : array
    {
        try
        {
            $response = $this->app->salesforce->sendUserDetailsToSalesforce($input);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SALESFORCE_FAILED_TO_DISPATCH_JOB);

            if($e->getCode() === ErrorCode::BAD_REQUEST_SALESFORCE_DUPLICATES_RECORD_DETECTED or
                $e->getCode() === ErrorCode::BAD_REQUEST_SALESFORCE_FIELD_VALIDATION_ERROR)
            {
                throw $e;
            }

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_SALESFORCE_RETURNED_NON_2XX_RESPONSE);
        }

        return ['id' => $response['id']];
    }

    public function setUserPassword(array $input): array
    {
        $user = $this->user;

        $setPassword = $this->core->checkUserHasSetPassword($user);

        if($setPassword[Constants::SET_PASSWORD] === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PASSWORD_ALREADY_SET);
        }

        $user->getValidator()->validateInput(Constants::SET_PASSWORD, $input);

        return $this->core->setUserPassword($user, $input);
    }

    public function updateUserMerchantMapping(string $id, array $input): Entity
    {
        $input[Merchant\Entity::PRODUCT] = $input[Merchant\Entity::PRODUCT] ?? $this->auth->getRequestOriginProduct();

        $user = $this->repo->user->findOrFailPublic($id);

        $this->trace->info(TraceCode::UPDATE_USER_MERCHANT_MAPPING_REQUEST);

        $user = $this->core->updateUserMerchantMapping($user, $input);

        $this->trace->info(TraceCode::UPDATE_USER_MERCHANT_MAPPING_SUCCESS);

        return $user;
    }

    /**
     *  checks if provided role exist for provided user id, merchant id and product
     * @param $userId
     * @param $merchantId
     * @param $role
     * @param $product
     * @return bool
     */
    public function doesUserHaveRoleForMerchantAndProduct($userId, $merchantId, $role, $product)
    {
        $merchantUserMapping = $this->repo->merchant->getMerchantUserMapping($merchantId, $userId, $role, $product);

        return (empty($merchantUserMapping) === false);
    }


    public function login(array $input): array
    {
        try
        {
            $response = $this->core->login($input);

            $response = $this->addOauthTokenIfApplicable($response, $response[Entity::ID]);

            return $this->setOtpAuthTokenForBankingRequest($response, $response[Entity::ID]);
        }
        catch (\Throwable $ex)
        {
            $this->core->trackOnboardingEvent($input[Entity::EMAIL] ?? '', EventCode::MERCHANT_ONBOARDING_LOGIN_FAILURE, $ex);

            throw $ex;
        }
    }

    /**
     * Checks if x-mobile-oauth header is present
     * @return bool
     */
    public function isMobileOAuthRequest(): bool
    {
        return $this->app['request']->header(RequestHeader::X_MOBILE_OAUTH) === 'true';
    }

    /**
     * Creates oauth app and token for given user
     * @param array $responseData
     * @param string $userId
     * @param string|null $merchantId
     * @param Entity|null $userEntity
     * @param int $maxRetryCount
     * @return array
     * @throws \Throwable
     */
    public function addOauthTokenIfApplicable(array $responseData, string $userId, string $merchantId = null, Entity $userEntity = null, int $maxRetryCount = 1): array
    {
        if ($this->isMobileOAuthRequest() === false)
        {
            return $responseData;
        }

        if ($merchantId !== null)
        {
            $merchant = $this->repo->merchant->findByPublicId($merchantId);
        }
        else
        {
            $merchant = $this->core->selectCurrentMerchant($userId, $userEntity);
        }

        if (empty($merchant) === false)
        {
            try
            {
                $oAuthTokenService = new OAuthToken\Service();

                $input = [
                    OAuthApplicationConstants::OAUTH_TOKEN_SCOPE      => OAuthApplicationConstants::RX_MOBILE_TOKEN_SCOPE,
                    OAuthApplicationConstants::OAUTH_TOKEN_GRANT_TYPE => OAuthApplicationConstants::RX_MOBILE_TOKEN_GRANT_TYPE,
                    OAuthApplicationConstants::OAUTH_TOKEN_MODE       => OAuthApplicationConstants::RX_MOBILE_TOKEN_MODE,
                    OAuthApplicationConstants::OAUTH_APP_TYPE         => OAuthApplicationConstants::RX_MOBILE_APP_TYPE,
                    OAuthApplicationConstants::OAUTH_APP_NAME         => OAuthApplicationConstants::RX_MOBILE_APP_NAME,
                    OAuthApplicationConstants::OAUTH_APP_WEBSITE      => OAuthApplicationConstants::RX_MOBILE_APP_WEBSITE,
                ];

                $this->trace->info(TraceCode::MOBILE_OAUTH_REQUEST, $input);

                $responseToken = $oAuthTokenService->createOauthAppAndTokenForMobileApp($userId,
                    $merchant,
                    $input,
                    $maxRetryCount
                );

                $responseTokenArray = [
                    OAuthApplicationConstants::X_MOBILE_ACCESS_TOKEN => $responseToken[OAuthApplicationConstants::ACCESS_TOKEN],
                    OAuthApplicationConstants::X_MOBILE_REFRESH_TOKEN => $responseToken[OAuthApplicationConstants::REFRESH_TOKEN],
                    OAuthApplicationConstants::X_MOBILE_CLIENT_ID => $responseToken[OAuthApplicationConstants::CLIENT_ID],
                    OAuthApplicationConstants::CURRENT_MERCHANT_ID => $merchant->getId(),
                ];

                return array_merge($responseData, $responseTokenArray);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Logger::ERROR, TraceCode::MOBILE_OAUTH_TOKEN_GENERATION_ERROR);

                throw new ServerErrorException(
                    "Failed to complete request", ErrorCode::SERVER_ERROR, null, $e);
            }
        }

        return $responseData;
    }

    /**
     * @param array $responseData
     * @param MerchantEntity $merchant
     * @param string $clientId
     * @param string $accessToken
     * @return array
     * @throws BadRequestException
     * @throws Exception\ServerErrorException
     * @throws \Throwable
     */
    public function revokeAccessTokenForExistingMerchant(array $responseData, MerchantEntity $merchant, string $clientId, string $accessToken): array
    {
        $oAuthTokenService = new OAuthToken\Service();

        return $oAuthTokenService->revokeAccessTokenForMerchant($responseData, $merchant, $clientId, OAuthApplicationConstants::RX_MOBILE_TOKEN_TYPE, $accessToken);
    }

    /**
     * This function generates new access token using refresh token
     * @param string $merchantId
     * @param string $clientId
     * @param string $refreshToken
     * @param string $product
     * @return array
     * @throws BadRequestException
     * @throws Exception\ServerErrorException
     * @throws \Throwable
     */
    public function refreshAccessToken(string $merchantId, string $clientId, string $refreshToken, string $product = Product::BANKING): array
    {
        try
        {
            $merchant = $this->repo->merchant->findByPublicId($merchantId);

            $oAuthTokenService = new OAuthToken\Service();

            if ($product === Product::BANKING)
            {
                $responseToken = $oAuthTokenService->refreshAccessTokenForMerchant($merchant,
                    $clientId,
                    OAuthApplicationConstants::RX_MOBILE_TOKEN_TYPE,
                    OAuthApplicationConstants::RX_MOBILE_REFRESH_TOKEN_GRANT_TYPE,
                    $refreshToken);

                $responseData = [
                    OAuthApplicationConstants::X_MOBILE_ACCESS_TOKEN     => $responseToken[OAuthApplicationConstants::ACCESS_TOKEN],
                    OAuthApplicationConstants::X_MOBILE_REFRESH_TOKEN    => $responseToken[OAuthApplicationConstants::REFRESH_TOKEN],
                    OAuthApplicationConstants::X_MOBILE_CLIENT_ID        => $responseToken[OAuthApplicationConstants::CLIENT_ID],
                    OAuthApplicationConstants::CURRENT_MERCHANT_ID       => $merchant->getId()
                ];
            }

            return $responseData;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Logger::ERROR,
                TraceCode::MOBILE_OAUTH_REFRESH_TOKEN_GENERATION_ERROR,
                ['merchantId' => $merchantId]);

            throw $e;
        }
    }

    public function loginWithOtp(array $input): array
    {
        $data = $this->core->loginWithOtp($input);

        return $data;
    }

    public function verifyLoginOtp(array $input): array
    {
        $response = $this->core->verifyLoginOtp($input);

        $response = $this->addOauthTokenIfApplicable($response, $response[Entity::ID]);

        return $this->setOtpAuthTokenForBankingRequest($response, $response[Entity::ID]);
    }

    public function loginOtp2faPassword(array $input): array
    {
        $user = $this->auth->getUser();

        $response = $this->core->loginOtp2faPassword($user, $input);

        $response = $this->addOauthTokenIfApplicable($response, $user->getId(), null, $user);

        return $this->setOtpAuthTokenForBankingRequest($response, $user->getId());
    }

    public function sendVerificationOtp(array $input): array
    {
        $data = $this->core->sendVerificationOtp($input);

        return $data;
    }

    public function verifyVerificationOtp(array $input): array
    {
        $response = $this->core->verifyVerificationOtp($input);

        $response = $this->addOauthTokenIfApplicable($response, $response[Entity::ID]);

        return $response;
    }

    public function checkUserAccess(array $input)
    {
        if (empty($input['merchant_id']) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $merchantId = Account\Entity::verifyIdAndSilentlyStripSign($input['merchant_id']);

        $user = $this->auth->getUser();

        $product = $this->auth->getRequestOriginProduct();

        return $this->core()->checkAccessForMerchant($user, $merchantId, $product);

    }

    public function setup2faContactMobile(array $input): array
    {
        $user = $this->auth->getUser();

        return $this->core->setup2faContactMobile($user, $input);
    }

    public function resendOtp()
    {
        $user = $this->auth->getUser();

        return $this->core->resendOtp($user);
    }

    public function send2faOtp()
    {
        $user = $this->auth->getUser();

        return $this->core->send2faOtp($user);
    }

    public function setup2faVerifyMobileOnLogin(array $input): array
    {
        return $this->core->setup2faVerifyMobileOnLogin($input);
    }

    public function verifyUserSecondFactorAuth(array $input): array
    {
        $user = $this->auth->getUser();

        $response = $this->core->verifyUserSecondFactorAuth($user, $input);

        $response = $this->addOauthTokenIfApplicable($response, $response[Entity::ID]);

        return $this->setOtpAuthTokenForBankingRequest($response, $user->getId());
    }

    /**
     * Create a merchant by prefilling the user data.
     * This is so that the redundant data is not asked in the onboarding flow again
     * @param array $user
     * @param array $input
     * @return array
     */
    function createMerchantWithPrefillData(array $user, array $input, bool $isInternal=false)
    {
        // set orgID from auth if it is not internal request
        // set orgID from payload if it is internal request
        $orgID = $this->auth->getOrgId();
        if ($isInternal)
        {
            $orgID = $input[Merchant\Entity::ORG_ID];
        }

        $merchantInputData = [
            Merchant\Entity::NAME          => $input[Merchant\Entity::NAME] ?? '',
            Merchant\Entity::SIGNUP_SOURCE => $input[DeviceDetail\Entity::SIGNUP_SOURCE] ??
                $this->auth->getRequestOriginProduct(),
            Merchant\Entity::COUNTRY_CODE  => $input[Merchant\Entity::COUNTRY_CODE] ?? 'IN',
            Merchant\Entity::ORG_ID        => $orgID,
        ];

        $merchantDetailInputData = [];

        if (isset($user[Entity::EMAIL]) === true)
        {
            $merchantInputData[Merchant\Entity::EMAIL]            = $user[Entity::EMAIL];
        }

        if (isset($user[Entity::CONTACT_MOBILE]))
        {
            $merchantDetailInputData[Entity::CONTACT_MOBILE]      = $user[Entity::CONTACT_MOBILE];
        }

        $merchantInputData[Merchant\Entity::SIGNUP_VIA_EMAIL] = $user[Entity::SIGNUP_VIA_EMAIL] === 1 ? 1 : 0;

        $inputData = [
            Merchant\Entity::SKIP_EMAIL_UNIQUENESS_CHECK    =>  true,
        ];

        return $this->createMerchantFromUser(
            $merchantInputData,
            $user,
            '',
            false,
            $inputData,
            false,
            $merchantDetailInputData
        );
    }

    /**
     * Creates a new merchant for a user
     *
     * @param array $input {
     *     The input array containing payload data
     *
     *     @type string $country_code The country code based on user selection
     * }
     *
     * @return array
     */
    public function createMerchantForUser(array $input, bool $isInternal=false): array
    {
        // if skip_workflow_create is true then don't create
        // workflow return after merchant is created
        $skipWorkflowCreate = $input['skip_workflow_create'];
        unset($input['skip_workflow_create']);

        if ($isInternal)
        {
            $this->validator->validateInput('createMerchantInternal', $input);
            $userId = $input[Constants::USER_ID];
            $this->ba->setUserById($userId);
        }
        else
        {
            $this->validator->validateInput('createMerchant', $input);
        }
        $countryCode = $input[Merchant\Entity::COUNTRY_CODE] ?? 'IN';
        $signupCampaign = $input[DeviceDetail\Entity::SIGNUP_CAMPAIGN] ?? null;
        $businessName = $input[Merchant\Entity::NAME] ?? '';
        $signupSource = $input[DeviceDetail\Entity::SIGNUP_SOURCE] ??
            $this->auth->getRequestOriginProduct();

        $user = $this->auth->getUser();

        if ((new AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__)) {
            $merchants = $user->getMerchantsFromAsvWithPivot(1);
        } else {
            $merchants = $user->merchants()->take(1)->get();
        }

        if ($merchants->count() > 0) {
            // User already has a merchant so we need to prefill any data the user has
            $data = $this->createMerchantWithPrefillData($user->toArray(), $input, $isInternal);
        } else {
            // User doesn't have a merchant and is signing up so we do the usual signup
            $merchantInputData = [
                Merchant\Entity::SIGNUP_SOURCE  => $signupSource,
            ];
            if ($isInternal)
            {
                $merchantInputData[Merchant\Entity::ORG_ID] = $input[Merchant\Entity::ORG_ID];
            }
            $data = $this->createMerchant($user->toArray(), '', $businessName, $countryCode, false, $merchantInputData, null, false, $isInternal);
        }

        $this->trace->info(TraceCode::USER_CREATED_MERCHANT,
            [
                'userId'        => $user[Entity::ID],
                'merchantId'    => $data['id'],
            ]);

        $merchantId = $data['id'];

        $sendUslSalesforceEvent = $this->shouldSendUslSalesforceEvent($user[Entity::ID]);

        if ($sendUslSalesforceEvent === true) {

            $merchantCreateSalesForcePayload = [
                'user_id' => $user[Entity::ID],
                'merchant_id' => $merchantId,
                'country_code' => $countryCode,
                'product' => $input[Merchant\Entity::PRODUCT] ?? $this->auth->getRequestOriginProduct(),
            ];

            $this->app->salesforce->sendUslCreateUserAndMerchantDetails($merchantCreateSalesForcePayload);
        }

        if (empty($signupCampaign) === false)
        {
            $ddInput = [
                DeviceDetail\Entity::MERCHANT_ID        => $merchantId,
                DeviceDetail\Entity::USER_ID            => $user['id'],
                DeviceDetail\Entity::SIGNUP_CAMPAIGN    => $signupCampaign,
            ];
            $merchantCore = new Merchant\Core();
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $this->app['config']->get('app.workflow_segregation_store_user_signup_state'),
            ];
            $shouldStoreUserSignupState = $merchantCore->isSplitzExperimentEnable($properties,'enable');
            if ($skipWorkflowCreate && $shouldStoreUserSignupState) {
                // preserving user_signup_state inorder to identify the merchant state in usl, if user_signup_state is
                // mid_created and FE doesn't receive workflow_details_v2 in onboarding Meta then FE will redirect merchants
                // to Payment channel screen to create workflow.
                $ddInput[DeviceDetail\Entity::METADATA] = [
                    DeviceDetailConstants::USER_SIGNUP_STATE => 'mid_created'
                ];
            }
            (new DeviceDetail\Core)->createDeviceDetail($ddInput);
        }

        if ($user[Entity::SIGNUP_VIA_EMAIL] === 1)
        {
            $this->signUpSuccess($user, false, Constants::PASSWORD, null, $merchantId);
        }
        else if (empty($user[Entity::OAUTH_PROVIDER]) === true)
        {
            $this->signUpSuccess($user, false, Constants::OTP, null, $merchantId);
        }

        if ($skipWorkflowCreate)
        {
            return $data;
        }

        // Start the onboarding of merchant via PGOS
        if ($user[Entity::SIGNUP_VIA_EMAIL] === 1) {
            $input[Entity::EMAIL] = $user[Entity::EMAIL];
            try {
                if (empty($signupCampaign) === false)
                {
                    $merchant = $this->repo->merchant->findOrFail($merchantId);

                    $this->handlePGOSOnboardingForOAuthMerchants($merchant, $signupCampaign, $input, $user);
                }
            } catch (\Throwable $exception) {
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'message'       => "Error in handlePGOSOnboardingForOAuthMerchants()",
                    'merchant_id'   => $merchantId,
                    'error_message' => $exception->getMessage()
                ]);
            }
        } else if (empty($user[Entity::OAUTH_PROVIDER]) === true) {
            $input[Entity::CONTACT_MOBILE] = $user[Entity::CONTACT_MOBILE];
            if (!$this->isAssistedOnboardingSignupCampaign($signupCampaign)) {
                try {
                    $merchant = $this->repo->merchant->findOrFail($merchantId);

                    $this->handlePGOSOnboarding($merchant, $signupCampaign, $countryCode, $input, $user);
                } catch (\Throwable $exception) {
                    $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                        'message' => "Error in handlePGOSOnboarding()",
                        'merchant_id' => $merchant->getId(),
                        'error_message' => $exception->getMessage()
                    ]);
                }
            }
        }

        return $data;
    }

    public function getOnboardingService($input): array {
        $this->validator->validateInput('getOnboardingService', $input);
        $userId = $input[Entity::USER_ID];
        $merchantId = $input[Entity::MERCHANT_ID];

        $user = $this->repo->user->findOrFail($userId);
        $merchant = $this->repo->merchant->findOrFail($merchantId);
        $shouldOnboardViaPGOS = false;

        if ($user[Entity::SIGNUP_VIA_EMAIL] === 1)
        {
            $shouldOnboardViaPGOS = $this->shouldOnboardViaPGOSForOAuthMerchants($merchant, $input, $input[DeviceDetail\Entity::SIGNUP_CAMPAIGN]);
        }
        else if (empty($user[Entity::OAUTH_PROVIDER]) === true)
        {
            $shouldOnboardViaPGOS = $this->shouldOnboardViaPGOSForNonOAuthMerchants($merchant, $input[DeviceDetail\Entity::SIGNUP_CAMPAIGN], $input[Merchant\Entity::COUNTRY_CODE]);
        }

        $service = $shouldOnboardViaPGOS === true ? "pgos" : "api";
        return ['service' => $service];
    }

    public function get(string $id, array $input = []): array
    {
        // dashboard_guest is blocked as temp solution for SIBB-161
        $allowGuestAppIDOR = true;

        if ($this->auth->getInternalApp() === 'dashboard_guest')
        {
            $route = $this->app['api.route']->getCurrentRouteName();

            if (in_array($route,Constants::USER_FETCH_GUEST_BLACKLISTED_ROUTES,true))
            {
                $allowGuestAppIDOR = false;
            }

            $this->trace->warning(TraceCode::USER_FETCH_VIA_GUEST_AUTH,
                [
                    'route'     => $route,
                    'allowIDOR' => $allowGuestAppIDOR
                ]);
        }

        // dashboard_guest is blocked as temp solution for SIBB-161
        //if logged in user is PARTNER_AGENT then fetch $user object from id which is created while creating user for merchant
        if ($this->auth->isAdminAuth() === true or
            ($this->auth->isPrivilegeAuth() === true and
             $allowGuestAppIDOR === true) or  $this->auth->getUserRole()==Role::PARTNER_AGENT)
        {
            $user = $this->repo->user->findOrFailPublic($id);
        }
        else
        {
            // Using user context from header to avoid IDOR.
            $user = $this->auth->getUser();
        }

        $response = $this->core->get($user);

        $productSwitch = $input[Constants::PRODUCT_SWITCH] ?? 'false';

        if ($productSwitch === 'true')
        {
            $productSwitchOccurence = $this->productSwitchIfApplicable($response, $user);

            $this->trace->info(TraceCode::PRODUCT_SWITCH_REQUEST, [
                'user_id'                   => $user->getId(),
                'productSwitch'             => $productSwitch,
                'productSwitchOccurence'    => $productSwitchOccurence,
            ]);

            if ($productSwitchOccurence === true)
            {
                $response = $this->core->get($user);
            }
        }

        // Current merchant will be null in case of new signup and get function gets called through verify_user_otp_register

        $deviceDetail = $this->repo->user_device_detail->fetchByUserId($id);

        if (empty($deviceDetail) === false)
        {
            $response[DeviceDetail\Entity::SIGNUP_CAMPAIGN] = $deviceDetail->getSignupCampaign();
        }
        // In other instances when get function gets called we will always fetch user's owner signup's campaign
        $merchantId =  $input[Constants::MERCHANT_ID];
        $shouldGetMerchantSignupCampaign = $input['merchant_signup_campaign'] === 'true';

        if (empty($merchantId) === false)
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            if ($shouldGetMerchantSignupCampaign) {
                $user = $merchant->users()->where(Merchant\Detail\Entity::ROLE, '=', User\Role::OWNER)
                    ->where(Entity::ID, '=', $id)
                    ->first();
            } else {
                $user = $merchant->users()->where(Merchant\Detail\Entity::ROLE, '=', User\Role::OWNER)
                    ->first();
            }

            $ownerUserId = $user[Merchant\OwnerDetail\Entity::ID] ?? null;

            if (empty($ownerUserId) === false)
            {
                if ($shouldGetMerchantSignupCampaign) {
                    $deviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserId($merchantId, $ownerUserId);
                } else {
                    $deviceDetail = $this->repo->user_device_detail->fetchByUserId($ownerUserId);
                }

                if (empty($deviceDetail) === false)
                {
                    $response[DeviceDetail\Entity::SIGNUP_CAMPAIGN] = $deviceDetail->getSignupCampaign();
                }
                else
                {
                    $response[DeviceDetail\Entity::SIGNUP_CAMPAIGN] = null;
                }
            }
        }

        if ($this->auth->getInternalApp() === 'pgos')
        {
            if (empty($user[Entity::OAUTH_PROVIDER]) === false)
            {
                $response[Entity::OAUTH_PROVIDER] = json_decode($user[Entity::OAUTH_PROVIDER]);
            }
        }

        return $response;
    }

    /**
     * @param array $response
     * @param Entity $user
     * @return bool
     * In case of login via mobile, first login API of API is called and then users API of API is called
     * Since there is no call to users API of dashboard, product-switch doesn't happen
     * Hence we are doing product switch on login for mobile as they pass product_switch as "true" in body
     */
    public function productSwitchIfApplicable(array $response, Entity $user): bool
    {
        $this->trace->info(TraceCode::PRE_PRODUCT_SWITCH_APPLICABLE, [
            'response'  => $response,
            'user'      => $user,
        ]);

        $isProductSwitchRequiredResponse = $this->isProductSwitchRequired($response);

        $this->trace->info(TraceCode::POST_PRODUCT_SWITCH_APPLICABLE, [
            'isProductSwitchRequiredResponse'  => $isProductSwitchRequiredResponse,
        ]);

        if ($isProductSwitchRequiredResponse[Constants::PRODUCT_SWITCH_REQUIRED] === true)
        {
            $merchant = $this->repo->merchant->findOrFail($isProductSwitchRequiredResponse[Entity::MERCHANT_ID]);

            $merchantService = new Merchant\Service();

            $this->app['basicauth']->setMerchant($merchant);

            $this->app['basicauth']->setUser($user);

            $merchantService->switchProductMerchant();

            return true;
        }

        return false;
    }

    public function isProductSwitchRequired(array $user): array
    {
        $productSwitchMap = [
            Constants::PRODUCT_SWITCH_REQUIRED => false,
            Entity::MERCHANT_ID                => '',
        ];

        $isBankingRequest = $this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING;

        $merchants = $user[Entity::MERCHANTS];

        $isUserOwnerForSwitchProduct = false;

        $isUserRelatedToCurrentProduct = false;

        foreach ($merchants as $merchant)
        {
            if (($isBankingRequest === true &&
                    empty($merchant[Entity::BANKING_ROLE]) === false) ||
                ($isBankingRequest === false &&
                    empty($merchant[Entity::ROLE]) === false))
            {
                $isUserRelatedToCurrentProduct = true;
            }

            if (($isBankingRequest === true &&
                    $merchant[Entity::ROLE] === Entity::OWNER) ||
                ($isBankingRequest === false &&
                    $merchant[Entity::BANKING_ROLE] === Entity::OWNER))
            {
                $isUserOwnerForSwitchProduct = true;

                $productSwitchMap[Entity::MERCHANT_ID] = $merchant[Entity::ID];
            }
        }

        if ($isUserRelatedToCurrentProduct === false &&
            $isUserOwnerForSwitchProduct === true)
        {
            $productSwitchMap[Constants::PRODUCT_SWITCH_REQUIRED] = true;
        }

        $this->trace->info(TraceCode::PRODUCT_SWITCH_REQUIRED, [
            'productSwitchMap' => $productSwitchMap,
            'user'             => $user,
        ]);

        return $productSwitchMap;
    }

    public function getActorInfo(string $id): array
    {
        $this->trace->info(TraceCode::FETCH_ACTOR_INFO_REQUEST,
            [
                'user_id' => $id
            ]);

        return $this->core->getActorInfo($id);
    }

    public function getUserEntity(string $id): array
    {
        if ($this->auth->isPrivilegeAuth() === true)
        {
            return $this->repo->user->findOrFailPublic($id)->toArrayPublic();
        }

        return [];
    }

    public function updateMerchantManageTeamWithOtpVerification(string $userId, array $input): array
    {
        $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        $inputAction = $input['action'] ?? '';
        $input = array_except($input, ['action']);

        $this->core->verifyOtp($input + ['action' => 'update_user'],
            $this->merchant,
            $this->user,
            $this->mode === Mode::TEST);

        $updateUserInput = array_except($input, ['otp', 'token']);
        $updateUserInput += ['action' => $inputAction];

        return $this->updateMerchantManageTeam($userId, $updateUserInput);
    }

    public function updateMerchantManageTeam(string $userId, array $input): array
    {
        $input['merchant_id'] = $this->merchant->getId();

        $role = '';

        if (array_key_exists(Entity::ROLE, $input))
        {
            $role = $input[Entity::ROLE];
        }

        $teamData = [
            'merchant_id' => $input['merchant_id'],
            'user_id'     => $userId,
            'role'        => $role,
        ];

        $this->validator->validateInput('teamManagement', $teamData);

        $user = $this->updateUserMerchantMapping($userId, $input);

        $response = $user->toArrayPublic();

        if ($this->auth->getRequestOriginProduct() === Product::BANKING &&
            isset($input['action']) === true &&
            $input['action'] === 'detach')
        {
            $oAuthTokenService = new OAuthToken\Service();

            $oAuthTokenService->revokeTokenForMerchantUserPair(app('basicauth')->getMerchant(),OAuthApplicationConstants::RX_MOBILE_TOKEN_TYPE, $userId);
        }

        return $response;
    }

    public function bulkUpdateUserMapping(array $input)
    {
        foreach ($input as $row)
        {
            $this->validator->validateInput('bulk_user_mapping', $row);
        }

        foreach ($input as $row)
        {
            $this->trace->info(TraceCode::USER_ROLE_MAPPING, $row);

            $teamInput = [
                Entity::MERCHANT_ID => $row[Entity::MERCHANT_ID],
                Entity::PRODUCT     => $row[Entity::PRODUCT],
                Entity::ROLE        => $row[Entity::ROLE],
                Entity::ACTION      => $row[Entity::ACTION],
            ];

            $this->updateUserMerchantMapping($row[Entity::USER_ID], $teamInput);
        }

        return [];
    }

    /**
     * Upgrades given user to merchant.
     * @param array $input
     *
     * @return array
     */
    public function upgradeUserToMerchant(array $input)
    {
        $userId = $input['user_id'];

        $user = $this->repo->user->findOrFailPublic($userId)->toArrayPublic();

        $merchantData = [
            Merchant\Entity::NAME          => $input['business_name'],
            Merchant\Entity::EMAIL         => $user['email'],
            Merchant\Entity::SIGNUP_SOURCE => $this->auth->getRequestOriginProduct()
        ];

        $data = $this->createMerchantFromUser($merchantData, $user);

        return $data;
    }

    /**
     * Resend verification mail for not confirmed user.
     * @param string $userId
     *
     * @return array
     */
    public function resendVerificationMail()
    {
        $dashboardHeaders = $this->auth->getDashboardHeaders();

        $user = $this->repo->user->findOrFailPublic($dashboardHeaders['user_id']);

        $data = $this->sendConfirmationMail($user);

        $this->core->trackOnboardingEvent($user->getEmail(),
                                         EventCode::SIGNUP_RESEND_VERIFICATION_EMAIL_SUCCESS);

        return $data;
    }

    /**
     *  * Resend verification mail with OTP for not confirmed user.
     * @param $input
     *
     * @return mixed
     */
    public function resendVerificationOtp($input)
    {
        $this->user->getValidator()->validateResendEmailWithOtpOperation($input);

        $dashboardHeaders = $this->auth->getDashboardHeaders();

        $merchant = $this->auth->getMerchant();

        $merchantData = [];

        $user = $this->repo->user->findOrFailPublic($dashboardHeaders['user_id']);

        LoginSignupRateLimit::validateKeyLimitExceeded(
            $user->getId(),
            Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX,
            Constants::EMAIL_VERIFICATION_OTP_SEND_TTL,
            Constants::EMAIL_VERIFICATION_OTP_SEND_THRESHOLD
        );

        if (empty($input['token']) === false)
        {
            $merchantData['token'] = $input['token'];
        }

        if ($user->getConfirmedAttribute() === false)
        {
            $requestOriginProduct = $this->auth->getRequestOriginProduct();

            $isProductBanking = ($requestOriginProduct === Product::BANKING);

            $inputData = ["isRequestFromXVerifyEmail" => $isProductBanking];

            $data = $this->sendOtpEmailVerification($user, $merchant, $merchantData, $inputData);
        }
        else
        {
            // if user is already confirmed then sending confirm as true.
            return ['confirm' => true];
        }

        $this->core->trackOnboardingEvent($user->getEmail(),
                                         EventCode::SIGNUP_RESEND_VERIFICATION_EMAIL_OTP_SUCCESS);

        return $data;
    }

    public function isUnifiedRequest($origin): bool
    {
        foreach (Constants::UNIFIED_ORIGINS as $unified_origin) {
            if ($origin == $unified_origin) {
              return true;
            }
        }

        return false;
    }
    /**
     * @throws \libphonenumber\NumberParseException
     * @throws BadRequestException
     */
    public function postResetPassword(array $input)
    {
        $this->validator->validateInput('resetPassword', $input);

        $this->trace->info(TraceCode::USER_PASSWORD_RESET_REQUEST, $input);

        if (isset($input['email']) === true)
        {
            $email = $input['email'];

            //find or fail public by email.
            /** @var User\Entity $user */
            if($this->app['razorx']->getTreatment(mb_strtolower($email), Constants::FETCH_USER_EMAIL_CASE_INSENSITIVE, Mode::LIVE) === 'on')
            {
                $user = $this->repo->user->getUserFromEmailCaseInsensitive($email);
            }
            else
            {
                $user = $this->repo->user->getUserFromEmail(mb_strtolower($email));
            }

            if (empty($user) === true)
            {
                $this->trace->info(TraceCode::USER_NOT_FOUND,
                    [
                        'email' => mask_email($email)
                    ]);

                $this->core->trackOnboardingEvent($email,
                                                 EventCode::MERCHANT_ONBOARDING_RESET_PASSWORD_FAILURE,
                                                 new BaseException(Constants::USER_EMAIL_NOT_FOUND));
            }
            else
            {
                LoginSignupRateLimit::validateKeyLimitExceeded(
                    $user->getId(),
                    Constants::RESET_PASSWORD_RATE_LIMIT_SUFFIX,
                    Constants::RESET_PASSWORD_RATE_LIMIT_TTL,
                    Constants::RESET_PASSWORD_RATE_LIMIT_THRESHOLD
                );

                $orgId = $this->auth->getOrgId();

                $org = $this->repo->org->findByPublicId($orgId);

                $showAxisSupportUrl = $org->isFeatureEnabled(FeatureConstant::SHOW_SUPPORT_URL);
                $isCustomOnboardingEmail = $org->isFeatureEnabled(FeatureConstant::CUSTOM_ONBOARDING_EMAILS);

                //get Org and send it to mailer, deal with other orgs as well.
                $org = $org->toArrayPublic();

                $origin  = $_SERVER['HTTP_X_REQUEST_ORIGIN'];
                // if orgId = org_abc then extractedOrgId will be abc
                $extractedOrgId = Org\Entity::verifyIdAndStripSign($orgId);

                if ($this->isUnifiedRequest($origin)) {
                    switch ($extractedOrgId) {
                        case env('CURLEC_ORG_ID'):
                            $unified_hostname = env('CURLEC_ACCOUNTS_URL');
                            break;
                        case env('AXIS_ORG_ID'):
                            $unified_hostname = env('AXIS_ACCOUNTS_URL');
                            break;
                        case env('HDFC_ORG_ID'):
                            $unified_hostname = env('HDFC_ACCOUNTS_URL');
                            break;
                        case env('YES_ORG_ID'):
                            $unified_hostname = env('YES_ACCOUNTS_URL');
                            break;
                        case env('INDUS_ORG_ID'):
                            $unified_hostname = env('INDUS_ACCOUNTS_URL');
                            break;
                        case env('IDFC_ORG_ID'):
                            $unified_hostname = env('IDFC_ACCOUNTS_URL');
                            break;
                        case env('RAZORPAY_ORG_ID'):
                            $unified_hostname = env('RAZORPAY_ACCOUNTS_URL');
                            break;
                    }
                }


                $org['hostname'] = $this->auth->getOrgHostName();
                $org['showAxisSupportUrl'] = $showAxisSupportUrl;
                $org['isCustomOnboardingEmail'] = $isCustomOnboardingEmail;

                $requestOriginProduct = $this->auth->getRequestOriginProduct();

                $passwordResetMail = new UserMail\PasswordReset($user->toArrayPublic(), $org, $requestOriginProduct, $unified_hostname);

                Mail::send($passwordResetMail);

                $this->core->trackOnboardingEvent($user->getEmail(),
                                                 EventCode::MERCHANT_ONBOARDING_RESET_PASSWORD_SUCCESS);
            }
        }
        else if(isset($input['contact_mobile']) === true)
        {
            $contactMobile = $input['contact_mobile'];

            $user = $this->core->getUserFromMobile($contactMobile);

            if (empty($user) === true)
            {
                $this->trace->info(TraceCode::USER_NOT_FOUND,
                    [
                        'contact_mobile' => mask_phone($contactMobile)
                    ]);

                $this->core->trackOnboardingEventByContactMobile($contactMobile,
                    EventCode::MERCHANT_ONBOARDING_RESET_PASSWORD_FAILURE,
                    new BaseException(Constants::USER_CONTACT_MOBILE_NOT_FOUND));
            }
            else
            {
                $this->trace->info(TraceCode::USER_DETAILS,
                    [
                        'user_id' => $user->getId(),
                    ]);

                $resetPasswordUsingSmsExperiment = $this->app->razorx->getTreatment(
                    $user->getId(),
                    Merchant\RazorxTreatment::RESET_PASSWORD_USING_SMS,
                    $this->mode
                );

                if(strtolower($resetPasswordUsingSmsExperiment) === Merchant\RazorxTreatment::RAZORX_VARIANT_ON)
                {
                    LoginSignupRateLimit::validateKeyLimitExceeded(
                        $user->getId(),
                        Constants::RESET_PASSWORD_RATE_LIMIT_SUFFIX,
                        Constants::RESET_PASSWORD_RATE_LIMIT_TTL,
                        Constants::RESET_PASSWORD_RATE_LIMIT_THRESHOLD
                    );

                    if($user->isContactMobileVerified() === true)
                    {
                        $this->processResetPasswordByMobile($user);
                    }
                    else
                    {
                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED,
                            null,
                            [
                                'internal_error_code' => ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED,
                            ]);
                    }
                }
            }
        }

        return ['success' => true];
    }

    public function processResetPasswordByMobile(Entity $user)
    {
      $token = $this->getTokenWithExpiry(
          $user['id'],
          User\Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME
      );

      $resetPasswordUrl = $this->getResetPasswordUrlForMobile($user, $token);

      $this->sendResetPasswordSMS($user, $resetPasswordUrl);
    }

    public function getResetPasswordUrlForMobile(Entity $user, string $token)
    {
        $product = $this->auth->getRequestOriginProduct();

        if($product === Product::BANKING)
        {
            $passwordResetUrl = 'https://' . parse_url(config('applications.banking_service_url'), PHP_URL_HOST).'/forgot-password#token='. $token . '&contact_mobile=' . $user['contact_mobile'];
        }
        else
        {
            $hostName = $this->auth->getOrgHostName();
            $passwordResetUrl = 'https://' . $hostName . '/#/access/resetpassword?contact_mobile='.$user['contact_mobile'].'&token='.$token;
        }

        return $this->elfin->shorten($passwordResetUrl);
    }


    public function getSMSPayloadForResetPassword(Entity $user, string $url)
    {
        $receiver = $user[Entity::CONTACT_MOBILE];
        $params = [
            'link' => $url,
        ];

        $payload = [
            'ownerId'               => $user->getId(),
            'ownerType'             => Constants::MERCHANT,
            'orgId'                 => $this->auth->getOrgId(),
            'templateName'          => 'sms.user.reset_password',
            'templateNamespace'     => 'partnerships',
            'language'              => 'english',
            'sender'                => 'RZRPAY',
            'destination'           => $receiver,
            'contentParams'         => $params
        ];

        return $payload;
    }

    public function sendResetPasswordSMS(Entity $user, string $url)
    {
        $payload = $this->getSMSPayloadForResetPassword($user, $url);

        return $this->app['stork_service']->sendSms($this->mode, $payload);
    }

    /**
     * This email goes to sub-merchant user when the aggregator/partner
     * tries to create a login for him but the user account already
     * exists and we just attach it to the sub-merchant in question.
     *
     * @param  Entity          $user
     * @param  Merchant\Entity $submerchant
     *
     * @return array
     */
    public function postAccountMappedEmail(Entity $user, Merchant\Entity $submerchant)
    {
        $orgId = $this->auth->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

        $org['hostname'] = $this->auth->getOrgHostName();

        $submerchantArray = $submerchant->toArrayPublic();

        $accountMappedMail = new UserMail\MappedToAccount($user, $org, $submerchantArray);

        Mail::queue($accountMappedMail);

        return ['success' => true];
    }

    /**
     * Sends Linked Account access email with user password reset link.
     *
     * @param Entity            $user
     * @param Merchant\Entity   $subMerchant
     *
     * @return array
     */
    public function postLinkedAccountAccessEmail(Entity $user, Merchant\Entity $subMerchant): array
    {
        $orgId = $this->auth->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

        $org['hostname'] = $this->auth->getOrgHostName();

        $linkedAccountAccessMail = new UserMail\LinkedAccountUserAccess($user, $org, $subMerchant);

        Mail::queue($linkedAccountAccessMail);

        return ['success' => true];
    }

    public function getTokenWithExpiry(string $userId, int $expiry): string
    {
        $this->trace->info(
            TraceCode::USER_PASSWORD_RESET_TOKEN_GENERATE,
            [
                'user_id' => $userId,
                'expiry'  => $expiry,
            ]);

        $userCore = $this->core;

        $expiryTime = Carbon::now()->timestamp + $expiry;

        $token = $userCore->generateToken();

        $user = $this->repo->user->findOrFailPublic($userId);

        $userCore->savePasswordResetTokenAndExpiry($user, $token, $expiryTime);

        return $token;
    }

    public function setAndSaveResetPasswordToken($user, $token)
    {
        $user->setPasswordResetToken($token);

        $this->repo->user->saveOrFail($user);
    }

    /**
     * @param  array $input
     *
     * @return array
     *
     * @throws Exception\BadRequestException
     */
    public function changePasswordByToken(array $input)
    {
        $this->validator->validateInput('changePasswordToken', $input);

        if(isset($input['email']))
        {
            $email = $input['email'];

            /** @var Entity $user */
            if($this->app['razorx']->getTreatment(mb_strtolower($email), Constants::FETCH_USER_EMAIL_CASE_INSENSITIVE, Mode::LIVE) === 'on')
            {
                $user = $this->repo->user->getUserFromEmailCaseInsensitive($email);
            }
            else
            {
                $user = $this->repo->user->getUserFromEmail(mb_strtolower($email));
            }
        }
        else
        {
            $contact_mobile = $input['contact_mobile'];

            $user = $this->core->getUserFromMobile($contact_mobile);
        }

        $expiry = $user->getPasswordResetExpiry();

        $now = Carbon::now()->getTimestamp();

        if ($expiry < $now)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }

        $token = $user->getPasswordResetToken();

        if ((empty($token) === true) or (hash_equals($token, $input['token']) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }
        else
        {
            LoginSignupRateLimit::validateKeyLimitExceeded(
                $user->getId(),
                Constants::CHANGE_PASSWORD_RATE_LIMIT_SUFFIX,
                Constants::CHANGE_PASSWORD_RATE_LIMIT_TTL,
                Constants::CHANGE_PASSWORD_RATE_LIMIT_THRESHOLD
            );

            $this->core->setNewPassword($user, $input);

            if (isset($input[Entity::EMAIL]) === true)
            {
                $this->core->trackOnboardingEvent($input[Entity::EMAIL], EventCode::MERCHANT_RESET_PASSWORD_BY_TOKEN_SUCCESS);
            }

            // Password reset via mail essentially confirms the email.
            if ($user->getConfirmedAttribute() === false)
            {
                $this->core->confirm($user);
            }
        }

        $orgId = $this->auth->getOrgId();
        $org = $this->repo->org->findByPublicId($orgId);

        $isOrg2FaEnforced = ($org->isMerchant2FaEnabled() === true);

        $isUser2FaEnabled = (
            ($user->isSecondFactorAuth() === true) or
            ($user->isSecondFactorAuthEnforced() === true)
        );

        if (($isUser2FaEnabled === true) OR ($isOrg2FaEnforced === true))
        {
            if (($user->isOwner() === true) and
                ($user->isAccountLocked() === true))
            {
                $this->trace->info(TraceCode::USER_ACCOUNT_LOCK_UNLOCK_ACTION, [
                    Entity::USER_ID => $user->getId(),
                    Entity::ACTION  => Constants::UNLOCK
                ]);

                $user->setWrong2faAttempts(0);

                $user->setAccountLocked(false);

                $this->repo->saveOrFail($user);
            }
        }

        $this->revokeTokenOnPasswordChange($user);

        return ['success' => true, 'user_id' => $user->getId()];
    }

    /**
     * This is to revoke oauth token for mobile in case password is changed for the user
     * @param Entity $user
     * @return void
     * @throws Exception\ServerErrorException
     */
    private function revokeTokenOnPasswordChange(Entity $user)
    {
        if ((new Merchant\Acs\AsvRouter\AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__))
        {
            $bankingMerchantIds = $user->getBankingMerchantIdsForRole();
            $bankingMerchants = $this->repo->merchant->findMerchantsByIds($bankingMerchantIds);
        }
        else
        {
            $bankingMerchants = $user->bankingMerchants()->get();
        }

        $oAuthTokenService = new OAuthToken\Service();

        foreach ($bankingMerchants as $merchant)
        {
            $oAuthTokenService->revokeTokenForMerchantUserPair($merchant, OAuthApplicationConstants::RX_MOBILE_TOKEN_TYPE, $user->getId());
        }
    }

    public function mobileOauthLogout(array $input): array
    {
        return $this->revokeOauthToken($input);
    }

    private function revokeOauthToken(array $input): array
    {
        $merchant = $this->merchant;

        $oAuthTokenService = new OAuthToken\Service();

        $response = $oAuthTokenService->revokeTokenOnLogout($merchant, $input);

        return $response;
    }

    /**
     * fetch clientId if present in cookie and return as visitorId
     *
     * @return string
     */
    public function fetchVisitorIdFromCookie()
    {
        if (empty(\Cookie::get(Entity::CLIENT_ID)) === false)
        {
            $clientId = \Cookie::get(Entity::CLIENT_ID);

            return $clientId;
        }

        return '';
    }

    public function addUtmParameters(& $data)
    {
        if (empty(\Cookie::get('rzp_utm')) === false)
        {
            // For some reason, the cookie has extra double quotes at the
            // beginning and end, so trimming that. If not removed, json_decode fails.
            $this->trace->info(TraceCode::RZP_UTM, ['rzp_utm_cookie' => \Cookie::get('rzp_utm')]);
            $cookieValue = trim(\Cookie::get('rzp_utm'), '"');
            $utmParams = json_decode($cookieValue, true);
            $data[Constants::CTA]       = $utmParams[Constants::CTA] ?? '';
            $data[Constants::WEBSITE]   = $utmParams[Constants::WEBSITE] ?? '';
            $data[Constants::FC_SOURCE] = $utmParams[Constants::FC_SOURCE] ?? '';
            $data[Constants::LC_SOURCE] = $utmParams[Constants::LC_SOURCE] ?? '';

            $data[Constants::BANNER_ID]          = $utmParams[Constants::BANNER_ID] ?? '';
            $data[Constants::BANNER_CLICKSOURCE] = $utmParams[Constants::BANNER_CLICKSOURCE] ?? '';
            $data[Constants::BANNER_CLICKTIME] = $utmParams[Constants::BANNER_CLICKTIME] ?? '';

            foreach (Constants::$clickIdentifier as $clickId)
            {
                $data['first_' . $clickId] = $utmParams['first_' . $clickId] ?? '';
                $data['final_' . $clickId] = $utmParams['final_' . $clickId] ?? '';
            }

            if (empty($utmParams[Constants::ATTRIBUTIONS]) === false)
            {
                $utmParams[Constants::ATTRIBUTIONS][1] = $utmParams[Constants::ATTRIBUTIONS][1] ??
                    $utmParams[Constants::ATTRIBUTIONS][0];

                foreach (Constants::$attributionList as $attribution)
                {
                    $data['first_' . $attribution] = $utmParams[Constants::ATTRIBUTIONS][0][$attribution] ?? '';
                    $data['final_' . $attribution] = $utmParams[Constants::ATTRIBUTIONS][1][$attribution] ?? '';
                }
            }
        }
    }

    public function getUtmParameters(): array
    {
        $utmCookie = \Cookie::get('rzp_utm');
        $this->trace->info(TraceCode::RZP_UTM, ['rzp_utm_cookie' => $utmCookie]);

        if (empty($utmCookie)) {
            return [];
        }

        $cookieValue = trim($utmCookie, '"');
        $utmParams = json_decode($cookieValue, true);

        return $utmParams ?: [];
    }

    /**
     * If we create a new user we send him a password reset link to start using dasboard
     * The reset flow will also confirm the user in the process.
     * If we find an existing user with the sub-merchant email then we send a mail informing
     * that he has access to sub-merchant account also now.
     *
     * @param User\Entity     $subMerchantUser
     * @param Merchant\Entity $subMerchant
     * @param boolean         $createdNew
     *
     */
    public function sendAccountLinkedCommunicationEmail(
                                                        User\Entity $subMerchantUser,
                                                        Merchant\Entity $subMerchant,
                                                        bool $createdNew)
    {

        $response = [];

        if (($createdNew === true) and ($subMerchant->isLinkedAccount() === true))
        {
            $response = $this->postLinkedAccountAccessEmail($subMerchantUser, $subMerchant);
        }
        else if ($createdNew === true)
        {
            $response = $this->postResetPassword([User\Entity::EMAIL => $subMerchantUser[User\Entity::EMAIL]]);
        }
        else
        {
            $response = $this->postAccountMappedEmail($subMerchantUser, $subMerchant);
        }

        return $response;
    }

    public function syncMerchantUserOnProducts(string $merchantId)
    {
        $userRole = null;

        $product = $this->auth->getRequestOriginProduct();

        $user = $this->auth->getUser();

        $switchProduct = ($product === Product::BANKING) ? Product::PRIMARY : Product::BANKING;

        $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                                                                     $user->getId(),
                                                                     null,
                                                                     $switchProduct);

        if (empty($userMapping) === false)
        {
            $currentUserRole = $userMapping->pivot->role;

            if (in_array($currentUserRole, Role::BANKING_ROLES, true) === true)
            {
                $this->merchantService->switchProductMerchant($product);

                $userRole = $currentUserRole;
            }
        }

        return $userRole;
    }

    /**
     * This will assign applicable role to the product by checking it's origin.
     * PG Owner/Admin role on BB will be Owner/Admin. rest all other roles will be rejected and viceversa.
     *
     * @param $product
     *
     * @return null|\RZP\Models\User\Entity
     */
    public function addProductSwitchRole($product)
    {
        $user = $this->auth->getUser();

        $product = $product ?? $this->auth->getRequestOriginProduct();

        $merchantId = $this->auth->getMerchantId();

        // Check if a role for this user already exists with the existing product merchant user mapping.
        $userMapping = $this->getMerchantUserMappingForProduct($product, $merchantId, $user->getId());

        if (empty($userMapping) === true)
        {
            // Since we have user roles in headers we can get the opposite product easily.
            // In switch we have to assign the role for merchants with only the opposite product side role.
            // Like Owner in PG will be Owner in BB and Admin in BB will be Admin in PG.

            $switchProduct = ($product === Product::BANKING) ? Product::PRIMARY : Product::BANKING;

            $userMapping = $this->getMerchantUserMappingForProduct($switchProduct, $merchantId, $user->getId());

            $productRole = null;

            if (empty($userMapping) === false)
            {
                $productRole = $userMapping->pivot->role;
            }

            $userMerchantMappingInputData = [
                'action'      => 'attach',
                'role'        => $productRole,
                'merchant_id' => $merchantId,
                'product'     => $product,
            ];

            $user = $this->core->updateUserMerchantMapping($user, $userMerchantMappingInputData);
        }

        return $user;
    }

    public function sendOtp(array $input)
    {
        $this->user->getValidator()->validateSendOtpOperation($input);

        return $this->core()->sendOtp($input, $this->merchant, $this->user);
    }

    public function sendOtpWithContact(array $input)
    {
        $this->user->getValidator()->validateInput('sendOtpWithContact', $input);

        return $this->core()->sendOtpWithContact($input, $this->merchant, $this->user);
    }

    public function verifyOtpWithToken(array $input)
    {
        $this->user->getValidator()->validateInput('verifyOtp', $input);

        return $this->core()->verifyOtp($input, $this->merchant, $this->user);
    }

    public function verifyContactWithOtp(array $input): array
    {
        $this->user->getValidator()->validateVerifyContactWithOtpOperation($input);

        $this->core()->verifyContactWithOtp($input, $this->merchant, $this->user);

        /** @var HubspotClient $hubspotClient */
        $hubspotClient = $this->app->hubspot;

        if (empty($this->merchant->getEmail()) === false)
        {
            $hubspotClient->trackHubspotEvent($this->merchant->getEmail(), [
                'contact_verified' => true
            ]);
        }

        return $this->user->toArrayPublic();
    }


    public function switchMerchantWithToken(array $input): array
    {
        $user = $this->auth->getUser();

        $beforeSwitchMidAccessToken = $input[OAuthApplicationConstants::ACCESS_TOKEN];

        $clientId = $input[OAuthApplicationConstants::CLIENT_ID];

        (new Validator)->validateInput('switch_merchant', $input);

        $afterSwitchMid = $input[OAuthApplicationConstants::MERCHANT_ID];

        $userMapping = $this->repo->merchant->getMerchantUserMapping($afterSwitchMid, $user->getId(), null, $this->auth->getRequestOriginProduct());

        $response = [
            'access'   => false,
            'merchant' => $afterSwitchMid,
        ];

        if (empty($userMapping) === false)
        {
            $response['access'] = true;
        }

        if ($response['access'] === true)
        {
            $response = $this->revokeAccessTokenForExistingMerchant($response, $this->merchant, $clientId, $beforeSwitchMidAccessToken);

            $response = $this->addOauthTokenIfApplicable($response, $user->getUserId(), $afterSwitchMid, $user, 2);
        }
        return $response;
    }


    public function mobileOauthRefreshToken(array $input): array
    {
        $refreshToken   = $input[OAuthApplicationConstants::REFRESH_TOKEN];

        $merchantId     = $input[OAuthApplicationConstants::MERCHANT_ID];

        $clientId       = $input[OAuthApplicationConstants::CLIENT_ID];

        (new Validator)->validateInput('new_access_token', $input);

        return  $this->refreshAccessToken($merchantId, $clientId, $refreshToken);
    }

    public function verifyContactForRblIfOwner(array $input)
    {
        $isUserOwnerForBanking = (new User\Service())->doesUserHaveRoleForMerchantAndProduct(
            $this->user->getId(),
            $this->merchant->getId(),
            Role::OWNER,
            Product::BANKING);

        if ($isUserOwnerForBanking === true &&
            isset($input['contact_mobile']) &&
            $this->user->getContactMobile() === $input['contact_mobile'])
        {
            $this->trace->info(TraceCode::USER_VERIFIED_VIA_RBL_CA, [
                'user'     => $this->user->getId(),
                'merchant' => $this->merchant->getId(),
            ]);

            $this->core()->verifyUserContactForOwnerInRbl($this->user);
        }
    }

    public function verifyOtpAndUpdateContactMobile(array $input): array
    {
        $this->user->getValidator()->validateInput('verify_otp_from_update', $input);

        $response = $this->core()->verifyOtpAndUpdateContactMobile($input, $this->merchant, $this->user);

        return $response->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function verifyEmailWithOtp(array $input): array
    {
        $user = $this->user;

        $userRole = $this->auth->getUserRole();

        // If the actual user accessing this API is POS sales agent then override the user to the owner of the merchant
        // This is done to ensure that the OTP verification happens for the correct user
        if ($userRole === User\Role::RAZORPAY_SALES)
        {
            $salesUserId = $user->getId();

            $merchantId = $this->app['basicauth']->getMerchantId();

            $merchantUser = $this->repo->merchant_user->findByRolesAndMerchantId([User\Role::OWNER], $merchantId)->first();

            $user = $this->repo->user->findOrFail($merchantUser->user_id);

            $this->trace->info(
                TraceCode::VERIFY_EMAIL_WITH_OTP_OVERRIDE_USER_FOR_RAZORPAY_SALES_ROLE,
                [
                    'merchant_id'       => $merchantId,
                    'user_id'           => $merchantUser->user_id,
                    'rzp_sales_user_id' => $salesUserId
                ]
            );
        }

        if ($user->getConfirmedAttribute() === false)
        {
            $user->getValidator()->validateVerifyEmailWithOtpOperation($input);

            $requestOriginProduct = $this->auth->getRequestOriginProduct();

            $action = ($requestOriginProduct === Product::BANKING) ? 'x_verify_email' : 'verify_email';

            $this->core()->verifyEmailWithOtp($input, $user, $this->merchant, $action);

            LoginSignupRateLimit::resetKey($user->getId(), Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX);
        }

        $response['user'] = $user->toArrayPublic();

        return $response;
    }

    /**
     * Change 2fa setting of user (enable/disable)
     *
     * @param array  $input
     *
     * @return array
     */
    public function change2faSetting(array $input)
    {
        $this->user->getValidator()->validateInput('change2faSetting', $input);

        return $this->core()->change2faSetting($this->user, $input);
    }

    /**
     * @param  string $id
     * @param  array  $input
     *
     * @return array
     */
    public function resetUserPassword(string $id, array $input)
    {
        /** @var Admin\Entity $admin */
        $admin = $this->auth->getAdmin();

        $merchant = $this->auth->getMerchant();

        $user = $this->repo->user->findOrFailPublic($id);

        $userValidator = $this->validator;

        // TODO: Remove this after handling properly in AdminAccess middleware
        // It does not handle org id as of now, also need to test admin-
        // merchant relations
        (new Merchant\Validator)->validateAdminMerchantAccess($admin, $merchant);

        $userValidator->validateMerchantUserRelation($merchant, $user);

        $userValidator->validateInput('changePasswordAdmin', $input);

        $this->core()->edit($user, $input, 'changePasswordAdmin');

        return ['success' => true];
    }


    /**
     * An additional user authorization token also has to be given in input.
     *  This token is generated by using api on the route "user_verify_through_email".
     *
     * @param array $input
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function editContactMobile(array $input)
    {
        $this->validator->validateInput('edit_contact_mobile', $input);

        $token = $input[Entity::OTP_AUTH_TOKEN];

        $this->app['token_service']->verify($token, $this->user->getId());

        return $this->core()->editContactMobile($input, $this->user);
    }

    public function isPartnerMerchant($merchantId): bool
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $isPartner = $merchant->getPartnerType();

        if (empty($isPartner) === true) {

            $isSubMerchantOfPartner  = $this->repo->merchant_access_map->getByMerchantId($merchantId);

            if (empty($isSubMerchantOfPartner) == true) {
                return false;
            }
        }

        return true;
    }

    public function fetchMerchantIdsForUserContact(string $contact)
    {
        $phoneNumber = new PhoneBook($contact);

        $phoneNumber = $phoneNumber->format(PhoneBook::DOMESTIC);

        $user = $this->repo->user->getUserFromMobileOrFail($phoneNumber);

        $nonPartnerIds = [];

        foreach ($user->getPrimaryMerchantIds() as $id)
        {
            if($this->isPartnerMerchant($id) === false)
            {
                array_push($nonPartnerIds, $id);
            }
        }

        return [
            'owner_ids' => $nonPartnerIds,
            'user' => $user->toArrayPublic(),
            ];
    }

    public function fetchPrimaryUserContact(string $merchantId) {

        $merchantUsers = $this->repo->merchant_user->fetchPrimaryUserIdForMerchantIdAndRole($merchantId);

        $contacts = [];

        foreach ($merchantUsers as $key => $value)
        {
            $userContact = $this->repo->user->findOrFail($value)->getContactMobile();

            $phoneNumber = new PhoneBook($userContact);

            $phoneNumber = $phoneNumber->format(PhoneBook::E164);

            array_push($contacts, $phoneNumber);
        }

        return ['owner_contacts' => $contacts];
    }

    /**
     * An user authorization token also has to be given in input.
     * This token is provided by using api on the route "user_verify_through_mode".
     *
     * @param array $input
     * @return mixed
     * @throws Exception\BadRequestValidationFailureException|BadRequestException
     */
    public function sendOtpForContactMobileUpdate(array $input)
    {
        $user = $this->user;

        // rate limit 9 send OTP attempts very 1800 s for a user
        $this->validator->validateSendOtpLimitNotExceeded($user);

        $cacheKey = $this->getThrottleContactMobileCacheKey($user);

        $attempts = Cache::get($cacheKey, 0);

        $this->trace->info(TraceCode::THROTTLE_CONTACT_MOBILE_KEY_CACHE_DETAIL, [
            'cache_key'   => $cacheKey,
            'attempts'    => $attempts
        ]);

        $this->validator->validateThrottleContactMobileLimit($attempts);

        $this->validator->validateInput('edit_contact_mobile', $input);

        $this->validator->validateUniqueNumberExcludingCurrentUser($user, $input[Entity::CONTACT_MOBILE]);

        $token = $input[Entity::OTP_AUTH_TOKEN];

        $this->app['token_service']->verify($token, $user->getId());

        $this->core()->sendOtpForContactMobileUpdate($input, $user);

        return ['contact number' => $user->getContactMobile()];
    }

    protected function getThrottleContactMobileCacheKey(Entity $user)
    {
        return sprintf(Constants::THROTTLE_UPDATE_CONTACT_MOBILE_CACHE_KEY_PREFIX, $user->getId());
    }

    public function updateContactMobile(array $input)
    {
        $this->validator->validateInput('update_contact_mobile', $input);

        $user = $this->repo->user->findOrFailPublic($input[Entity::USER_ID]);

        return $this->core()->updateContactMobile($input, $user);
    }

    public function accountLockUnlock(string $userId, string $action): array
    {
        $accountLockData = [
            Entity::USER_ID => $userId,
            Entity::ACTION  => $action,
        ];

        $this->validator->validateInput('user_account_lock_unlock', $accountLockData);

        $user = $this->repo->user->findOrFailPublic($userId);

        return $this->core()->accountLockUnlock($user, $action);
    }

    public function verifyUserThroughEmail($input)
    {
        $merchant = $this->auth->getMerchant();

        $user = $this->auth->getUser();

        return $this->core()->verifyUserThroughEmail($input, $merchant, $user);
    }

    public function verifyUserThroughMode($input)
    {
        $merchant = $this->auth->getMerchant();

        $user = $this->auth->getUser();

        $input[Entity::ACTION] = $input[Entity::ACTION] ?? Entity::SECOND_FACTOR_AUTH;

        return $this->core()->verifyUserThroughMode($input, $merchant, $user);
    }

    public function oAuthSignup($input): array
    {
        // should accept only email / oauth_provider.
        $data = $this->register($input, 'createOauth');

        $this->core->trackOnboardingEvent($input[Entity::EMAIL], EventCode::SIGNUP_CREATE_ACCOUNT_SUCCESS_WITH_GOOGLE);

        return $data;
    }

    public function oAuthLogin($input): array
    {
        $response = $this->core->oauthLogin($input);

        $this->core->trackOnboardingEvent($input[Entity::EMAIL], EventCode::LOGIN_SUCCESS_WITH_GOOGLE);

        $response = $this->addOauthTokenIfApplicable($response, $response[Entity::ID]);

        return $response;
    }

    public function getUserForMerchant(string $userId)
    {
        $merchant = $this->auth->getMerchant();

        $user = $this->repo->merchant->getMerchantUserMapping($merchant->getId(),$userId);

        if ($user === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
        }

        return $user->toArrayPublic();
    }

    /**
     * Marks that user has given consent to Razorpay to send WhatsApp messages
     *
     * @param array $input
     * @param null  $user
     *
     * @return mixed
     * @throws Exception\LogicException
     */
    public function optInForWhatsapp(array $input, $user = null)
    {
        $this->trace->info(TraceCode::MERCHANT_WHATSAPP_OPT_IN, ['input' => $input]);

        $this->validator->validateInput('opt_in_whatsapp', $input);

        if(empty($user) === true)
        {
            $user = $this->user;
        }

        $contact = $user->getContactMobile();

        if (empty($contact) === true)
        {
            throw new Exception\BadRequestValidationFailureException('User does not have a mobile number associated with the account');
        }

        try
        {
            $res = app('stork_service')->optInForWhatsapp($this->mode, $contact, $input);
        }
        catch (\Exception $exception)
        {
            // request exception is being catched and set as previous
            $ex = $exception->getPrevious();

            if ((empty($ex) === false) and
                ($this->isTimeOutException($ex) === true))
            {
                $this->trace->traceException($ex);

                return [
                    'optin_status' => false,
                    'error_message' => 'request to stork service timed out',
                ];
            }

           throw $exception;
        }

        $res['optin_status'] = true;

        return $res;
    }

    protected function isTimeOutException(\Exception $ex)
    {
        $message = $ex->getMessage();

        if (substr($message, 0, 34 ) === 'cURL error 28: Operation timed out')
        {
            return true;
        }

        return false;
    }

    /**
     * Marks that user has revoked their consent to Razorpay to send WhatsApp messages.
     *
     * @param array $input
     * @param null  $user
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    public function optOutForWhatsapp(array $input, $user = null)
    {
        $this->trace->info(TraceCode::MERCHANT_WHATSAPP_OPT_OUT, ['input' => $input]);

        (new Validator)->validateInput('opt_out_whatsapp', $input);

        if(empty($user) === true)
        {
            $user = $this->user;
        }

        $contact = $user->getContactMobile();

        if (empty($contact) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND);
        }

        $businessAccount = $input['business_account'] ?? '';

        return app('stork_service')->optOutForWhatsapp($this->mode, $contact, $input['source'], $businessAccount);
    }

    public function optInStatusForWhatsapp(array $input, $user = null)
    {
        $this->trace->info(TraceCode::MERCHANT_WHATSAPP_OPT_IN_STATUS, ['input' => $input]);

        (new Validator)->validateInput('opt_in_status_whatsapp', $input);

        if((empty($user) === true) && ($this->ba->isAdminAuth() === false))
        {
            $user = $this->user;

        } else if ((empty($user) === true) && $this->ba->isAdminAuth() === true)
        {
            $user  = $this->merchant->primaryOwner();
        }

        $contact = $user->getContactMobile();

        if(empty($contact) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND);
        }

        $businessAccount = $input['business_account'] ?? '';

        return app('stork_service')->optInStatusForWhatsapp($this->mode, $contact, $input['source'], $businessAccount);
    }

    public function getDetails(array $input)
    {
        (new Validator)->validateInput('get_details', $input);

        return (new Core())->getDetails($input);
    }

    public function getDetailsForPayroll(array $input)
    {
        (new Validator)->validateInput('get_details', $input);

        return (new Core())->getDetailsForPayroll($input);
    }


    public function getInternationalDetails(array $input)
    {
        $merchantEmailId = $this->merchant->getEmail();

        return (new Core())->getInternationalDetails($merchantEmailId);
    }

    public function getDetailsUnified(array $input)
    {
        (new Validator)->validateInput('get_details', $input);

        $response = $this->core->getDetailsUnified($input);

        return $response;
    }

    public function getUserRoles(string $userID, string $merchantID)
    {
        (new Validator)->validateInput('get_user_roles', [
            'user_id'     => $userID,
            'merchant_id' => $merchantID,
        ]);

        return $this->core->getUserAllRoles($userID, $merchantID);
    }

    public function removeIncorrectPasswordCount(array $input)
    {
        (new Validator)->validateInput('reset_incorrect_password_count', $input);

        return (new Core())->removeIncorrectPasswordCount($input['emails']);
    }

    public function sendXMobileAppDownloadLinkSms(array $input)
    {
        $merchant = $this->merchant;

        return $this->core()->sendXMobileAppDownloadLinkSms($input, $merchant);
    }

    /**
     * @param $input
     * @return array
     * @throws Exception\BadRequestException
     * Sample csv file looks like
     * login_email,update_contact,
     * ppabc.test@gmail.com,7353424525
     */
    public function verifyContactMobile($input): array
    {

        $rows = $input;

        $this->validator->validateInput('verify_contact_mobile_list', ['input'=>$input]);

        if(count($input)>500)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT);
        }

        $response = [];

        foreach ($rows as $row)
        {
            $inputParams = [
                ENTITY::EMAIL          => $row['merchant_login_email'],
                ENTITY::CONTACT_MOBILE => $row['update_contact'],
            ];

            $this->validator->validateInput('verify_contact_mobile', $inputParams);

            $user = $this->core->getUserFromEmail([ENTITY::EMAIL => $inputParams[ENTITY::EMAIL]]);


            $resUser = $this->core->updateContactMobile([ENTITY::CONTACT_MOBILE=>$inputParams[ENTITY::CONTACT_MOBILE]],$user);


            array_push($response,$resUser->getEmail());
        }

        return $response;
    }

    public function saveDeviceDetails(array $input)
    {
        return (new DeviceDetail\Core)->createUserDeviceDetail($input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function sendOtpForAddEmail(array $input): array
    {
        $this->validator->validateInput('add_email', $input);

        $token = $input[Entity::OTP_AUTH_TOKEN];

        $this->app['token_service']->verify($token, $this->user->getId());

        $this->core()->sendOtpForAddEmail($input, $this->user);

        return ['email' => $input[Entity::EMAIL]];
    }


    /**
     * @param array $input
     * @return array
     */
    public function verifyOtpForAddEmail(array $input): array
    {
        $this->validator->validateInput('add_email_verify', $input);
        $input[Entity::EMAIL] = mb_strtolower($input[Entity::EMAIL]);
        $user = $this->core()->verifyOtpForAddEmail($input, $this->user);

        //if the user signs up on PG with mobile number and does not enter email id and then
        // makes a product switch from PG->X, he needs to add his email id to be able to create VA
        //in live mode

        $merchant = $this->auth->getMerchant();

        $isBankingRequest = $this->auth->isProductBanking();

        //entities will be created and events will get fired only if the product switch is from PG to
        //X, the email is verified and it is a banking request.
        if (($isBankingRequest === true) and
            ($merchant->getSignupSource() === Product::PRIMARY) and
            ($user->getEmailVerifiedAttribute() === true))
        {
            $this->merchantService->switchProductMerchant(null,true);
        }

        return $user->toArrayPublic();
    }

    public function getUserByVerifiedContact(array $input) {
        $user = $this->core()->getUserByVerifiedContact($input);

        return $user;
    }

    public function getMerchantUserMappingForProduct(string $product = null,
                                                     string $merchantId = null,
                                                     string $userId = null,
                                                     bool $useWritePdo = false)
    {
        $userId = $userId ?? $this->auth->getUser()->getId();

        $product = $product ?? $this->auth->getRequestOriginProduct();

        $merchantId = $merchantId ?? $this->auth->getMerchantId();

        return $this->repo->merchant->getMerchantUserMapping($merchantId,
                                                             $userId,
                                                             null,
                                                             $product,
                                                             $useWritePdo);
    }

    /**
     * X necessarily needs users to have an email to work.
     * For users signing up with mobile and switching to X or logging into X with mobile
     * without having added their email address, X will be using `user_add_email` flow
     * to prompt users to add an email.
     * This flow requires users to authenticate themselves first before they are allowed to add an email address.
     * For users who have just logged in to X with mobile, following this would require 3 OTPs to be entered by the User.
     * In order to reduce the inconvenience, we send the `otp_auth_token` in response to a successful
     * login which can be used to trigger the `user_add_email` flow.
     * @param array $response
     * @param string $userId
     * @return array
     */
    protected function setOtpAuthTokenForBankingRequest(array $response, string $userId): array
    {
        $requestOriginProduct = $this->auth->getRequestOriginProduct();

        if($requestOriginProduct === Product::BANKING)
        {
            $response['otp_auth_token'] = $this->app['token_service']->generate($userId);
        }

        return $response;
    }

    public function sendResetPasswordSegmentEventAdmin(array $input): array
    {
        $this->trace->info(TraceCode::USER_PASSWORD_RESET_REQUEST, $input);

        if (isset($input['email']) === true)
        {
            $email = mb_strtolower($input['email']);

            $this->user = $this->repo->user->getUserFromEmail($email);

            $merchant = $this->user->getMerchantEntity();

            if ((empty($this->user) === false) and (empty($merchant) === false))
            {
                $this->setResetPasswordTokenAndSendEmail();

                return ['success' => true];
            }
        }
        return ['success' => false];
    }


    /**
     * @return void
     */
    private function setResetPasswordTokenAndSendEmail(): void
    {
        $passwordResetMail = new UserMail\RazorpayX\SetPasswordRBLCoCreated($this->user, Product::BANKING);

        Mail::send($passwordResetMail);
    }

    public function updateContactNumberForSubMerchantUser(array $inputData)
    {
        $response = [];

        foreach ($inputData as $input)
        {
            $submerchantId = $input['sub_id'];
            $contactNo = $input['contact_no'];

            $user = $this->core->updateContactNumberForSubMerchantUser($submerchantId, $contactNo);

            array_push($response, $user);
        }

        return $response;
    }

    /**
     * Update the user's name
     *
     * @param array $input The input data containing the new name.
     * @return array mixed The response from the name update operation.
     * @throws Exception\BadRequestException If the input is invalid or the username is empty or not different from the current name.
     */
    public function postUpdateUserName(array $input)
    {
        $this->trace->info(TraceCode::USER_NAME_UPDATE_REQUEST, $input);

        $input[Entity::NAME] = trim($input[Entity::NAME]);

        $this->validator->validateInput('update_user_name', $input);

        $response = $this->core->postUpdateUserName($input[Entity::NAME], $this->user);

        return $response;
    }

    /**
     * Get the requested token for roast flow
     * This is applicable only for lower environments, mainly in roast flow
     *
     * In Prod env, we return 400
     *
     * @param array $input The input data containing the new name.
     * @return array mixed The response from the name update operation.
     * @throws Exception\BadRequestException If the input is invalid or the username is empty or not different from the current name.
     */
    public function qaGetTokenForRoast(string $type, array $input)
    {
        if (app()->isEnvironmentProduction() === true) {
            throw new BadRequestException('This endpoint is not available in production');
        }

        switch ($type) {
            case 'password_reset_token':

                if(isset($input['email']))
                {
                    $email = $input['email'];

                    $user = $this->repo->user->getUserFromEmail(mb_strtolower($email));
                }
                else
                {
                    $contact_mobile = $input['contact_mobile'];

                    $user = $this->core->getUserFromMobile($contact_mobile);
                }

                $token = $user->getPasswordResetToken();

                return [
                    'password_reset_token' => $token,
                ];

            case 'invitation_token':

                $product = $input['product'] ?? 'banking';
                $merchantId = $input['merchant_id'];
                $userEmail = $input['email'];

                # code...
                $invitationToken = $this->repo->invitation->getInvitationToken($product, $merchantId, $userEmail);

                return [
                    'invitation_token' => $invitationToken['token'],
                ];

                throw new BadRequestException('Token not found for the given input', null, [
                    'product' => $product,
                    'merchant_id' => $merchantId,
                    'email' => $userEmail,
                ]);

            default:
                throw new BadRequestException('Invalid type');
        }
    }

    public function postToggleDashboardCaptcha(array $input)
    {
        $fields = [
             DcsConstants::DisableCaptcha => $input['value']
        ];
        $this->app['dcs_config_service'] -> editConfiguration(DcsConstants::DisableCaptcha, DcsConstants::DashboardCaptchaEntityId, $fields, $this->mode);

        $res =  $this->app['dcs_config_service'] -> fetchConfiguration(DcsConstants::DisableCaptcha, DcsConstants::DashboardCaptchaEntityId, [DcsConstants::DisableCaptcha], $this->mode);
        return $res;
    }

    public function createVendorEntities(array $input)
    {
        $this->validator->validateInput(Validator::CREATE_VENDOR_ENTITIES, $input);

        $user = null;
        $vendorName = $input['name'];

        $user = $this->repo->user->findByEmail($input['email']);

        // check if dependent entities are already present
        $bankingOwnerId = $this->repo->merchant_user->fetchMerchantIdForUserIdRoleAndProduct($user->getId(), Role::OWNER, Product::BANKING);

        if (empty($bankingOwnerId) === false) // If the user is already a banking owner we will return that MID
        {
            $this->trace->info(TraceCode::USER_ALREADY_BANKING_OWNER,
                [
                    'user_id' => $user->getId(),
                    'merchant_id' => $bankingOwnerId[0]
                ]);
            return [
                'merchant_id' => $bankingOwnerId[0],
            ];
        }

        $createMerchantUserMappingResponse = $this->checkAndCreateMerchantUserMappingForPrimaryOwner($user->getId());

        if(empty($createMerchantUserMappingResponse) === false) // If the user is a primary owner but not banking owner,
                                                    //  we will create merchant_user mapping for banking product and return that MID
        {
            return $createMerchantUserMappingResponse;
        }

        // If there are no MIDs associated with the user, we will create a new MID for the user with product banking.
        $this->trace->info(TraceCode::CREATE_NEW_MERCHANT_AND_CORRESPONDING_ENTITIES_FOR_USER,
            [
                'user_id' => $user->getId(),
                'name' => $input['name']
            ]);

        $this->auth->setRequestOriginProduct(ProductType::BANKING);

        $input = [
            DeviceDetail\Entity::SIGNUP_SOURCE => Product::BANKING,
        ];

        $data = $this->createMerchant($user->toArray(), '', $vendorName, 'IN', false, $input, [], false);

        return [
            'merchant_id' => $data['id'],
        ];
    }

    public function checkAndCreateMerchantUserMappingForPrimaryOwner(string $userId)
    {
        $primaryOwnerId = $this->repo->merchant_user->fetchMerchantIdForUserIdRoleAndProduct($userId, Role::OWNER, Product::PRIMARY);

        if (empty($primaryOwnerId) === false)   // If the user is a primary owner but not banking owner,
                                                //  we will create merchant_user mapping for banking product and return that MID
        {
            $this->trace->info(TraceCode::CREATE_BANKING_MERCHANT_USER_MAPPING_FOR_PRIMARY_OWNER,
                [
                    'user_id' => $userId,
                    'merchant_id' => $primaryOwnerId[0]
                ]);

            $this->repo->merchant_user->createMerchantUserMappingforUser($userId, $primaryOwnerId[0], Role::OWNER, Product::BANKING);

            return [
                'merchant_id' => $primaryOwnerId[0],
            ];
        }

        // if the user is not a primary owner, we will return an empty array
        return [];
    }

    public function getMultipleUsers(array $input)
    {
        $this->validator->validateInput(Validator::GET_MULTIPLE_USERS, $input);

        $userMapping = [];

        if (isset($input['user_ids']) === true and empty($input['user_ids']) === false)
        {
            $users = $this->repo->user->getMultipleUsersByIDsV2($input['user_ids']);

            foreach ($users as $user) {
                $userMapping[$user['id']] = $user->toArray();
                $userMapping[$user['id']]['is_password_set'] = $user->getPassword() !== null;
            }
        }

        if (isset($input['user_emails']) === true and empty($input['user_emails']) === false)
        {
            $users = $this->repo->user->getMultipleUsersByEmailsV2($input['user_emails']);

            foreach ($users as $user) {
                $userMapping[$user['email']] = $user->toArray();
                $userMapping[$user['email']]['is_password_set'] = $user->getPassword() !== null;
            }
        }

        // Fetch users by mobile numbers if provided
        if (isset($input['user_contacts']) === true and empty($input['user_contacts']) === false)
        {
            $mobiles = $input['user_contacts'];
            $phoneMap = [];
            $totalPhoneFormats = new PublicCollection();
            foreach ($mobiles as $mobile)
            {
                $formats = (new PhoneBook($mobile))->getMobileNumberFormats();
                foreach ($formats as $format) {
                    $totalPhoneFormats->push($format);
                    $phoneMap[$format] = $mobile;
                }
            }
            $users = $this->repo->user->getMultipleUsersByMobilesV2($totalPhoneFormats->toArray());

            foreach ($users as $user)
            {
                $phone = $phoneMap[$user[Entity::CONTACT_MOBILE]];
                $userMapping[$phone] = $user->toArray();
                $userMapping[$phone]['is_password_set'] = $user->getPassword() !== null;
            }
        }

        return $userMapping;
    }

    function shouldUpdateUserMerchantMapping($signupCampaign): bool
    {
        $loggedInMerchant=  $this->app['basicauth']->getMerchant();
        $ezetapMerchantId=  $this->app['config']->get('app.ezetap_merchant_id');

        return $this->isAssistedOnboardingSignupCampaign($signupCampaign) &&
            ($loggedInMerchant->getId() == $ezetapMerchantId ||
                ($loggedInMerchant->isResellerPartner() &&
                    $loggedInMerchant->isFeatureEnabled(FeatureConstant::POS_CHANNEL_PARTNERSHIP)));
    }

    public static function isAssistedOnboardingSignupCampaign($signupCampaign) : bool
    {
        return in_array($signupCampaign, [DeviceDetailConstants::ASSISTED_ONBOARDING, DeviceDetailConstants::PARTNER_ASSISTED_ONBOARDING]);
    }

    public function addSalesUserToMerchant(array $input): array
    {
        $this->validator->validateInput('addSalesUserToMerchant', $input);

        try {
            // Fetch the user using the provided email
            $user = $this->repo->user->getUserFromEmail(strtolower($input['email']));

            // Check if the user was found, if not, throw an exception
            if (empty($user))
            {
                throw new Exception\BadRequestValidationFailureException(ErrorCode::ERROR_USER_NOT_FOUND_BY_EMAIL);
            }

            $merchant = $this->repo->merchant->findOrFailPublic($input['merchant_id']);

            if (empty($merchant))
            {
                throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_MERCHANT_NOT_FOUND);
            }

            // Check if the user is already assigned to the merchant with the specified role and product
            $userMapping = $this->repo->merchant_user->getUserRoleMapping(
                $user->getId(),
                $input['merchant_id'],
                Role::RAZORPAY_SALES,
                Product::PRIMARY
            );

            // If a mapping exists, throw an exception indicating the merchant user mapping already exists
            if (!empty($userMapping))
            {
                throw new Exception\BadRequestValidationFailureException(ErrorCode::ERROR_MERCHANT_USER_ALREADY_EXISTS);
            }

            $response = $this->repo->transactionOnLiveAndTestAndAsv(function() use ($user, $input)
            {
                // Attach a new merchant user mapping for the specified user, merchant, role, and product
                $userMerchantMappingInputData = [
                    'action' => 'attach',
                    'role' => Role::RAZORPAY_SALES,
                    'merchant_id' =>$input['merchant_id'],
                ];

                return $this->updateUserMerchantMapping($user->getId(), $userMerchantMappingInputData);
            });

            return $response->toArrayPublic();
        }
        catch (\Throwable $exception)
        {
            throw new Exception\BadRequestValidationFailureException($exception->getMessage());
        }
    }

    /**
     * @param array{
     *     id: int,
     *     product: string,
     *     product_restricted: bool,
     *     DEFAULT_MERCHANT_ID : string
     * } $input Associative array with specific fields
     *
     * @return array
     * @throws BadRequestException
     */
    public function getMerchantsOfUser(string $id,array $input) : array
    {
            (new Validator)->validateInput('get_users_merchants', $input);

            $user = $this->repo->user->findOrFail($id);

            if (empty($user)){
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
            }

            // core->get gets list of merchants using using following params in the input
            // DEFAULT_MERCHANT_ID - parsed and handled in side the core->get
            // X-ORG-HOSTNAME - added to context from basic auth middleware and used inside core->get
            $userMerchants = $this->core->get($user,true,$input);

            if( empty($userMerchants['merchants']) || count($userMerchants['merchants']) == 0 ){
                $userMerchants['merchants'] = [];
                return $userMerchants;
            }

            //if intent is auth/login, select the merchants with which user can login
            if ($input['intent'] == 'auth') {

                $product = empty($input['product']) ? "" : $input['product'];
                $merchants = $this->core->selectMerchantsToLogin($userMerchants['merchants'], $product);
                $userMerchants['merchants'] = $merchants;
                return $userMerchants;
            }

            $userMerchants['merchants'] = $userMerchants[Entity::MERCHANTS];

            return $userMerchants;
    }

    public function isUserExistsForCustomInvite($heimdallTokenData, &$user): void
    {
        $orgID = $heimdallTokenData[AdminLead\Entity::ORG_ID];

        Org\Entity::verifyIdAndSilentlyStripSign($orgID);

        $org = $this->repo->org->findOrFailPublic($orgID);

        $permissionEnabled = (new Org\Service)->isRequiredPermissionEnabledforOrg($orgID, Permission::CUSTOM_INVITE_MERCHANT_FLOW);

        $vasOrgFeatureEnabled = $org->isFeatureEnabled(FeatureConstant::VAS_ORG_IDENTIFIER);

        if(($permissionEnabled === true) and ($vasOrgFeatureEnabled === true) and (empty($user) === true))
        {
            $user = optional($this->repo->user->getUserFromEmail(strtolower($heimdallTokenData[AdminLead\Entity::EMAIL])))->toArray();
        }
    }

   public function skipEmailUniquenessCheckForCustomInvite($user, $merchantInputData, &$inputData): void
   {
       $merchantOrgId = $merchantInputData[Merchant\Entity::ORG_ID];

       Org\Entity::verifyIdAndSilentlyStripSign($merchantOrgId);

       $org = $this->repo->org->findOrFailPublic($merchantOrgId);

       $permissionEnabled = (new Org\Service)->isRequiredPermissionEnabledforOrg($merchantOrgId, Permission::CUSTOM_INVITE_MERCHANT_FLOW);

       $vasOrgFeatureEnabled = $org->isFeatureEnabled(FeatureConstant::VAS_ORG_IDENTIFIER);

       if(($permissionEnabled === true) and ($vasOrgFeatureEnabled === true))
       {
           $this->user = $this->repo->user->find($user[Entity::ID]);

           // Set the user verified to skip email verification for customized invite flow.
           if($this->user->getConfirmedAttribute() === false)
           {
               $this->confirm($this->user->id);
           }

           // Skip the uniqueness check for merchant email
           $inputData [Merchant\Entity::SKIP_EMAIL_UNIQUENESS_CHECK ] = true;
       }
   }

    public function userServiceDataAccessor($input)
    {
        $responseData = [];

        $this->trace->info(
            TraceCode::USER_SERVICE_DATA_ACCESSOR,
            [
                'input' => $input
            ]
        );

        $action = $input['action'] ?? 'default';

        switch ($action) {
            case 'create_merchant':
                $merchantInputData = $input['payload']['merchant_input_data'] ?? [];
                $merchantDetailInputData = $input['payload']['merchant_detail_input_data'] ?? [];
                $createMerchantMetadata = $input['payload']['create_merchant_metadata'] ?? [];
                $responseData = $this->merchantService->create($merchantInputData,
                    $merchantDetailInputData,
                    $createMerchantMetadata
                );
                break;

            case 'product_switch':
                $this->productSwitch($input);
                $responseData = ['success' => true];
                break;

            case 'actor_info':
                $responseData = Adapter\Base::getActorInfo();
                break;

            case 'fetch_role_names_from_authz':
                $responseData = $this->fetchAuthzRoleNames($input);
                break;

            case 'fetch_role_name':
                $responseData = $this->repo->roles->fetchRoleName($input['role_name']);
                break;

            case 'fetch_settings':
                $responseData = $this->core->fetchUserSettings($input['user_id']);
                break;

            case 'fetch_additional_data':
                $merchantEntity = $this->repo->merchant->findOrFailPublic($input['merchant_id']);
                $responseData = [
                    'permissions' =>  $this->core->userPermissions($input['merchant']),
                    'attributes' => $this->core->fetchMerchantAttribute($input['merchant_id']),
                    'methods' => $merchantEntity->getMethods()
                ];

                $responseData += $this->core->fetchBankingBalanceData($input['merchant_id']);
                break;

            default:
                break;
        }

        return [
            'response' => $responseData
        ];
    }

   public function fetchAuthzRoleNames($input)
   {
       try {
           $res = (new \RZP\Models\Roles\Service())->getRoleNamesUsingExperiment($input['role_ids']);
           return $res;
       }catch (\Throwable $ex) {
           $this->trace->info(
               TraceCode::USER_SERVICE_DATA_ACCESSOR,
               [
                   'desc' => 'error while fetching authz role names',
                   'err' => $ex->getMessage()
               ],
           );
           return ['res' => 'something went wrong'];
       }
   }

   public function productSwitch($input)
   {
       $userID = $input['user_id'];
       $merchantID = $input['merchant_id'];
       $merchant = $this->repo->merchant->findOrFail($merchantID);
       $user = $this->repo->user->findOrFail($userID);

       $this->app['basicauth']->setMerchant($merchant);
       $this->app['basicauth']->setUser($user);

       $merchantService = new Merchant\Service();

       $merchantService->switchProductMerchant();

   }

}
