<?php

namespace RZP\Http\Controllers;

use Config;
use ApiResponse;
use Illuminate\Http\Request as Request;
use Illuminate\Http\Response as BaseResponse;

class MockLumberjackController extends Controller
{
    const X_IDENTIFIER = 'x-identifier';

    const X_SIGNATURE = 'x-signature';

    public function mockEventTrack(Request $request)
    {
        $identifier = $request->header(self::X_IDENTIFIER);

        $signature = $request->header(self::X_SIGNATURE);

        $secret = $this->app['config']->get('applications.lumberjack.secret');

        $key = $request->get('key', null);

        $calcSign = base64_encode(hash_hmac('sha256', $key, $secret, true));

        if ($signature !== $calcSign)
        {
            return ApiResponse::json(
                ['success' => false],
                BaseResponse::HTTP_UNAUTHORIZED
            );
        }

        return ApiResponse::json(
            ['success' => true]
        );
    }
}
