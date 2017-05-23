<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\User;

class UserController extends Controller
{
    public function createUser()
    {
        $input = Request::all();

        $data = (new User\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function editUser(string $id)
    {
        $input = Request::all();

        $data = (new User\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function confirmUser(string $id)
    {
        $data = (new User\Service)->confirm($id);

        return ApiResponse::json($data);
    }

    public function confirmUserByData()
    {
        $input = Request::all();

        $data = (new User\Service)->confirmUserByData($input);

        return ApiResponse::json($data);
    }

    public function changeUserPassword(string $id)
    {
        $input = Request::all();

        $data = (new User\Service)->changePassword($id, $input);

        return ApiResponse::json($data);
    }

    public function updateUserMaping(string $id, string $action)
    {
        $input = Request::all();

        $input['action'] = $action;

        $data = (new User\Service)->updateUserMerchantMapping($id, $input);

        return ApiResponse::json($data);
    }

    public function loginUser()
    {
        $input = Request::all();

        $data = (new User\Service)->login($input);

        return ApiResponse::json($data);
    }

    public function getUser(string $id)
    {
        $data = (new User\Service)->get($id);

        return ApiResponse::json($data);
    }
}
