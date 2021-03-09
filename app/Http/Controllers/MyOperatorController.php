<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class MyOperatorController extends Controller
{

    const GET_USERS_PATH                      = 'user';
    const PUSH_CALL_TO_QUEUE_PATH             = 'obd-api-v1';

    const MYOPERATOR_V1_ROUTES = [
        self::GET_USERS_PATH,
    ];

    const MYOPERATOR_V2_ROUTES = [
        self::PUSH_CALL_TO_QUEUE_PATH
    ];

    public function getProxyCallToMyOperatorV1($path)
    {
        $this->validatePathForProxyCallToMyOperator(self::MYOPERATOR_V1_ROUTES, $path);

        $response = $this->app['myoperator']->getProxyCallToMyOperatorV1($path);;

        return ApiResponse::json($response);
    }

    public function postProxyCallToMyOperatorV2(string $path): array
    {
        $this->validatePathForProxyCallToMyOperator(self::MYOPERATOR_V2_ROUTES, $path);

        $input = Request::all();

        $response = $this->app['myoperator']->postProxyCallToMyOperatorV2($path, $input);

        return $response;
    }

    protected function validatePathForProxyCallToMyOperator($validRoutesArray, $path)
    {
        if(in_array($path, $validRoutesArray) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

}
