<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\User;

class UserController extends Controller
{
    public function postUser()
    {
        $input = Request::all();

        $data = (new User\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function putUser(string $id)
    {
    	$input = Request::all();

        $data = (new User\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function actionOnUserMerchantMapping(string $id, string $action)
    {
        $input = Request::all();

        $input['action'] = $action;

        $data = (new User\Service)->mappingAction($id, $input);

        return ApiResponse::json($data);
    }

    public function confirmUser(string $id)
    {
        $data = (new User\Service)->confirm($id);

        return ApiResponse::json($data);
    }

    public function changeUserPassword(string $id)
    {
        $input = Request::all();

        $data = (new User\Service)->changePassword($id, $input);

        return ApiResponse::json($data);
    }

    public function loginUser()
    {
        $input = Request::all();

        $data = (new User\Service)->login($input);

        return ApiResponse::json($data);
    }
}
