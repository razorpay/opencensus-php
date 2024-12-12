<?php

namespace App\Http\Middleware;

use App\Constants\Constants;
use App\Trace\TraceCode;
use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptCookies extends BaseEncrypter
{
    /*
     * Adding user session cookie in except array as cookie encryption is breaking in graphql api's for laravel 6 and above
     *
     * Csrf mismatch in case of Graphql Request flow for encryption-decryption:

     * Request(graph org) -> decryption(graph org) -> Request(/org) -> decryption(/org) -> encryption(/org) ->
     *  encryption(graph org) -> request(graph login) -> decryption(graph login) -> Request(/user/login) ->
     *  decryption(/user/login) -> encryption(/user/login) ->  encryption(graph login).
     *
     * Marking rzp_usr_session cookie disabled until alternative is found.
     * */
    protected $except = [
        'rzp_merchant_id',
        'rzp_user_id',
        'rzp_ab_uuid',
        'rzp_usr_session',
        Constants::RZP_ACCESS_TOKEN, // received from Edge
        Constants::RZP_REFRESH_TOKEN,
        Constants::RZP_USER_MERCHANT_REGION,
        Constants::ADMIN_EXPERIENCE_SESSION,
    ];


    /**
     * Decrypt the cookies on the request.
     *
     * @param Request $request
     *
     * @return Request
     */
    protected function decrypt(Request $request)
    {
        foreach ($request->cookies as $key => $cookie) {
            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                $value = $this->decryptCookie($key, $cookie);

                $request->cookies->set($key, $this->validateValue($key, $value));
            } catch (DecryptException $e) {
                $request->cookies->set($key, null);
            } catch (\Throwable $e) {
                $request->cookies->remove($key);
            }
        }

        return $request;
    }
}
