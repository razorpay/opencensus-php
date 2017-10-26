<?php

namespace RZP\Models\User;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    const DISABLE_CAPTCHA_SECRET = 'DISABLE_THE_CAPTCHA_YOU_SHALL';

    protected static $createRules = [
        Entity::ID                    => 'sometimes|max:14',
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::EMAIL                 => 'required|email|unique:users,email',
        Entity::PASSWORD              => 'required|between:7,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:7,50',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
        Entity::REMEMBER_TOKEN        => 'sometimes',
        Entity::CONFIRM_TOKEN         => 'sometimes',
        Entity::CAPTCHA               => 'required_without:captcha_disable',
        Entity::CAPTCHA_DISABLE       => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::EMAIL                 => 'sometimes|email|unique:users,email',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
    ];

    protected static $changePasswordRules = [
        Entity::PASSWORD              => 'required|between:7,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:7,50',
        Entity::OLD_PASSWORD          => 'sometimes|string',
    ];

    protected static $actionRules = [
        Entity::ACTION                => 'required|custom',
        Entity::MERCHANT_ID           => 'required|max:14',
        Entity::ROLE                  => 'sometimes|string|in:owner,manager,operations,finance,support,admin,sellerapp',
    ];

    protected static $loginRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
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
        Entity::PASSWORD              => 'required|between:7,50|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|between:7,50',
        Entity::TOKEN                 => 'required|string',
        Entity::EXPIRY_TIME           => 'required',
    ];

    protected static $teamManagementValidators = [
        'self_user',
        'team_user',
    ];

    protected static $createValidators = [
        'captcha'
    ];

    /**
     * merchant can not edit or delete his own user id.
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function validateSelfUser(array $input)
    {
        $app = App::getFacadeRoot();

        $dashboardHeaders = $app['basicauth']->getDashboardHeaders();

        $dashboardUserId = $dashboardHeaders['user_id'];

        if ($input['user_id'] === $dashboardUserId)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_SELF_USER);
        }
    }

    /**
     * This function handles https://www.owasp.org/index.php/Top_10_2013-A4-Insecure_Direct_Object_References
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function validateTeamUser(array $input)
    {
        $user = (new Merchant\Repository)->getMerchantUserMapping($input['merchant_id'], $input['user_id']);

        if (empty($user) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHNAT);
        }
    }

    /**
     * Google captcha validation.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function validateCaptcha(array $input)
    {
        if ((isset($input[Entity::CAPTCHA_DISABLE])) and
            ($input[Entity::CAPTCHA_DISABLE] === self::DISABLE_CAPTCHA_SECRET))
        {
            return;
        }

        $app = App::getFacadeRoot();

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

            $url = "https://www.google.com/recaptcha/api/siteverify?". $captchaQuery;

            $response = \Requests::get($url);

            $output = json_decode($response->body);

            if($output->success !== true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CAPTCHA_FAILED);
            }
        }
    }

    protected function validateAction(string $attribute, string $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED);
        }
    }
}
