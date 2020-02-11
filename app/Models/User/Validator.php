<?php

namespace RZP\Models\User;

use App;
use Hash;

use RZP\Base;
use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\User
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    const DISABLE_CAPTCHA_SECRET = 'DISABLE_THE_CAPTCHA_YOU_SHALL';

    protected static $createRules = [
        Entity::ID                              => 'sometimes|max:14',
        Entity::NAME                            => 'sometimes|string|max:200',
        Entity::EMAIL                           => 'required|email|unique:users,email',
        Entity::PASSWORD                        => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION           => 'required|between:8,50',
        Entity::CONTACT_MOBILE                  => 'sometimes|max:15',
        Entity::REMEMBER_TOKEN                  => 'sometimes',
        Entity::CONFIRM_TOKEN                   => 'sometimes',
        Entity::CAPTCHA                         => 'required_without:captcha_disable',
        Entity::CAPTCHA_DISABLE                 => 'sometimes|string',
        Entity::SETTINGS                        => 'nullable|associative_array',
        Merchant\Constants::PARTNER_INTENT      => 'sometimes|boolean',
    ];

    protected static $editRules = [
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
        Entity::SETTINGS              => 'nullable|associative_array',
    ];

    protected static $editEmailForMerchantRules = [
        Entity::EMAIL                 => 'filled|email|unique:users,email',
    ];

    protected static $changePasswordRules = [
        Entity::PASSWORD              => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:8,50',
        Entity::OLD_PASSWORD          => 'required|string',
    ];

    protected static $actionRules = [
        Entity::ACTION                => 'required|custom',
        Entity::MERCHANT_ID           => 'required|max:14',
        Merchant\Entity::PRODUCT      => 'required|in:primary,banking',
        Entity::ROLE                  => 'sometimes|string|custom',
    ];

    protected static $loginRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
        Entity::OTP                   => 'sometimes|filled',
    ];

    protected static $setup2faMobileRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
        Entity::CONTACT_MOBILE        => 'required|max:15',
    ];

    protected static $setup2faVerifyMobileRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
        Entity::OTP                   => 'required|filled|min:4'
    ];

    protected static $change2faSettingRules = [
        Entity::PASSWORD              => 'required|between:6,50',
        Entity::SECOND_FACTOR_AUTH    => 'required|boolean',
    ];

    protected static $confirmRules = [
        Entity::CONFIRM_TOKEN         => 'sometimes',
        Entity::EMAIL                 => 'sometimes|email',
    ];

    protected static $preSignupRules = [
        Entity::NAME                  => 'sometimes|alpha_space|max:200',
        Entity::CONTACT_MOBILE        => 'sometimes|numeric|digits_between:8,11',
    ];

    protected static $teamManagementRules = [
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        Entity::USER_ID     => 'required|alpha_num|size:14',
    ];

    protected static $changePasswordTokenRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:8,50',
        Entity::TOKEN                 => 'required|string|size:50',
    ];

    protected static $changePasswordAdminRules = [
        Entity::PASSWORD              => 'required|between:8,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:8,50',
    ];

    protected static $editContactMobileRules = [
        Entity::OTP_AUTH_TOKEN => 'sometimes|filled',
        Entity::CONTACT_MOBILE => 'required|max:15',
        Entity::OTP            => 'sometimes|filled|min:4',
    ];

    protected static $updateContactMobileRules = [
        Entity::USER_ID        => 'required|alpha_num|size:14',
        Entity::CONTACT_MOBILE => 'required|numeric|digits_between:8,11',
    ];

    protected static $actionValidators = [
        'product_role'
    ];

    protected static $userAccountLockUnlockRules = [
        Entity::USER_ID         =>  'required|alpha_num|size:14',
        Entity::ACTION          =>  'required|string|filled|in:lock,unlock',
    ];

    protected static $createOtpRules = [
        // When medium is not sent OTP is sent to both mediums.
        Entity::MEDIUM        => 'sometimes|filled|in:sms,email',
        Entity::ACTION        => 'required|filled|in:'
                                 . 'verify_contact,'
                                 . 'create_payout,'
                                 . 'create_payout_batch,'
                                 . 'approve_payout,'
                                 . 'approve_payout_bulk,'
                                 . 'user_auth',
        Entity::TOKEN         => 'sometimes|filled',

        // Applicable to select actions: Need to send these payloads for raven's sms content.
        'amount'              => 'required_if:action,create_payout,approve_payout|integer|min:100',
        'account_number'      => 'required_if:action,create_payout,create_payout_batch,approve_payout,approve_payout_bulk|alpha_num|between:5,22',
        'fund_account_id'     => 'required_if:action,create_payout|public_id|size:17',
        'purpose'             => 'required_if:action,create_payout|string|max:30|alpha_dash_space',
        'payout_id'           => 'required_if:action,approve_payout|public_id|size:19',
        'payout_total_amount' => 'required_if:action,approve_payout_bulk|integer|min:100',
        'payout_count'        => 'required_if:action,approve_payout_bulk|integer|min:1',
    ];

    protected static $sendOtpWithContactRules = [
        Entity::ACTION          => 'required|filled|in:bureau_verify',
        Entity::TOKEN           => 'sometimes|filled',
        Entity::CONTACT_MOBILE  => 'required|max:15',
        Entity::MEDIUM          => 'sometimes|filled|in:sms',
    ];

    protected static $verifyOtpRules = [
        Entity::OTP             => 'required|filled|min:4',
        Entity::TOKEN           => 'required|unsigned_id',
        Entity::ACTION          => 'sometimes|filled|in:bureau_verify',
        Entity::CONTACT_MOBILE  => 'required_if:action,bureau_verify|max:15',
    ];

    protected static $teamManagementValidators = [
        'self_user',
        'team_user',
    ];

    protected static $createValidators = [
        'captcha'
    ];

    protected static $changePasswordValidators = [
        'old_password'
    ];

    protected static $verifyUserThroughEmailRules = [
        Entity::OTP             => 'required|filled|min:4',
        Entity::TOKEN           => 'required|unsigned_id',
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
        if ((Role::exists($role) === false) and
            (BankingRole::exists($role) === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_ROLE_INVALID,
                Entity::ROLE,
                [Entity::ROLE => $role]);
        }
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
        if ((isset($input[Entity::CAPTCHA_DISABLE])) and
            ($input[Entity::CAPTCHA_DISABLE] === self::DISABLE_CAPTCHA_SECRET))
        {
            return;
        }

        $app = App::getFacadeRoot();

        $emailData['email'] = $input[Entity::EMAIL];

        if($app->environment('production') === true)
        {
            $captchaResponse = $input[Entity::CAPTCHA] ?? null;

            $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'];

            $noCaptchaSecret = config('app.signup.nocaptcha_secret');

            $input = [
                'secret'   => $noCaptchaSecret,
                'response' => $captchaResponse,
                'remoteip' => $clientIpAddress,
            ];

            $captchaQuery = http_build_query($input);

            $url = 'https://www.google.com/recaptcha/api/siteverify?'. $captchaQuery;

            $response = \Requests::get($url);

            $output = json_decode($response->body);

            if($output->success !== true)
            {
                $app['diag']->trackOnboardingEvent(EventCode::SIGNUP_CAPTCH_VERIFICATION_FAILED, null, null, $emailData);

                throw new BadRequestException(ErrorCode::BAD_REQUEST_CAPTCHA_FAILED);
            }
        }

        $app['diag']->trackOnboardingEvent(EventCode::SIGNUP_CAPTCHA_VERIFICATION_SUCCESS, null, null, $emailData);
    }

    protected function validateAction(string $attribute, string $action)
    {
        if (Action::exists($action) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED);
        }
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

        if (($action === 'verify_contact') and
            ($medium !== 'sms'))
        {
            throw new BadRequestValidationFailureException('Sms must be the medium for verifying contact');
        }

        if (($action === 'verify_contact') and
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
            ($action !== 'verify_contact') and
            ($user->isContactMobileVerified() === false))
        {
            throw new BadRequestValidationFailureException('Contact mobile is not verified');
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

    public function validateInputForProductBanking($input)
    {
        if (empty($input[Entity::OTP_AUTH_TOKEN]) === true)
        {
            throw new BadRequestValidationFailureException('User authorization token needs to be given.');
        }
    }
}
