<?php

namespace RZP\Http\Controllers;

use Config;
use Request;
use Response;
use Illuminate\Http\Response as BaseResponse;

class MockLumberjackController extends Controller
{

    const X_IDENTIFIER = 'x-identifier';

    const X_SIGNATURE = 'x-signature';

    public function __construct()
    {
        parent::__construct();
    }

    public function mockEventTrack(Request $request)
    {
        $identifier = null;

        $signature = null;

        if ($request->hasHeader(self::X_IDENTIFIER) === true)
        {
            $identifier = $request->header(self::X_IDENTIFIER);
        }

        if ($request->hasHeader(self::X_SIGNATURE) === true)
        {
            $signature = $request->header('x-signature');
        }

        $secret = $this->app['config']->get('applications.lumberjack')['secret'];

        $key = $request->get('key', null);

        $calcSign = hash_hmac('sha1', $key, $secret);

        if ($signature !== $calcSign)
        {
            return Response::json(
                ['success' => false],
                BaseResponse::HTTP_UNAUTHORIZED
            );
        }

        return Response::json(
                ['success' => true]
            );
    }
}
