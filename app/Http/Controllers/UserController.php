<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class UserController extends Controller
{
    public function createUser()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function editUser(string $id)
    {
        $input = Request::all();

        $data = $this->service()->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function confirmUser(string $id)
    {
        $data = $this->service()->confirm($id);

        return ApiResponse::json($data);
    }

    public function confirmUserByData()
    {
        $input = Request::all();

        $data = $this->service()->confirmUserByData($input);

        return ApiResponse::json($data);
    }

    public function changeUserPassword(string $id)
    {
        $input = Request::all();

        $data = $this->service()->changePassword($id, $input);

        return ApiResponse::json($data);
    }

    public function updateUserMaping(string $id, string $action)
    {
        $input = Request::all();

        $input['action'] = $action;

        $data = $this->service()->updateUserMerchantMapping($id, $input);

        return ApiResponse::json($data);
    }

    public function loginUser()
    {
        $input = Request::all();

        $data = $this->service()->login($input);

        return ApiResponse::json($data);
    }

    public function getUser(string $id)
    {
        $data = $this->service()->get($id);

        return ApiResponse::json($data);
    }
}
