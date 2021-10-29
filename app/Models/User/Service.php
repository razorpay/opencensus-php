<?php

namespace RZP\Models\User;

use Mail;
use Hash;
use Config;
use Carbon\Carbon;
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
use RZP\Models\Admin\Admin;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Models\DeviceDetail;
use RZP\Mail\User as UserMail;
use RZP\Services\HubspotClient;
use RZP\Models\Admin\AdminLead;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Feature\Constants as FeatureConstant;

class Service extends Base\Service
{
    protected $core;

    protected $validator;

    protected $merchantService;

    public function __construct(Core $core = null, Validator $validator = null, Merchant\Service $merchantService = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->validator = $validator ?? new Validator();

        $this->merchantService = $merchantService ?? new Merchant\Service();
    }

    public function register(array $input, string $operation = 'create'): array
    {
        $this->traceRegisterInput($input);

        $data = [];

        $referrer = $input['ref'] ?? '';

        $invitationToken = $input['invitation'] ?? null;

        $businessName = $input['business_name'] ?? '';

        $invitation = null;

        $user = null;

        $tokenData = null;

        $partnerIntent = $input[Merchant\Constants::PARTNER_INTENT] ?? false;

        $this->trace->count(Merchant\Metric::SIGNUP_TOTAL);

        $this->app->hubspot->trackSignupEvent($input);

        /*
         * If we have an invitation token, the user may have created an account
         * in the meantime. $user will be equal to the user with the same email
         * as the invited user
         */
        if (empty($invitationToken) === false)
        {
            $invitation = (new Invitation\Service)->fetchByToken($invitationToken);

            $user = $this->core->getUserFromEmail($invitation);

            // Since input would be lacking an email in case of registration via the invitation
            $input[Entity::EMAIL] = $invitation[Invitation\Entity::EMAIL];

            unset($input['invitation']);
        }

        $heimdallInvitationToken = $input['merchant_invitation'] ?? null;

        $adminId = null;

        if (empty($heimdallInvitationToken) === false)
        {
            // Check if this token is valid or not
            $tokenData = (new AdminLead\Service)->verify($heimdallInvitationToken);

            if (isset($tokenData['id']) === true)
            {
                $tokenSignUpInput = [AdminLead\Entity::SIGNED_UP => 1];

                (new AdminLead\Service)->editInvitation(
                    $tokenData[AdminLead\Entity::ORG_ID], $tokenData[AdminLead\Entity::ID], $tokenSignUpInput);
            }

            unset($input['merchant_invitation']);
        }

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
            if (empty($input[Entity::OAUTH_PROVIDER]) === true)
            {
                $input[Entity::PASSWORD_CONFIRMATION] = $input[Entity::PASSWORD];
            }

            $input[Entity::NAME] = $input[Entity::NAME] ?? '';

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
         * One cannot accept an invite and create a merchant account at the same time
         */
        if (empty($invitationToken) === false)
        {
            $invitationAcceptInput = [
                Invitation\Entity::USER_ID => $user[Entity::ID],
                Invitation\Entity::ACTION  => 'accept',
                Invitation\Entity::EMAIL   => $user[Entity::EMAIL],
            ];

            (new Invitation\Service)->action($invitation[Invitation\Entity::ID], $invitationAcceptInput);

            $this->confirm($user[Entity::ID]);

            $this->core->subscribeToMailingList($user);

             $data['login'] = true;
        }
        else
        {
            $merchantInputData = [
                Merchant\Entity::EMAIL         => $user[Entity::EMAIL],
                Merchant\Entity::NAME          => $businessName,
                Merchant\Entity::SIGNUP_SOURCE => $this->auth->getRequestOriginProduct(),
            ];

            if (isset($input[Merchant\Constants::PARTNER_INTENT]))
            {
                $merchantInputData[Merchant\Constants::PARTNER_INTENT] = $partnerIntent;
            }

            if (empty($tokenData) === false)
            {
                // Merchant belongs to the same org that the inviting admin does
                $merchantInputData[Merchant\Entity::ORG_ID] = $tokenData[AdminLead\Entity::ORG_ID];
                // Map merchant to the admin that generated his lead (invited merchant to sign up)
                $merchantInputData[Merchant\Entity::ADMINS] = [$tokenData[AdminLead\Entity::ADMIN_ID]];
            }

            $sendOtpEmail = filter_var($this->app['request']->header(RequestHeader::X_SEND_EMAIL_OTP, false),
                                       FILTER_VALIDATE_BOOLEAN);

            // Remove this when signup experiment for X is ramped up as we can find the
            // template just from the product origin
            $isRequestFromXVerifyEmail = $this->isRequestFromXVerifyEmail($input);

            $inputData = ["isRequestFromXVerifyEmail" => $isRequestFromXVerifyEmail];

            $data = $this->createMerchantFromUser($merchantInputData, $user, $referrer, $sendOtpEmail, $inputData);
        }

        $visitorId = $this->fetchVisitorIdFromCookie();

        $customProperties = [Entity::EMAIL                      => $user[Entity::EMAIL],
                             Entity::VISITOR_ID                 => $visitorId,
                             Merchant\Constants::PARTNER_INTENT => $partnerIntent];

        $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_CREATE_ACCOUNT_SUCCESS, $this->merchant, null, $customProperties);

        $this->pushSegmentSignupEvent($user[Entity::ID], $customProperties);

        return $data;
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
    public function createMerchantFromUser(array $merchantInputData, array $userData, string $referrer = '', bool $sendOtpEmail = false, array $inputData = [], bool $sendConfirmation = true)
    {
        $merchantData = $this->merchantService->create($merchantInputData);

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

        $data = [];
        $data['id']      = $merchant->getId();
        $data['name']    = $merchant->getName();
        $data['email']   = $user->getEmail();
        $data['user_id'] = $user->getId();

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

    protected function sendOtpEmailVerification(Merchant\Entity $merchant, Entity $user, array $merchantData = [], array $inputData = [])
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

        $user->getValidator()->validateInput('changePassword', $input);

        $this->core->setNewPassword($user, $input);

        return $user->toArrayPublic();
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
            return $this->core->login($input);
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

        return $user;
    }

    public function loginOtp2faPassword(array $input): array
    {
        $user = $this->auth->getUser();

        return $this->core->loginOtp2faPassword($user, $input);

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

        return $this->core->verifyUserSecondFactorAuth($user, $input);
    }

    public function get(string $id): array
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

        return $response;
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

    public function postResetPassword(array $input)
    {
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
                        'email' => $email
                    ]);

                $this->core->trackOnboardingEvent($email,
                                                 EventCode::MERCHANT_ONBOARDING_RESET_PASSWORD_FAILURE,
                                                 new BaseException(Constants::USER_EMAIL_NOT_FOUND));
            }
            else
            {
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

        return ['success' => true];
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

        $email = mb_strtolower($input['email']);

        /** @var Entity $user */
        $user = $this->repo->user->findByEmail($email);

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
            $this->core->setNewPassword($user, $input);

            // Password reset via mail essentially confirms the email.
            if ($user->getConfirmedAttribute() === false)
            {
                $this->core->confirm($user);
            }
        }

        $isOrg2FaEnforcedEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
            $user->getId(),
            RazorxTreatment::ORG_LEVEL_2FA_ENFORCED_FUNCTIONALITY);

        if (($isOrg2FaEnforcedEnabled === true) and
            ($user->isOwner() === true) and
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
        $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                                                                     $user->getId(),
                                                                     null,
                                                                     $product);

        if (empty($userMapping) === true)
        {
            // Since we have user roles in headers we can get the opposite product easily.
            // In switch we have to assign the role for merchants with only the opposite product side role.
            // Like Owner in PG will be Owner in BB and Admin in BB will be Admin in PG.

            $switchProduct = ($product === Product::BANKING) ? Product::PRIMARY : Product::BANKING;

            $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId,
                $user->getId(),
                null,
                $switchProduct);

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
        $hubspotClient->trackHubspotEvent($this->merchant->getEmail(), [
            'contact_verified' => true
        ]);

        return $this->user->toArrayPublic();
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
            throw new Exception\LogicException('User does not have a mobile number associated with the account');
        }

        return app('stork_service')->optInForWhatsapp($this->mode, $contact, $input);
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

    public function saveDeviceDetails(array $input)
    {
        return (new DeviceDetail\Core)->createUserDeviceDetail($input);
    }
}
