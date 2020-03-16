<?php

namespace RZP\Models\Typeform;

use Config;
use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Illuminate\Http\Request as HttpRequest;

class Auth
{

    protected const SIGNATURE_PREFIX = "sha256=";

    protected const TYPEFORM_ENCRYPTION_ALGO = "sha256";

    /**
     * @param HttpRequest $request
     *
     * @return |null
     */
    public static function authenticateTypeformWebhook(HttpRequest $request)
    {
        $actualSignature = $request->headers->get(RequestHeader::TYPEFORM_SIGNATURE);

        if (empty($actualSignature) === true or
            (hash_equals($actualSignature, Auth::fetchExpectedSignature($request)) === false))
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        return null;
    }

    /**
     * @param HttpRequest $request
     *
     * @return string
     */
    private static function fetchExpectedSignature(HttpRequest $request)
    {
        $expectedSecret = Config::get('applications.typeform.typeform_webhook_secret');

        $hashedSecret = hash_hmac(self::TYPEFORM_ENCRYPTION_ALGO, $request->getContent(), $expectedSecret, true);

        $expectedSignature = self::SIGNATURE_PREFIX . base64_encode($hashedSecret);

        return $expectedSignature;
    }
}
