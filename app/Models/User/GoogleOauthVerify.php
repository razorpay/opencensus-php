<?php

namespace RZP\Models\User;

use Google_Client;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class GoogleOauthVerify
{
    public function __construct()
    {
        $this->trace = app('trace');
    }

    public function verifyGoogleOauthIdToken(array $input): bool
    {
        $clientId = $this->getOauthClientIdFromSource($input);
        $payload  = [];

        try {
            // Specify the CLIENT_ID of the app that accesses the backend
            $client = new Google_Client([Constants::CLIENT_ID => $clientId]);

            $idToken = $input[Constants::ID_TOKEN];

            if (config(Constants::OAUTH_MERCHANT_OAUTH_MOCK) === true)
            {
                return true;
            }

            $payload = $client->verifyIdToken($idToken);

            // if the id token passed is invalid
            if ($payload === false)
            {
                $this->trace->error(TraceCode::GOOGLE_OAUTH_INVALID_ID_TOKEN, ['email' => $input[Entity::EMAIL]]);

                return false;
            }

            // Lower casing emails for consistency
            $input[Entity::EMAIL] = mb_strtolower($input[Entity::EMAIL]);

            if ((strcmp(strtolower($payload[Entity::EMAIL]), $input[Entity::EMAIL]) === 0) and
                ($payload[Constants::EMAIL_VERIFIED] === true))
            {
                $this->trace->info(TraceCode::GOOGLE_OAUTH_ID_TOKEN_VERIFY_SUCCESS, ['email' => $input[Entity::EMAIL]]);

                return true;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e, Trace::ERROR, TraceCode::GOOGLE_OAUTH_ID_TOKEN_VERIFY_FAILURE,
                [
                    Entity::EMAIL      => $input[Entity::EMAIL],
                    Constants::PAYLOAD => $payload
                ]);
        }

        return false;
    }

    protected function getOauthClientIdFromSource(array $input)
    {
        $oauthSource = $input[Constants::OAUTH_SOURCE] ?? Constants::DASHBOARD;

        switch ($oauthSource)
        {
            case Constants::IOS:
                return $this->getMerchantOauthClientIdIos();

            case Constants::ANDROID:
                return $this->getMerchantOauthClientIdAndroid();

            case Constants::EPOS:
                return $this->getMerchantOauthClientIdEpos();

            case Constants::X_IOS:
                return $this->getMerchantOauthClientIdXIos();

            case Constants::X_ANDROID:
                return $this->getMerchantOauthClientIdXAndroid();

            default:
                //
                // default is dashboard.
                //
                return $this->getMerchantOauthClientId();
        }
    }

    protected function getMerchantOauthClientId()
    {
        return config(Constants::OAUTH_MERCHANT_OAUTH_CLIENT_ID);
    }

    protected function getMerchantOauthClientIdEpos()
    {
        return config(Constants::OAUTH_MERCHANT_OAUTH_CLIENT_ID_EPOS);
    }

    protected function getMerchantOauthClientIdAndroid()
    {
        return config(Constants::OAUTH_MERCHANT_OAUTH_CLIENT_ID_ANDROID);
    }

    protected function getMerchantOauthClientIdIos()
    {
        return config(Constants::OAUTH_MERCHANT_OAUTH_CLIENT_ID_IOS);
    }

    protected function getMerchantOauthClientIdXAndroid()
    {
        return config(Constants::OAUTH_MERCHANT_OAUTH_CLIENT_ID_X_ANDROID);
    }

    protected function getMerchantOauthClientIdXIos()
    {
        return config(Constants::OAUTH_MERCHANT_OAUTH_CLIENT_ID_X_IOS);
    }
}
