<?php

namespace App\User;

use Request;
use Google_Client;
use App\Http\Headers;
use App\Trace\TraceCode;

class OauthHelper
{
    /**
     * @var Application
     */
    protected $app;

    protected $trace;

    /**
     * @var Store
     */
    protected $cache;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];
    }

    /**
     * This Function verify if any oauth provider client request response coming from frontend is correct or not
     * and set corresponding oauth provider after verification,
     *
     * For new oauth provider add another case and corresponding verification function.
     *
     * @param array $input
     *
     * @return bool
     * @throws \Exception
     */
    public function oauthProviderVerification(array &$input): bool
    {
        $oauthProvider = $input[Constants::OAUTH_PROVIDER];

        $input[Constants::OAUTH_SOURCE] = Request::header(Headers::OAUTH_SOURCE) ?? Constants::DASHBOARD;

        switch ($oauthProvider)
        {
            case Constants::OAUTH_PROVIDER_GOOGLE:
                return $this->verifyGoogleIdToken($input);

            default:
                return false;
        }
    }

    protected function getOauthClientIdFromSource(string $oauthSource)
    {
        switch ($oauthSource)
        {
            case Constants::IOS:
                return $this->getMerchantOauthClientIdIos();

            case Constants::ANDROID:
                return $this->getMerchantOauthClientIdAndroid();

            case Constants::EPOS:
                return $this->getMerchantOauthClientIdEpos();

            default:
                //
                // default is dashboard.
                //
                return $this->getMerchantOauthClientId();
        }
    }

    /**
     * verify Google Id_token using google oauth client Id
     * which make sure any data coming from FE is not by spoofing
     *
     * @param array $input
     *
     * @return bool
     * @throws \Exception
     */
    protected function verifyGoogleIdToken(array &$input): bool
    {
        $clientId = $this->getOauthClientIdFromSource($input[Constants::OAUTH_SOURCE]);

        // Specify the CLIENT_ID of the app that accesses the backend
        $client = new Google_Client([Constants::CLIENT_ID => $clientId]);

        $idToken = $input[Constants::ID_TOKEN];

        $payload = $client->verifyIdToken($idToken);

        // Lower casing emails for consistency
        $input[Constants::EMAIL] = mb_strtolower($input[Constants::EMAIL]);

        if (((strcmp(strtolower($payload[Constants::EMAIL]), strtolower($input[Constants::EMAIL])) === 0) === true) and
            ($payload[Constants::EMAIL_VERIFIED] === true))
        {
            $input[Constants::OAUTH_PROVIDER] = json_encode(array(Constants::OAUTH_PROVIDER_GOOGLE));

            return true;
        }
        else
        {
            $this->trace->info(TraceCode::GOOGLE_OAUTH_SIGN_IN_FAILURE,
                               [
                                   Constants::EMAIL   => $input[Constants::EMAIL],
                                   Constants::PAYLOAD => $payload
                               ]);

            return false;
        }
    }

    /**
     * @return Application|mixed
     */
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
}
