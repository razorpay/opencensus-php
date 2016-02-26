<?php

use Http\ApiResponse;
use Models\User;
use Models\User\Account;

class UserController extends BaseController
{
    public function createUser()
    {
        $input = Input::all();

        $data = (new User\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function updateUser($id)
    {
        $input = Input::all();

        $data = (new User\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function getUser($id)
    {
        $data = (new User\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function deleteUser($id)
    {
        $data = (new User\Service)->delete($id);

        return ApiResponse::json($data);
    }

    public function addMethod($id)
    {
        $input = Input::all();

        $data = (new User\Methods\Service)->add($id, $input);

        return ApiResponse::json($data);
    }

    public function updateMethod($uid, $mid)
    {
        $input = Input::all();

        $data = (new User\Methods\Service)->edit($uid, $mid, $input);

        return ApiResponse::json($data);
    }

    public function deleteMethod($uid, $mid)
    {
        $data = (new User\Methods\Service)->delete($uid, $mid);

        return ApiResponse::json($data);
    }

    public function fetchMethod($uid, $mid)
    {
        $data = (new User\Methods\Service)->fetch($uid, $mid);

        return ApiResponse::json($data);
    }

    public function fetchMethods($uid)
    {
        $data = (new User\Methods\Service)->fetchMultiple($uid);

        return ApiResponse::json($data);
    }
}