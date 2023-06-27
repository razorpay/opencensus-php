<?php

namespace RZP\Models\User;

use App;
use Hash;
use Request;
use RZP\Base;
use Lib\PhoneBook;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Vpa;
use RZP\Models\Roles;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Hashing\BcryptHasher;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Exception\BadRequestException;
use libphonenumber\NumberParseException;
use RZP\Models\Merchant\Detail\Entity as MDEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\BusinessDetail\Constants as BDConstants;
use RZP\Models\OAuthApplication\Constants as OAuthApplicationConstants;

/**
 * Class Validator
 *
 * @package RZP\Models\User
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    const DISABLE_CAPTCHA_SECRET                 = 'DISABLE_THE_CAPTCHA_YOU_SHALL';
    const CAPTCHA_MODE_HEADER                    = 'X-RECAPTCHA-MODE';
    const MAX_ALLOWED_CAPTCHA_REQUEST_ATTEMPTS   =  3;

    const CREATE_COMMON_RULES = [
        Entity::ID                              => 'sometimes|max:14',
        Entity::NAME                            => 'sometimes|string|max:200',
        Entity::EMAIL                           => 'required|email',
        Entity::PASSWORD                        => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION           => 'required|between:8,50',
        Entity::CONTACT_MOBILE                  => 'sometimes|nullable|max:15|contact_syntax',
        Entity::REMEMBER_TOKEN                  => 'sometimes',
        Entity::CONFIRM_TOKEN                   => 'sometimes',
        Entity::SETTINGS                        => 'nullable|associative_array',
        Merchant\Constants::PARTNER_INTENT      => 'sometimes|boolean',
        Entity::APP                             => 'sometimes|string',
        // Remove this when signup experiment for X is ramped up.
        Entity::X_VERIFY_EMAIL                  => 'sometimes|string',
        Entity::SIGNUP_VIA_EMAIL                => 'sometimes|in:0,1',
    ];

    protected static $createRules = self::CREATE_COMMON_RULES + [
        Entity::CAPTCHA                         => 'required_without_all:captcha_disable',
        Entity::CAPTCHA_DISABLE                 => 'sometimes|string',
    ];

    protected static $createWithoutCaptchaRules = self::CREATE_COMMON_RULES;

    protected static $createOTPSignupRules = [
        Entity::CAPTCHA                         => 'required_without_all:captcha_disable',
        Entity::CAPTCHA_DISABLE                 => 'sometimes|string',
        Entity::ID                              => 'sometimes|max:14',
        Entity::NAME                            => 'sometimes|string|max:200',
        Entity::EMAIL                           => 'required_without:contact_mobile|email',
        Entity::CONTACT_MOBILE                  => 'required_without:email|max:15|contact_syntax',
        Entity::REMEMBER_TOKEN                  => 'sometimes',
        Entity::CONFIRM_TOKEN                   => 'sometimes',
        Entity::SETTINGS                        => 'nullable|associative_array',
        Merchant\Constants::PARTNER_INTENT      => 'sometimes|boolean',
        Entity::APP                             => 'sometimes|string',
        // Remove this when signup experiment for X is ramped up.
        Entity::X_VERIFY_EMAIL                  => 'sometimes|string',
        Entity::TOKEN                           => 'required|string',
        Entity::OTP                             => 'required|string|between:4,6',
        Entity::SIGNUP_VIA_EMAIL                => 'sometimes|in:0,1',
    ];

    protected static $signupOtpRules = [
        Entity::CONTACT_MOBILE                  => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL                           => 'required_without:contact_mobile|email',
        Entity::TOKEN                           => 'sometimes|string',
        Entity::APP                             => 'sometimes|string'
    ];

    protected static $salesforceOtpRules = [
        Entity::CONTACT_MOBILE                  => 'required|max:15|contact_syntax',
        Entity::TOKEN                           => 'sometimes|string',
        Entity::CAPTCHA                         => 'required|string',
    ];

    protected static $verifySignupOtpRules = [
        Entity::CONTACT_MOBILE                  => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL                           => 'required_without:contact_mobile|email',
        Entity::TOKEN                           => 'required|string',
        Entity::APP                             => 'sometimes|string',
        Entity::CAPTCHA                         => 'required_without_all:captcha_disable',
        Entity::CAPTCHA_DISABLE                 => 'sometimes|string',
        Entity::OTP                             => 'required|string|between:4,6',
        Merchant\Constants::PARTNER_INTENT      => 'sometimes|boolean',
        BDConstants::PHYSICAL_STORE             => 'sometimes|boolean',
        BDConstants::SOCIAL_MEDIA               => 'sometimes|boolean',
        BDConstants::WEBSITE_OR_APP             => 'sometimes|boolean',
        BDConstants::OTHERS                     => 'sometimes|string',
        Merchant\Entity::SIGNUP_SOURCE          => 'sometimes|string',
    ];

    protected static $createOauthRules = [
        Entity::ID                              => 'sometimes|max:14',
        Entity::NAME                            => 'sometimes|string|max:200',
        Entity::EMAIL                           => 'required|email|unique:users,email',
        Entity::CONTACT_MOBILE                  => 'sometimes|nullable|max:15|contact_syntax',
        Entity::SETTINGS                        => 'nullable|associative_array',
        Merchant\Constants::PARTNER_INTENT      => 'sometimes|boolean',
        Entity::APP                             => 'sometimes|string',
        Entity::OAUTH_PROVIDER                  => 'required|string|custom',
        Entity::SIGNUP_VIA_EMAIL                => 'sometimes|in:0,1',
    ];

    protected static $editRules = [
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::CONTACT_MOBILE        => 'sometimes|nullable|max:15|contact_syntax',
//        Entity::EMAIL                 => 'sometimes|email|unique:users,email',
        Entity::SETTINGS              => 'nullable|associative_array',
    ];

    protected static $oauthRequestRules = [
        Constants::OAUTH_SOURCE   => 'sometimes|string',
        Constants::ID_TOKEN       => 'required|string',
        Entity::OAUTH_PROVIDER    => 'required|string|custom',
        Entity::EMAIL             => 'required|email',
        MDEntity::REFERRAL_CODE   => 'filled|string',
    ];

    protected static $editEmailForMerchantRules = [
        Entity::EMAIL                 => 'filled|email|unique:users,email',
    ];

    protected static $changePasswordRules = [
        Entity::PASSWORD              => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:8,50',
        Entity::OLD_PASSWORD          => 'required|string',
    ];

    protected static $setPasswordRules = [
        Entity::PASSWORD              => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:8,50',
    ];

    protected static $salesforceEventRules = [
        Entity::EMAIL              => 'required|email|string',
        Entity::NAME               => 'required|string',
        Entity::COMPANY            => 'required|string',
        Entity::REVENUE            => 'required|string',
    ];

    protected static $actionRules = [
        Entity::ACTION                => 'required|custom',
        Entity::MERCHANT_ID           => 'required|max:14',
        Merchant\Entity::PRODUCT      => 'required|in:primary,banking',
        Entity::ROLE                  => 'sometimes|string|custom',
    ];

    protected static $loginRules = [
        Entity::EMAIL             => 'required|email',
        Entity::PASSWORD          => 'required|between:6,50',
        Entity::CAPTCHA           => 'required_without:captcha_disable',
        Entity::CAPTCHA_DISABLE   => 'sometimes|string',
        Entity::APP               => 'sometimes|string',
        MDEntity::REFERRAL_CODE   => 'filled|string',
    ];

    protected static $loginMobileRules = [
        Entity::CONTACT_MOBILE  => 'required|max:15|contact_syntax',
        Entity::PASSWORD        => 'required|between:6,50',
        Entity::CAPTCHA         => 'required_without:captcha_disable',
        Entity::CAPTCHA_DISABLE => 'sometimes|string',
        Entity::APP             => 'sometimes|string',
    ];

    protected static $loginOtpRules = [
        Entity::CONTACT_MOBILE   => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL            => 'required_without:contact_mobile|email',
        Entity::TOKEN            => 'sometimes|string',
        Entity::SKIP_SMS_REQUEST => 'sometimes|boolean'
    ];

    protected static $verifyLoginOtpRules = [
        Entity::CONTACT_MOBILE            => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL                     => 'required_without:contact_mobile|email',
        Entity::TOKEN                     => 'required|string',
        Entity::OTP                       => 'required|string|between:4,6',
        Entity::CAPTCHA                   => 'required_without:captcha_disable',
        Entity::CAPTCHA_DISABLE           => 'sometimes|string',
        MDEntity::REFERRAL_CODE           => 'filled|string',
    ];

    protected static $loginOtp2faPasswordRules = [
        Entity::PASSWORD        => 'required|between:6,50'
    ];

    protected static $loginOauthRules = [
        Entity::EMAIL             => 'required|email',
        Entity::OAUTH_PROVIDER    => 'required|string|custom',
        Constants::ID_TOKEN       => 'sometimes|string',
        Constants::OAUTH_SOURCE   => 'sometimes|string',
        Entity::APP               => 'sometimes|string',
        MDEntity::REFERRAL_CODE   => 'filled|string',
    ];

    protected static $sendVerificationOtpRules = [
        Entity::CONTACT_MOBILE  => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL           => 'required_without:contact_mobile|email',
        Entity::TOKEN           => 'sometimes|string',
        Entity::PASSWORD        => 'required|between:6,50',
    ];

    protected static $verifyVerificationOtpRules = [
        Entity::CONTACT_MOBILE  => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL           => 'required_without:contact_mobile|email',
        Entity::TOKEN           => 'required|string',
        Entity::OTP             => 'required|string|between:4,6',
        Entity::CAPTCHA         => 'required_without:captcha_disable',
        Entity::CAPTCHA_DISABLE => 'sometimes|string',
    ];

    protected static $verifyUserSecondFactorRules = [
        Entity::OTP                   => 'required|string|between:4,6',
    ];

    protected static $setup2faMobileRules = [
        Entity::CONTACT_MOBILE        => 'required|max:15|contact_syntax',
    ];

    protected static $setup2faVerifyMobileRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
        Entity::OTP                   => 'required|filled|min:4'
    ];

    protected static $change2faSettingRules = [
        Entity::PASSWORD              => 'sometimes|between:6,50',
        Entity::SECOND_FACTOR_AUTH    => 'required|boolean',
    ];

    protected static $resetIncorrectPasswordCountRules = [
        'emails'   => 'required|array|max:500',
        'emails.*' => 'required|email',
    ];

    protected static $confirmRules = [
        Entity::CONFIRM_TOKEN         => 'sometimes',
        Entity::EMAIL                 => 'sometimes|email',
    ];

    protected static $preSignupRules = [
        Entity::NAME                  => 'sometimes|alpha_space|max:200',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15|contact_syntax',
//        Entity::EMAIL                 => 'sometimes|email'
    ];

    protected static $teamManagementRules = [
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        Entity::USER_ID     => 'required|alpha_num|size:14',
        Entity::ROLE        => 'sometimes|string',
    ];

    protected static $changePasswordTokenRules = [
        Entity::CONTACT_MOBILE        => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL                 => 'required_without:contact_mobile|email',
        Entity::PASSWORD              => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:8,50',
        Entity::TOKEN                 => 'required|string|size:50',
    ];

    protected static $resetPasswordRules = [
        Entity::CONTACT_MOBILE        => 'required_without:email|max:15|contact_syntax',
        Entity::EMAIL                 => 'required_without:contact_mobile|email',
    ];

    protected static $changePasswordAdminRules = [
        Entity::PASSWORD              => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:8,50',
    ];

    protected static $editContactMobileRules = [
        Entity::OTP_AUTH_TOKEN => 'required|filled',
        Entity::CONTACT_MOBILE => 'required|max:15|contact_syntax',
    ];

    protected static $fetchMerchantIdsByUserContactRules = [
        Entity::CONTACT_MOBILE => 'required|max:15|contact_syntax',
    ];

    protected static $optInWhatsappRules = [
        'source'               => 'required|string',
        'send_welcome_message' => 'sometimes|boolean',
        'business_account'     => 'sometimes|string|in:razorpayx,razorpay'
    ];

    protected static $optOutWhatsappRules = [
        'source'               => 'required|string',
        'business_account'     => 'sometimes|string|in:razorpayx,razorpay'
    ];

    protected static $optInStatusWhatsappRules = [
        'source'               => 'required|string',
        'business_account'     => 'sometimes|string|in:razorpayx,razorpay'
    ];

    protected static $updateContactMobileRules = [
        Entity::USER_ID        => 'required|alpha_num|size:14',
        Entity::CONTACT_MOBILE => 'required|max:15|contact_syntax',
    ];

    protected static $bulkUserMappingRules = [
        Entity::USER_ID               => 'required|alpha_num|size:14',
        Entity::MERCHANT_ID           => 'required|alpha_num|size:14',
        Merchant\Entity::PRODUCT      => 'required|in:primary,banking',
        Entity::ROLE                  => 'required|string|custom',
        Entity::ACTION                => 'required|custom',
    ];

    protected static $verifyContactMobileRules = [
        ENTITY::EMAIL               => 'required|email',
        ENTITY::CONTACT_MOBILE      => 'required|max:15|contact_syntax',
    ];

    protected static $verifyContactMobileListRules = [
        'input'             => 'required|array'
    ];

    protected static $addEmailRules = [
        Entity::OTP_AUTH_TOKEN => 'required|filled',
        Entity::EMAIL          => 'required|email',
    ];

    protected static $addEmailVerifyRules = [
        Entity::OTP             => 'required|filled|min:4',
        Entity::EMAIL           => 'required|email',

    ];

    protected static $actionValidators = [
        'product_role'
    ];

    protected static $userAccountLockUnlockRules = [
        Entity::USER_ID => 'required|alpha_num|size:14',
        Entity::ACTION  => 'required|string|filled|in:lock,unlock,un_verify',
    ];

    protected static $createEmailUniqueRules = [
        Entity::EMAIL => 'required|email|unique:users,email',
    ];

    protected static $createMobileUniqueRules = [
        Entity::CONTACT_MOBILE => 'required|max:15|contact_syntax|unique:users,contact_mobile',
    ];

    protected static $createOtpRules = [
        // When medium is not sent OTP is sent to both mediums.
        Entity::MEDIUM        => 'sometimes|filled|in:sms,email,sms_and_email',
        Entity::ACTION        => 'required|filled|in:'
                                 . 'verify_contact,'
                                 . 'verify_user,'
                                 . 'verify_email,'
                                 . 'x_verify_email,'
                                 . 'create_payout,'
                                 . 'create_composite_payout_with_otp,'
                                 . 'sub_virtual_account_transfer,'
                                 . 'create_payout_link,'
                                 . 'create_payout_batch,'
                                 . 'create_payout_batch_v2,'
                                 . 'approve_payout,'
                                 . 'approve_payout_bulk,'
                                 . 'second_factor_auth,'
                                 . 'user_auth,'
                                 . 'bulk_payout_approve,'
                                 . 'create_bulk_payout_link,'
                                 . 'replace_key,'
                                 . 'apple_watch_token,'
                                 . 'create_workflow_config,'
                                 . 'update_workflow_config,'
                                 . 'delete_workflow_config,'
                                 . 'ip_whitelist',
        Entity::TOKEN         => 'sometimes|filled',

        // Applicable to select actions: Need to send these payloads for raven's sms content.
        'amount'                  => 'required_if:action,create_payout,create_composite_payout_with_otp,sub_virtual_account_transfer,approve_payout,create_payout_link|integer|min:100',
        'account_number'          => 'required_if:action,create_payout,create_composite_payout_with_otp,create_payout_batch,create_payout_batch_v2,approve_payout,approve_payout_bulk,create_payout_link,create_bulk_payout_link|alpha_num|between:5,22',
        'master_account_number'   => 'required_if:action,sub_virtual_account_transfer|alpha_num|between:5,22',
        'sub_account_number'      => 'required_if:action,sub_virtual_account_transfer|alpha_num|between:5,22',
        'fund_account_id'         => 'required_if:action,create_payout|public_id|size:17',
        'purpose'                 => 'required_if:action,create_payout,create_composite_payout_with_otp,create_payout_link|string|max:30|alpha_dash_space',
        'payout_id'               => 'required_if:action,approve_payout|public_id|size:19',
        'payout_total_amount'     => 'required_if:action,approve_payout_bulk|integer|min:100',
        'payout_count'            => 'required_if:action,approve_payout_bulk|integer|min:1',
        'approved_payout_count'   => 'required_if:action,bulk_payout_approve|integer',
        'approved_payout_amount'  => 'required_if:action,bulk_payout_approve|numeric',
        'rejected_payout_count'   => 'required_if:action,bulk_payout_approve|integer',
        'rejected_payout_amount'  => 'required_if:action,bulk_payout_approve|numeric',
        'total_payout_amount'     => 'required_if:action,create_payout_batch_v2|integer|min:100',
        'vpa'                     => 'required_if:action,create_composite_payout_with_otp|string|max:100|custom',
        'contact'                 => 'sometimes_if:action,create_payout_link',
        'total_payout_link_amount'=> 'required_if:action,create_bulk_payout_link|integer',
        'whitelisted_ips'         => 'required_if:action,ip_whitelist|array|min:1|max:20'
    ];

    protected static $sendOtpWithContactRules = [
        Entity::ACTION          => 'required|filled|in:bureau_verify,verify_support_contact,verify_contact',
        Entity::TOKEN           => 'sometimes|filled',
        Entity::CONTACT_MOBILE  => 'required|max:15|contact_syntax',
        Entity::MEDIUM          => 'sometimes|filled|in:sms',
    ];

    protected static $verifyOtpRules = [
        Entity::OTP             => 'required|filled|min:4',
        Entity::TOKEN           => 'required|unsigned_id',
        Entity::ACTION          => 'sometimes|filled|in:bureau_verify,verify_support_contact,verify_user',
        Entity::CONTACT_MOBILE  => 'required_if:action,bureau_verify,verify_support_contact|max:15|contact_syntax',
    ];

    protected static $verifyOtpFromUpdateRules = [
        Constants::OTP             => 'required|filled|min:4',
        Constants::RECEIVER        => 'required|max:15|contact_syntax',
    ];

    protected static $newAccessTokenRules = [
        OAuthApplicationConstants::MERCHANT_ID     => 'required|alpha_num|size:14',
        OAuthApplicationConstants::REFRESH_TOKEN   => 'required',
        OAuthApplicationConstants::CLIENT_ID       => 'required',
    ];

    protected static $resendOtpRules = [
        Entity::TOKEN           => 'sometimes|unsigned_id',
    ];

    protected static $getUserByEmailRules = [
        Entity::EMAIL           => 'required|email',
    ];

    protected static $getDetailsRules = [
        Entity::EMAIL           => 'required|email',
    ];

    protected static $getUserRolesRules = [
        'user_id'     => 'required|alpha_num|size:14',
        'merchant_id' => 'required|alpha_num|size:14',
    ];

    protected static $switchMerchantRules = [
        OAuthApplicationConstants::MERCHANT_ID  => 'required|alpha_num|size:14',
        OAuthApplicationConstants::ACCESS_TOKEN => 'required',
        OAuthApplicationConstants::CLIENT_ID    => 'required',
    ];

    protected static $updateUserNameRules = [
        'name' => 'required|string|min:4|max:200',
    ];

    protected static $teamManagementValidators = [
        'self_user',
        'team_user',
        'owner'
    ];

    protected static $createValidators = [
        'captcha',
        'email_or_mobile_unique',
    ];

    protected static $createWithoutCaptchaValidators = [
        'email_or_mobile_unique',
    ];

    protected static $createOTPSignupValidators = [
        'email_or_mobile_unique'
    ];

    protected static $loginValidators = [
        'captcha'
    ];

    protected static $salesforceOtpValidators = [
        'captcha_only'
    ];

    protected static $loginMobileValidators = [
        'captcha'
    ];

    protected static $verifyLoginOtpValidators = [
        'captcha_only'
    ];

    protected static $verifyVerificationOtpValidators = [
        'captcha_only'
    ];

    protected static $changePasswordValidators = [
        'old_password'
    ];

    protected static $verifyUserThroughModeRules = [
        Entity::OTP             => 'required|filled|min:4',
        Entity::ACTION          => 'required',
        Entity::TOKEN           => 'required|unsigned_id',
        Entity::MEDIUM          => 'required|in:sms,email,sms_and_email',
    ];

    protected static $verifySignupOtpValidators = [
        'captcha_only',
        'country_code'
    ];

    protected static $signupOtpValidators = [
        'country_code'
    ];

    protected static $resetPasswordValidators = [
        'country_code'
    ];

    /**
     * merchant can not edit or delete his own user id.
     * @param array $input
     *
     * @throws BadRequestException
     */
    protected function validateSelfUser(array $input)
    {
        $app = App::getFacadeRoot();

        $dashboardUser = $app['basicauth']->getUser();

        if ((empty($dashboardUser) === true) or ($input['user_id'] === $dashboardUser->getId()))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_SELF_USER);
        }
    }

    /**
     * any user role cannot update the owner role
     * @param array $input
     *
     * @throws BadRequestException
     */
    protected function validateOwner(array $input)
    {
        $app = App::getFacadeRoot();

        $product = $app['basicauth']->getRequestOriginProduct();

        if ($product === Product::BANKING)
        {
            $user = (new Merchant\Repository)->getMerchantUserMapping($input['merchant_id'], $input['user_id'], null, Product::BANKING);

            if ($user->pivot->role === 'owner')
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_OWNER_ROLE);
            }

            if ($input[Entity::ROLE] === 'owner')
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_OWNER_ROLE);
            }
        }

    }

    protected function validateProductRole(array $input)
    {
        if (empty($input[Entity::ROLE]) === true)
        {
            return;
        }

        $role    = $input[Entity::ROLE];
        $product = $input[Entity::PRODUCT];

        /** @var Merchant\Entity|null $merchant */
        $merchant = $this->entity->merchant;

        if (Role::validateProductRoleForMerchant($role, $product, $merchant) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_ROLE_INVALID,
                Entity::ROLE,
                [Entity::ROLE => $role, Entity::PRODUCT => $product]);
        }
    }

    protected function validateOldPassword(array $input)
    {
        $user = $this->entity;

        if (Hash::check($input[Entity::OLD_PASSWORD], $user->getPassword()) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_OLD_PASSWORD_MISMATCH);
        }
    }

    /**
     * This function handles https://www.owasp.org/index.php/Top_10_2013-A4-Insecure_Direct_Object_References
     * @param array $input
     *
     * @throws BadRequestException
     */
    protected function validateTeamUser(array $input)
    {
        $user = (new Merchant\Repository)->getMerchantUserMapping($input['merchant_id'], $input['user_id']);

        if (empty($user) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT);
        }
    }

    protected function validateRole(string $attribute, string $role)
    {
        $bankingRole = (new Roles\Repository())->fetchRole($role);

        if ((Role::exists($role) === false) and
            (empty($bankingRole) === true) and
            (BankingRole::existsBankingLMSRole($role) === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_ROLE_INVALID,
                Entity::ROLE,
                [Entity::ROLE => $role]);
        }
    }

    /**
     * Validate Json Encoded oauth Provider
     * @param string $attribute
     * @param string $oauthProvider
     *
     * @throws BadRequestException
     */
    protected function validateOauthProvider(string $attribute, string $oauthProvider)
    {
        $decodedOauthProvider = json_decode($oauthProvider, true);

        $oauthProvider = $decodedOauthProvider[0] ?? null;

        OauthProvider::validate($oauthProvider);
    }

    public function incrementRequestCount(string $merchantEmail)
    {
        $app = App::getFacadeRoot();

        try
        {
            $redis = $app['redis']->Connection('mutex_redis');

            $index = $redis->incr($merchantEmail);

            // add expiry for first increment of the key
            if ($index === 1)
            {
                $redis->expire($merchantEmail, Constants::INCORRECT_LOGIN_TTL);
            }
        }
        catch (\Throwable $e)
        {
            $app['trace']->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::USER_INCORRECT_PASSWORD_REDIS_ERROR,
                ['key' => $merchantEmail]);

            return;
        }
    }

    /**
     * @param array $input
     * @throws NumberParseException
     */
    protected function validateEmailOrMobileUnique(array $input)
    {
        if (isset($input[Entity::EMAIL]) === true)
        {
            $this->validateInput('createEmailUnique', [Entity::EMAIL => $input[Entity::EMAIL]]);
        }
        else if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $validMobileNumberFormats = (new PhoneBook($input[Entity::CONTACT_MOBILE]))->getMobileNumberFormats();
            foreach ($validMobileNumberFormats as $mobileNumber)
            {
                $this->validateInput('createMobileUnique', [Entity::CONTACT_MOBILE => $mobileNumber]);
            }
        }
    }

    /**
     * @throws BadRequestException
     */
    protected function checkCaptchaWithGoogle(
        $app, $input, $emailData,
        $verificationSuccessEventCode = null,
        $verificationFailedEventCode = null)
    {

        if ((in_array($app->environment(), Constants::WHITELIST_ENVIRONMENT_CAPTCHA_VALIDATION, true) === true) and
            (in_array($emailData['email'], Constants::WHITELIST_CAPTCHA_EMAILS, true) === false) and
            (in_array($input[Entity::CONTACT_MOBILE] ?? null, Constants::WHITELIST_CAPTCHA_CONTACT_MOBILE, true) === false))
        {
            $captchaResponse = $input[Entity::CAPTCHA] ?? null;

            $captchaSecret = $this->getCaptchaSecret($input);

            $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'];

            $input = [
                'secret'   => $captchaSecret,
                'response' => $captchaResponse,
                'remoteip' => $clientIpAddress,
            ];

            $captchaQuery = http_build_query($input);

            $url = 'https://www.google.com/recaptcha/api/siteverify?'. $captchaQuery;

            $start_time = microtime(true) ;
            $response = $this->getCaptchaVerificationResponse($url);
            $end_time = microtime(true);
            $captcha_validation_duration = ($end_time - $start_time) * 1000; // in milliseconds

            $app['trace']->histogram(Metric::CAPTCHA_VALIDATION_DURATION, $captcha_validation_duration);

            $output = json_decode($response->body);

            $app['trace']->info(TraceCode::CAPTCHA_ENABLED, [$captchaResponse,  $emailData]);

            if($output->success !== true)
            {
                if (empty($verificationFailedEventCode) == false)
                {
                    $app['diag']->trackOnboardingEvent($verificationFailedEventCode, null, null, $emailData);
                }

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                    null,
                    [
                        'output_from_google'        => (array)$output,
                        // adding little more data temporarily for deubbing
                        'emailData'                 => $emailData,
                        'captcha_mode_header'       => Request::header(self::CAPTCHA_MODE_HEADER),
                        'remoteip'                  => $clientIpAddress,
                    ]
                );
            }
            else if(array_key_exists('score', (array)$output) === true)
            {
                $threshold = 0.9;
                if ($output->score < $threshold)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_CAPTCHA_SCORE_LOW,
                        null,
                        [
                            'output_from_google'        => (array)$output,
                            'emailData'                 => $emailData,
                            'captcha_mode_header'       => Request::header(self::CAPTCHA_MODE_HEADER),
                            'remoteip'                  => $clientIpAddress,
                        ]
                    );
                }
                else
                {
                    $router = $app['router'];

                    $payload = [
                        'captcha_mode_header'       => Request::header(self::CAPTCHA_MODE_HEADER),
                        'emailData'                 => $emailData,
                        'route'                     => $router->currentRouteName(),
                        'score'                     => $output->score,
                        'threshold'                 => $threshold,

                    ];
                    if (empty($verificationSuccessEventCode) == false)
                    {
                        $app['diag']->trackOnboardingEvent($verificationSuccessEventCode, null, null, $payload);
                    }
                }
            }
        }
    }

    /**
     * @param array $input
     * @throws BadRequestValidationFailureException
     * @throws NumberParseException
     */
    protected function validateCountryCode(array $input)
    {
        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $phoneNumber = $input[Entity::CONTACT_MOBILE];
            $parsedPhoneNumber = new PhoneBook($phoneNumber);
            $countryCode = $parsedPhoneNumber->getRegionCodeForNumber();

            if (!in_array($countryCode, Constants::SUPPORTED_COUNTRY_CODES_SIGNUP))
            {
                throw new BadRequestValidationFailureException('Unsupported Country Code.');
            }
        }
    }

    /**
     * @param array $input
     * @throws BadRequestException
     */
    protected function validateCaptchaOnly(array $input)
    {
        $app = App::getFacadeRoot();

        if ($this->isCaptchaDisabledAndReceiverSet($input))
        {
            return;
        }

        $emailData['email'] = $input[Entity::EMAIL] ?? null;
        $emailData['masked_contact_mobile'] = isset($input[Entity::CONTACT_MOBILE]) ? mask_phone($input[Entity::CONTACT_MOBILE]) : null;

        $this->checkCaptchaWithGoogle($app, $input, $emailData);
    }

    protected function isCaptchaDisabledAndReceiverSet($input): bool
    {
        if( $this->isCaptchaDisabled($input) === true )
        {
            if (isset($input[Entity::EMAIL]) === true
                or isset($input[Entity::CONTACT_MOBILE]) === true)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Google captcha validation.
     *
     * @param array $input
     *
     * @throws BadRequestException
     */
    protected function validateCaptcha(array $input)
    {
        $app = App::getFacadeRoot();

        if ($this->isCaptchaDisabledAndReceiverSet($input))
        {
            $this->handleCaptchaDisabled($input[Entity::EMAIL] ?? $input[Entity::CONTACT_MOBILE]);
            return;
        }

        $emailData['email'] = $input[Entity::EMAIL] ?? null;
        $emailData['masked_contact_mobile'] = isset($input[Entity::CONTACT_MOBILE]) ? mask_phone($input[Entity::CONTACT_MOBILE]) : null;

        $this->checkCaptchaWithGoogle(
            $app,
            $input,
            $emailData,
            EventCode::CAPTCHA_TOKEN_VERIFICATION_SUCCESS,
            EventCode::SIGNUP_CAPTCH_VERIFICATION_FAILED
        );

        $app['diag']->trackOnboardingEvent(EventCode::SIGNUP_CAPTCHA_VERIFICATION_SUCCESS, null, null, $emailData);
    }

    protected function getCaptchaVerificationResponse(string $url, int $maxAllowedAttempts = self::MAX_ALLOWED_CAPTCHA_REQUEST_ATTEMPTS)
    {
        $app = App::getFacadeRoot();

        $currentAttempt = 1;

        while ($currentAttempt <= $maxAllowedAttempts)
        {
            try
            {
                $response = $this->makeRequestAndGetCaptchaVerificationResponse($url);

                if ($currentAttempt > 1)
                {
                    $app['trace']->info(TraceCode::CAPTCHA_RETRY_LOGIC_SUCCESS, ['attempt' => $currentAttempt]);
                }

                return $response;
            }
            catch (\WpOrg\Requests\Exception $e)
            {
                $app['trace']->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::CAPTCHA_VERIFICATION_CALL_FAILED,
                    ['attempt' => $currentAttempt]);

                if ($currentAttempt == $maxAllowedAttempts)
                {
                    throw $e;
                }
            }

            $currentAttempt++;
        }
    }

    protected function makeRequestAndGetCaptchaVerificationResponse(string $url)
    {
        $response = \Requests::get($url);

        return $response;
    }

    protected function handleCaptchaDisabled($loginMedium)
    {
        $app = App::getFacadeRoot();

        $this->incrementRequestCount($loginMedium);

        // check if attempts is greater than threshold
        // if yes throw error captcha is required.
        $count = $this->getIncorrectPasswordCount($loginMedium);

        if ($count > Constants::INCORRECT_LOGIN_THRESHOLD_COUNT)
        {
            $app['trace']->info(TraceCode::USER_LOGIN_INCORRECT_PASSWORD_EXHAUSTED, [$loginMedium]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INCORRECT_LOGIN_ATTEMPT
            );
        }

        $app['trace']->info(TraceCode::USER_LOGIN_CAPTCHA_DISABLED, [$loginMedium]);

        return;
    }

    protected function validateVpa($attribute, $vpa)
    {
        (new Vpa\Validator)->validateAddress($attribute, $vpa);

        $vpaParts = explode('@', $vpa);

        if ((ProviderCode::validate($vpaParts[1]) === false))
        {
            // Invalid VPA
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                $attribute,
                [
                    'vpa' => $vpa
                ]);
        }
    }

    protected function validateAction(string $attribute, string $action)
    {
        if (Action::exists($action) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED);
        }
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    public function isCaptchaDisabled(array $input): bool
    {
        if ((empty($input[Entity::CAPTCHA_DISABLE]) === false) and
            ($input[Entity::CAPTCHA_DISABLE] === self::DISABLE_CAPTCHA_SECRET))
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    protected function isOauthEnabled(array $input): bool
    {
        if (empty($input[Entity::OAUTH_PROVIDER]) === false)
        {
            return true;
        }

        return false;
    }

    /**
     * Validates send OTP operation
     *
     * @param  array $input
     * @throws BadRequestValidationFailureException
     */
    public function validateSendOtpOperation(array $input)
    {
        /** @var Entity $user */
        $user = $this->entity;

        $this->validateInput('createOtp', $input);

        $action = $input[Entity::ACTION];
        // Medium is optional input, for validation logic here assigns 'both' as the value.
        $medium = $input[Entity::MEDIUM] ?? 'both';

        if (($action === 'user_auth') and
            ($medium !== 'email'))
        {
            throw new BadRequestValidationFailureException('Otp must be sent to registered email');
        }

        if ((in_array($action, Constants::ACTIONS_FOR_OTP_CONTACT_VERIFICATION, true) === true) and
            ($medium !== 'sms'))
        {
            throw new BadRequestValidationFailureException('Sms must be the medium for verifying contact');
        }

        if ((in_array($action, Constants::ACTIONS_FOR_OTP_CONTACT_VERIFICATION, true) === true) and
            ($user->isContactMobileVerified() === true))
        {
            throw new BadRequestValidationFailureException('Contact mobile is already verified');
        }

        if (($medium === 'sms') and
            ($user->getContactMobile() === null))
        {
            throw new BadRequestValidationFailureException('Contact mobile does not exist');
        }

        if (($medium === 'sms') and
            (in_array($action, Constants::ACTIONS_FOR_OTP_CONTACT_VERIFICATION, true) === false) and
            ($user->isContactMobileVerified() === false))
        {
            throw new BadRequestValidationFailureException('Contact mobile is not verified');
        }

        if (($action === 'verify_email' ||
             $action === 'x_verify_email') and
            ($medium !== 'email'))
        {
            throw new BadRequestValidationFailureException('Email must be the medium for verifying Email');
        }

        if (($action === 'verify_email' ||
             $action === 'x_verify_email') and
            ($user->getConfirmedAttribute() === true))
        {
            throw new BadRequestValidationFailureException('Email is already verified');
        }

        if (($medium === 'email') and
            ($user->getEmail() === null))
        {
            throw new BadRequestValidationFailureException('Email does not Exist');
        }

        if (($medium === 'email') and
            ($action !== 'verify_email' and
             $action !== 'x_verify_email') and
            ($user->getConfirmedAttribute() === false))
        {
            throw new BadRequestValidationFailureException('Contact Email is not verified');
        }

        if (($medium !== 'sms') and
            ($action === 'apple_watch_token'))
        {
            throw new BadRequestValidationFailureException('Sms must be the medium for generating Apple Watch token');
        }
    }

    public function validateVerifyContactWithOtpOperation(array $input)
    {
        if ($this->entity->isContactMobileVerified() === true)
        {
            throw new BadRequestValidationFailureException('Contact mobile is already verified');
        }

        $this->validateInput('verifyOtp', $input);
    }

    public function validateVerifyEmailWithOtpOperation(array $input)
    {
        $this->validateInput('verifyOtp', $input);
    }

    public function validateResendEmailWithOtpOperation(array $input)
    {
        $this->validateInput('resendOtp', $input);
    }


    /**
     * @param  Merchant\Entity $merchant
     * @param  Entity          $user
     *
     * @throws Exception\BadRequestException
     */
    public function validateMerchantUserRelation(Merchant\Entity $merchant, Entity $user)
    {
        if (in_array($user->getId(), $merchant->users()->get()->getIds(), true) === true)
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT);
    }

    /**
     * @deprecated
     */
    public function validatePasswordIsNotSameAsLastThree(string $newPassword)
    {
        $oldPassword  = $this->entity->getAttribute(Entity::PASSWORD);
        $oldPassword1 = $this->entity->getAttribute(Entity::OLD_PASSWORD_1);
        $oldPassword2 = $this->entity->getAttribute(Entity::OLD_PASSWORD_2);

        foreach (array_filter([$oldPassword, $oldPassword1, $oldPassword2]) as $old)
        {
            if (Hash::check($newPassword, $old) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_NEW_PASSWORD_SAME_AS_OLD_PASSWORD);
            }
        }
    }

    private function getCaptchaSecret(array $input): string
    {
        if (empty($input[Entity::APP]) === false and $input[Entity::APP] === 'android')
        {
            return config('app.signup.android_captcha_secret');
        }

        $captchaMode = Request::header(self::CAPTCHA_MODE_HEADER);

        if ($captchaMode === 'invisible')
        {
            return config('app.signup.invisible_captcha_secret');
        }

        if ($captchaMode === 'v3')
        {
            return config('app.signup.v3_captcha_secret');
        }

        return config('app.signup.nocaptcha_secret');
    }

    public function validatePasswordResetToken($user, $token)
    {
        $app = App::getFacadeRoot();

        $expiry = $user->getPasswordResetExpiry();

        $now = Carbon::now()->getTimestamp();

        //If expiry and user token are not null, flow will proceed
        if ((isset($expiry) === false) or
            ($expiry < $now))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }

        $userToken = $user->getPasswordResetToken();

        if ((isset($token) === false) or
            (isset($userToken) === false) or
            (hash_equals($userToken, $token) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID);
        }
    }

    public function validateUniqueNumberExcludingCurrentUser(Entity $user, $number)
    {
        $validContactMobileNumberFormats = (new PhoneBook($number))->getMobileNumberFormats();

        // if same number already verified
        if ((in_array($user->getContactMobile(), $validContactMobileNumberFormats, true)) and
            ($user->isContactMobileVerified() === true))
        {
            throw new BadRequestValidationFailureException('Contact mobile is already verified');
        }

        $response = (new Repository())->findUserWithContactNumbersExcludingUser($user->getId(), $validContactMobileNumberFormats);

        if (isset($response) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN);
        }
    }

    public function validateOauthRequest(array $input)
    {
        $this->validateInputValues('oauth_request', $input);
    }

    public function getIncorrectPasswordCount(string $merchantEmail): int
    {
        $app = App::getFacadeRoot();

        $redis = $app['redis']->Connection('mutex_redis');

        try
        {
            return $redis->get($merchantEmail) ?? 0;
        }
        catch (\Throwable $e)
        {
            $app['trace']->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::USER_INCORRECT_PASSWORD_REDIS_ERROR,
                ['key' => $merchantEmail]);

            return 0;
        }
    }

    public function validateThrottleContactMobileLimit(int $attempts)
    {
        if ($attempts >= Constants::THROTTLE_UPDATE_CONTACT_MOBILE_LIMIT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LIMIT_FOR_UPDATE_CONTACT_MOBILE_EXCEEDED);
        }
    }

    public function validateSendOtpLimitNotExceeded(Entity $user)
    {
        $app = App::getFacadeRoot();
        $redis = $app['redis']->Connection('mutex_redis');

        $fullKey = Constants::THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_PREFIX.$user->getId();
        $index = $redis->incr($fullKey);

        if ($index === 1)
        {
            $redis->expire($fullKey, Constants::THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_LIMIT_TTL);
        }

        if ($index > Constants::THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_LIMIT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SMS_OTP_FAILED);
        }
    }
}
