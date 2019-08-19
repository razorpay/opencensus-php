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

    /**
     * Edit user action for logged in user (via Dashboard headers).
     * @return \Illuminate\Http\Response
     */
    public function editSelf()
    {
        $response = $this->service()->editSelf($this->input);

        return ApiResponse::json($response);
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

    public function changeUserPassword()
    {
        $input = Request::all();

        $data = $this->service()->changePassword($input);

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

    public function setup2faMobileOnLogin()
    {
        $input = Request::all();

        $data = $this->service()->setup2faMobileOnLogin($input);

        return ApiResponse::json($data);
    }

    public function setup2faVerifyMobileOnLogin()
    {
        $input = Request::all();

        $data = $this->service()->setup2faVerifyMobileOnLogin($input);

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

    public function postResetPasswordByEmail()
    {
        $input = Request::all();

        $data = $this->service()->postResetPassword($input);

        return ApiResponse::json($data);
    }

    public function postChangePasswordByToken()
    {
        $input = Request::all();

        $data = $this->service()->changePasswordByToken($input);

        return ApiResponse::json($data);
    }

    public function sendOtp()
    {
        $response = $this->service()->sendOtp($this->input);

        return ApiResponse::json($response);
    }

    public function verifyContactWithOtp()
    {
        $response = $this->service()->verifyContactWithOtp($this->input);

        return ApiResponse::json($response);
    }

    public function resetUserPassword(string $id)
    {
        $input = Request::all();

        $response = $this->service()->resetUserPassword($id, $input);

        return ApiResponse::json($response);
    }

    public function change2faSetting()
    {
        $input = Request::all();

        $response = $this->service()->change2faSetting($input);

        return ApiResponse::json($response);
    }

    /**
     *  User is updating his/her own contact Mobile
     * @return mixed
     */
    public function editContactMobile()
    {
        $input = Request::all();

        $data = $this->service()->editContactMobile($input);

        return ApiResponse::json($data);
    }
}
