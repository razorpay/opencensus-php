<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;
use Symfony\Component\HttpFoundation\Request;

class EncryptCookies extends BaseEncrypter
{
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
