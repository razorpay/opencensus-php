<?php

namespace App\Http\Middleware;

use App\Trace\TraceCode;
use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptCookies extends BaseEncrypter
{
    protected $except = [
        'rzp_merchant_id',
        'rzp_user_id',
    ];

    protected function decrypt(Request $request)
    {
        foreach ($request->cookies as $key => $c) {

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

        return $request;
    }
}
