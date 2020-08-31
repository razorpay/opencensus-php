<?php

namespace App\Http\Middleware;

use App\Trace\TraceCode;
use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptCookies extends BaseEncrypter
{
    protected function decrypt(Request $request)
    {
        app('trace')->info(TraceCode::USER_COOKIES_KEYS, $request->cookies->keys());

        $cookieSize = [];

        foreach ($request->cookies as $key => $c) {

            $cookieSize[$key] = mb_strlen(serialize((array)$c), '8bit');

            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                $request->cookies->set($key, $this->decryptCookie($c));
            } catch (DecryptException $e)  {
                $request->cookies->set($key, null);
            } catch (\Throwable $e) {
                $request->cookies->remove($key);
            }
        }

        app('trace')->info(TraceCode::USER_COOKIES_KEYS, $cookieSize);

        return $request;
    }
}
