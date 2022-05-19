<?php

namespace RZP\Models\User;

use Mail;
use Hash;
use Cache;
use Config;
use Carbon\Carbon;
use RZP\Jobs\NotifyRas;
use RZP\Models\Base\PublicEntity;
use Illuminate\Hashing\BcryptHasher;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Models\Invitation;
use Razorpay\Trace\Logger;
use RZP\Models\Admin\Admin;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Models\DeviceDetail;
use RZP\Mail\User as UserMail;
use RZP\Services\HubspotClient;
use RZP\Models\Admin\AdminLead;
use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\User\RateLimitLoginSignup\Facade as LoginSignupRateLimit;

class Service extends Base\Service
{
    protected $core;

    protected $validator;

    protected $merchantService;

    protected $m2mReferralService;

    public function __construct(Core $core = null, Validator $validator = null, Merchant\Service $merchantService = null,
                                Merchant\M2MReferral\Service $m2mReferralService = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->validator = $validator ?? new Validator();

        $this->merchantService = $merchantService ?? new Merchant\Service();

        $this->m2mReferralService = $m2mReferralService ?? new Merchant\M2MReferral\Service();

        $this->elfin = $this->app['elfin'];
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


    public function register(array $input, string $operation = 'create', bool $sendConfirmation = true): array
    {
        $this->traceRegisterInput($input);

        $m2mReferralInput = $this->m2mReferralService->extractFriendBuyParams($input);

        $referrer = $input['ref'] ?? '';

        $businessName = $input['business_name'] ?? '';

        $partnerIntent = $input[Merchant\Constants::PARTNER_INTENT] ?? false;

        $this->trace->count(Merchant\Metric::SIGNUP_TOTAL);

        $this->app->hubspot->trackSignupEvent($input);

        $partnerInvitation  = $this->handleUserInvitation($input);
        $user               = $partnerInvitation['user'];
        $invitation         = $partnerInvitation['invitation'];
        $invitationToken    = $partnerInvitation['invitationToken'];

        $heimdallTokenData = $this->handleHeimdallInvitation($input);

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
                if($this->core()->checkIfMobileAlreadyExists($input[Entity::CONTACT_MOBILE]) === true)
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

        /**
         * These two conditions are exclusive
         * One cannot accept an invitation and create a merchant account at the same time
         */
        if (empty($invitationToken) === false)
        {
            $this->acceptInvite($user, $invitation);
            $data = ['login'=>true];
        }
        else
        {
            $data = $this->createMerchant($user, $referrer, $businessName, $partnerIntent, $input, $heimdallTokenData, $sendConfirmation);
        }

        $signupMethod = Constants::PASSWORD;

        $this->signUpSuccess($user, $partnerIntent, $signupMethod,$m2mReferralInput);

        return $data;
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

    protected function handleHeimdallInvitation(array &$input)
    {
        $heimdallInvitationToken = $input['merchant_invitation'] ?? null;

        $heimdallTokenData = null;

        if (empty($heimdallInvitationToken) === false)
        {
            // Check if this token is valid or not
            $heimdallTokenData = (new AdminLead\Service)->verify($heimdallInvitationToken);

            if (isset($heimdallTokenData['id']) === true)
            {
                $tokenSignUpInput = [AdminLead\Entity::SIGNED_UP => 1];

                (new AdminLead\Service)->editInvitation(
                    $heimdallTokenData[AdminLead\Entity::ORG_ID], $heimdallTokenData[AdminLead\Entity::ID], $tokenSignUpInput);
            }

            unset($input['merchant_invitation']);
        }
        return $heimdallTokenData;

    }

    protected function signUpSuccess($user, $partnerIntent, $signupMethod,$m2mReferralInput=null)
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

        $customProperties = [
            Entity::EMAIL                      => $user[Entity::EMAIL] ?? null,
            Entity::VISITOR_ID                 => $visitorId,
            Merchant\Constants::PARTNER_INTENT => $partnerIntent,
            'is_m2m_referral'                  => $isM2MReferral,
            'phone'                            => $user[Entity::CONTACT_MOBILE] ?? ""

        ];

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

        $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_CREATE_ACCOUNT_SUCCESS, $merchant, null, $customProperties);

        $this->notifyRasOnSignup($merchant, $customProperties, $user);
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

    protected function acceptInvite($user, array $invitation = null)
    {
        $invitationAcceptInput = [
            Invitation\Entity::USER_ID => $user[Entity::ID],
            Invitation\Entity::ACTION  => 'accept',
            Invitation\Entity::EMAIL   => $user[Entity::EMAIL],
        ];

        (new Invitation\Service)->action($invitation[Invitation\Entity::ID], $invitationAcceptInput);

        $this->confirm($user[Entity::ID]);

        $this->core->subscribeToMailingList($user);
    }

    protected function createMerchant(array $user, string $referrer, string $businessName, bool $partnerIntent, array $input, $heimdallTokenData, bool $sendConfirmation): array
    {
        $merchantInputData = [
            Merchant\Entity::NAME          => $businessName,
            Merchant\Entity::SIGNUP_SOURCE => $this->auth->getRequestOriginProduct(),
        ];

        $merchantDetailInputData = [];

        if (isset($user[Entity::EMAIL]) === true)
        {
            $merchantInputData[Merchant\Entity::EMAIL] = $user[Entity::EMAIL];
            $merchantInputData[Merchant\Entity::SIGNUP_VIA_EMAIL] = 1;
        }
        else if (isset($user[Entity::CONTACT_MOBILE]))
        {
            $merchantDetailInputData[Entity::CONTACT_MOBILE] = $user[Entity::CONTACT_MOBILE];
            $merchantInputData[Merchant\Entity::SIGNUP_VIA_EMAIL] = 0;
        }

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
        }

        $sendOtpEmail = filter_var($this->app['request']->header(RequestHeader::X_SEND_EMAIL_OTP, false),
            FILTER_VALIDATE_BOOLEAN);

        // Remove this when signup experiment for X is ramped up as we can find the
        // template just from the product origin
        $isRequestFromXVerifyEmail = $this->isRequestFromXVerifyEmail($input);

        $inputData = ["isRequestFromXVerifyEmail" => $isRequestFromXVerifyEmail];

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

    public function verifySignupOtp(array $input, string $operation = 'createOTPSignup'): array
    {
        $referrer = $input['ref'] ?? '';

        $m2mReferralInput = $this->m2mReferralService->extractFriendBuyParams($input);

        $businessName = $input['business_name'] ?? '';

        $partnerIntent = $input[Merchant\Constants::PARTNER_INTENT] ?? false;

        $this->trace->count(Merchant\Metric::SIGNUP_TOTAL);

        // $this->app->hubspot->trackSignupEvent($input);
        // TODO: @kartik.sayani- is this needed with mobile/email + otp signup?
        $partnerInvitation      = $this->handleUserInvitation($input);
        $user                   = $partnerInvitation['user'];
        $invitation             = $partnerInvitation['invitation'];
        $invitationToken        = $partnerInvitation['invitationToken'];

        $verifySuccess = $this->core->verifySignupOtp($input);

        if ($verifySuccess === true)
        {
            $heimdallTokenData = $this->handleHeimdallInvitation($input);

            if (empty($user) === true)
            {
                $input[Entity::NAME] = $input[Entity::NAME] ?? '';

                if (isset($input[Entity::CONTACT_MOBILE]) === true)
                {
                    $input[Entity::SIGNUP_VIA_EMAIL] = 0;
                }
                else
                {
                    $input[Entity::SIGNUP_VIA_EMAIL] = 1;
                }

                unset($input['ref']);

                unset($input['business_name']);

                $user = $this->create($input, $operation);

                $userEntity = $this->repo->user->findByPublicId($user[Entity::ID]);
                $this->core->setContactMobileOrEmailVerify($input, $userEntity);
            }

            /**
             * These two conditions are exclusive
             * One cannot accept an invitation and create a merchant account at the same time
             */
            if (empty($invitationToken) === false)
            {
                $this->acceptInvite($user, $invitation);
                $data = ['login'=>true];
            }
            else
            {
                $this->createMerchant($user, $referrer, $businessName, $partnerIntent, $input, $heimdallTokenData, false);

                $data = $this->get($user['id']);
            }

            $signupMethod = Constants::OTP;

            $this->signUpSuccess($user, $partnerIntent, $signupMethod,$m2mReferralInput);

            return $data;
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
        $merchantData = $this->merchantService->create($merchantInputData, $merchantDetailInputData);

        if (empty($referrer) === false)
        {
            $tagInputData = [
                'tags' => ['ref-' . $referrer],
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
    protected function sendConfirmationMailIfApplicable(Entity $user, Merchant\Entity $merchant, bool $sendOtpEmail = false, array $inputData = [])
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
            $data = $this->sendOtpEmailVerification($merchant, $user, [], $inputData);

            $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_SEND_VERIFICATION_EMAIL_OTP_SUCCESS, $merchant, null, $customProperties);

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $customProperties, SegmentEvent::SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS);

            // Add the response of token from Raven Service
            $response['token'] = $data['token'];
        }
        // Remove this else when signup experiment for X is ramped up.
        else
        {
            $this->sendConfirmationMail($user);

            $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_SEND_VERIFICATION_EMAIL_SUCCESS, $merchant, null, $customProperties);
        }

        $response['id']      = $merchant->getId();
        $response['name']    = $merchant->getName();
        $response['email']   = $user->getEmail();
        $response['user_id'] = $user->getId();

        return $response;
    }

    public function sendOtpEmailVerification(Merchant\Entity $merchant, Entity $user, array $merchantData = [], array $inputData = [])
    {
        $merchantData['medium'] = 'email';

        // Remove this when signup experiment for X is ramped up.
        // We can make use of product.
        $isRequestFromXVerifyEmail = $inputData['isRequestFromXVerifyEmail'] ?? false;

        $merchantData['action'] = ($isRequestFromXVerifyEmail === true) ? 'x_verify_email' : 'verify_email';

        $this->trace->info(
            TraceCode::USER_EMAIL_OTP_SEND,
            [
                'merchantId'      => $merchant->getId(),
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
        $this->core()->edit($this->user, $input);

        return $this->user->toArrayPublic();
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

        return $user->toArrayPublic();
    }

    public function checkUserHasSetPassword(): array
    {
        $user = $this->user;

       return $this->core->checkUserHasSetPassword($user);
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

    public function updateUserMerchantMapping(string $id, array $input): array
    {
        $input[Merchant\Entity::PRODUCT] = $input[Merchant\Entity::PRODUCT] ?? $this->auth->getRequestOriginProduct();

        $user = $this->repo->user->findOrFailPublic($id);

        $user = $this->core->updateUserMerchantMapping($user, $input);

        return $user->toArrayPublic();
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

            return $this->setOtpAuthTokenForBankingRequest($response, $response[Entity::ID]);
        }
        catch (\Throwable $ex)
        {
            $this->core->trackOnboardingEvent($input[Entity::EMAIL] ?? '', EventCode::MERCHANT_ONBOARDING_LOGIN_FAILURE, $ex);

            throw $ex;
        }
    }

    public function loginWithOtp(array $input): array
    {
        $data = $this->core->loginWithOtp($input);

        return $data;
    }

    public function verifyLoginOtp(array $input): array
    {
        $user = $this->core->verifyLoginOtp($input);

        return $this->setOtpAuthTokenForBankingRequest($user, $user[Entity::ID]);
    }

    public function loginOtp2faPassword(array $input): array
    {
        $user = $this->auth->getUser();

        $response = $this->core->loginOtp2faPassword($user, $input);

        return $this->setOtpAuthTokenForBankingRequest($response, $user->getId());

    }

    public function sendVerificationOtp(array $input): array
    {
        $data = $this->core->sendVerificationOtp($input);

        return $data;
    }

    public function verifyVerificationOtp(array $input): array
    {
        $user = $this->core->verifyVerificationOtp($input);

        return $user;
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

        return $this->setOtpAuthTokenForBankingRequest($response, $user->getId());
    }

    public function get(string $id, array $input = []): array
    {
        if ($this->auth->isAdminAuth() === true or $this->auth->isPrivilegeAuth() === true)
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

            if ($productSwitchOccurence === true)
            {
                $response = $this->core->get($user);
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
        $isProductSwitchRequiredResponse = $this->isProductSwitchRequired($response);

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
                    $merchant[Entity::PRODUCT] === ProductType::BANKING) ||
                ($isBankingRequest === false &&
                    $merchant[Entity::PRODUCT] === ProductType::PRIMARY))
            {
                $isUserRelatedToCurrentProduct = true;
            }

            if (($isBankingRequest === true &&
                    $merchant[Entity::PRODUCT] === ProductType::PRIMARY &&
                    $merchant[Entity::ROLE] === Entity::OWNER) ||
                ($isBankingRequest === false &&
                    $merchant[Entity::PRODUCT] === ProductType::BANKING &&
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

    public function updateMerchantManageTeam(string $userId, array $input): array
    {
        $input['merchant_id'] = $this->merchant->getId();

        $teamData = [
            'merchant_id' => $input['merchant_id'],
            'user_id'     => $userId,
        ];

        $this->validator->validateInput('teamManagement', $teamData);

        return $this->updateUserMerchantMapping($userId, $input);
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

            $data = $this->sendOtpEmailVerification($merchant, $user, $merchantData, $inputData);
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
            $email = mb_strtolower($input['email']);

            //find or fail public by email.
            /** @var User\Entity $user */
            $user = $this->repo->user->getUserFromEmail($email);

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

                //get Org and send it to mailer, deal with other orgs as well.
                $org = $org->toArrayPublic();

                $org['hostname'] = $this->auth->getOrgHostName();
                $org['showAxisSupportUrl'] = $showAxisSupportUrl;

                $requestOriginProduct = $this->auth->getRequestOriginProduct();

                $passwordResetMail = new UserMail\PasswordReset($user->toArrayPublic(), $org, $requestOriginProduct);

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
            $email = mb_strtolower($input['email']);

            /** @var Entity $user */
            $user = $this->repo->user->findByEmail($email);
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

            // Password reset via mail essentially confirms the email.
            if ($user->getConfirmedAttribute() === false)
            {
                $this->core->confirm($user);
            }
        }

        $isOrg2FaEnforcedExperimentEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
            $user->getId(),
            RazorxTreatment::ORG_LEVEL_2FA_ENFORCED_FUNCTIONALITY);

        $orgId = $this->auth->getOrgId();
        $org = $this->repo->org->findByPublicId($orgId);

        $isOrg2FaEnforced = (
            ($isOrg2FaEnforcedExperimentEnabled === true)
            AND ($org->isMerchant2FaEnabled() === true)
        );

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

        return ['success' => true, 'user_id' => $user->getId()];
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
        if ($this->user->getConfirmedAttribute() === false)
        {
            $this->user->getValidator()->validateVerifyEmailWithOtpOperation($input);

            $requestOriginProduct = $this->auth->getRequestOriginProduct();

            $action = ($requestOriginProduct === Product::BANKING) ? 'x_verify_email' : 'verify_email';

            $this->core()->verifyEmailWithOtp($input, $this->merchant, $this->user, $action);

            LoginSignupRateLimit::resetKey($this->user->getId(), Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX);
        }
        $response['user'] = $this->user->toArrayPublic();

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
        $data = $this->core->oauthLogin($input);

        $this->core->trackOnboardingEvent($input[Entity::EMAIL], EventCode::LOGIN_SUCCESS_WITH_GOOGLE);

        return $data;
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

        return app('stork_service')->optOutForWhatsapp($this->mode, $contact, $input['source']);
    }

    public function optInStatusForWhatsapp(array $input, $user = null)
    {
        $this->trace->info(TraceCode::MERCHANT_WHATSAPP_OPT_IN_STATUS, ['input' => $input]);

        (new Validator)->validateInput('opt_in_status_whatsapp', $input);

        if(empty($user) === true)
        {
            $user = $this->user;
        }

        $contact = $user->getContactMobile();

        if(empty($contact) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND);
        }

        return app('stork_service')->optInStatusForWhatsapp($this->mode, $contact, $input['source']);
    }

    public function getDetails(array $input)
    {
        (new Validator)->validateInput('get_details', $input);

        return (new Core())->getDetails($input);
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
}
