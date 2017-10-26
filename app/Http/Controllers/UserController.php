<?php
namespace RZP\Http\Controllers;

use Razorpay\Api\Api;
use Request;
use ApiResponse;

class UserController extends Controller
{
    public function registerUser()
    {
        $input = Request::all();

        $data = $this->service()->register($input);

        return ApiResponse::json($data);
    }

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

        $data = $this->service()->updateMerchantManageTeam($id, $input);

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

    public function postUpgradeUserToMerchant()
    {
        $input = Request::all();

        $data = $this->service()->upgradeUserToMerchant($input);

        return ApiResponse::json($data);
    }

    public function postResendVerificationMail()
    {
        $data = $this->service()->resendVerificationMail();

        return $data;
    }

    public function getUserByEmail(string $email)
    {
        $data = $this->service()->getUserByEmail($email);

        return ApiResponse::json($data);
    }
}
