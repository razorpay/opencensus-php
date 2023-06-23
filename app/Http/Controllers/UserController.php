<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\User\Entity;
use RZP\Models\User\Service;

class UserController extends Controller
{
    public function registerUser()
    {
        $input = Request::all();

        /** @var Service $userService */
        $userService = $this->service();
        $data = $userService->register($input);

        return ApiResponse::json($data);
    }

    public function changeBankingUserRole() {
        $input = Request::all();

        /** @var Service $userService */
        $userService = $this->service();
        $data = $userService->changeBankingUserRole($input);

        return ApiResponse::json($data);
    }

    public function registerUserWithOtp()
    {
        $input = Request::all();

        /** @var Service $userService */
        $userService = $this->service();
        $data = $userService->registerWithOtp($input);

        return ApiResponse::json($data);
    }

    public function sendOtpSalesforceUser()
    {
        $input = Request::all();
        $data = $this->service()->sendOtpSalesforce($input);
        return ApiResponse::json($data);
    }

    public function verifyOtpSalesforceUser()
    {
        $input = Request::all();
        $data = $this->service()->verifyOtpSalesforce($input);
        return ApiResponse::json($data);
    }

    public function verifySignupOtpAndRegisterUser()
    {
        $input = Request::all();

        /** @var Service $userService */
        $userService = $this->service();
        $data = $userService->verifySignupOtp($input);

        return ApiResponse::json($data);
    }

    public function createUser()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function oAuthSignup()
    {
        $input = Request::all();

        $data = $this->service()->oAuthSignup($input);

        return ApiResponse::json($data);
    }

    public function oAuthLogin()
    {
        $input = Request::all();

        $data = $this->service()->oAuthLogin($input);

        return ApiResponse::json($data);
    }

    public function editUser(string $id)
    {
        $input = Request::all();

        $data = $this->service()->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function removeIncorrectPasswordCount()
    {
        $input = Request::all();

        return $this->service()->removeIncorrectPasswordCount($input);
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

    public function getCheckUserHasSetPassword()
    {
        $data = $this->service()->checkUserHasSetPassword();

        return ApiResponse::json($data);
    }

    public function postPatchUserPassword()
    {
        $input = Request::all();

        $data = $this->service()->patchUserPassword($input);

        return ApiResponse::json($data);
    }

    public function sendUserDetailsToSalesForceEvent()
    {
        $input = Request::all();

        $data = $this->service()->sendUserDetailsToSalesForceEvent($input);

        return ApiResponse::json($data);
    }

    public function postSetUserPassword()
    {
        $input = Request::all();

        $data = $this->service()->setUserPassword($input);

        return ApiResponse::json($data);
    }

    public function updateUserMaping(string $id, string $action)
    {
        $input = Request::all();

        $input['action'] = $action;

        $data = $this->service()->updateMerchantManageTeam($id, $input);

        return ApiResponse::json($data);
    }

    public function bulkUpdateUserMapping()
    {
        $data = $this->service()->bulkUpdateUserMapping($this->input);

        return ApiResponse::json($data);
    }

    public function loginUser()
    {
        $input = Request::all();

        $data = $this->service()->login($input);

        return ApiResponse::json($data);
    }

    public function loginUserWithOtp()
    {
        $input = Request::all();

        $data = $this->service()->loginWithOtp($input);

        return ApiResponse::json($data);
    }

    public function verifyLoginOtp()
    {
        $input = Request::all();

        $data = $this->service()->verifyLoginOtp($input);

        return ApiResponse::json($data);
    }

    public function loginOtp2faPassword()
    {
        $input = Request::all();

        $data = $this->service()->loginOtp2faPassword($input);

        return ApiResponse::json($data);
    }


    public function sendVerificationOtp()
    {
        $input = Request::all();

        $data = $this->service()->sendVerificationOtp($input);

        return ApiResponse::json($data);
    }

    public function verifyVerificationOtp()
    {
        $input = Request::all();

        $data = $this->service()->verifyVerificationOtp($input);

        return ApiResponse::json($data);
    }

    public function checkUserAccess()
    {
        $input = Request::all();

        $data = $this->service()->checkUserAccess($input);

        return ApiResponse::json($data);
    }

    public function setup2faContactMobile()
    {
        $input = Request::all();

        $data = $this->service()->setup2faContactMobile($input);

        return ApiResponse::json($data);
    }

    public function resendOtp()
    {
        $data = $this->service()->resendOtp();

        return ApiResponse::json($data);
    }

    public function send2FaOtp()
    {
        $data = $this->service()->send2faOtp();

        return ApiResponse::json($data);
    }

    public function setup2faVerifyMobileOnLogin()
    {
        $input = Request::all();

        $data = $this->service()->setup2faVerifyMobileOnLogin($input);

        return ApiResponse::json($data);
    }

    public function verifyUserSecondFactorAuth()
    {
        $input = Request::all();

        $data = $this->service()->verifyUserSecondFactorAuth($input);

        return ApiResponse::json($data);
    }

    public function verifyOtpAndUpdateContactMobile()
    {
        $input = Request::all();

        $data = $this->service()->verifyOtpAndUpdateContactMobile($input);

        return ApiResponse::json($data);
    }

    public function getUser(string $id)
    {
        $input = Request::all();

        $data = $this->service()->get($id, $input);

        return ApiResponse::json($data);
    }

    public function getActorInfo(string $id)
    {
        $data = $this->service()->getActorInfo($id);

        return ApiResponse::json($data);
    }

    public function getUserEntity(string $id)
    {
        $data = $this->service()->getUserEntity($id);

        return ApiResponse::json($data);
    }

    public function getUserByVerifiedContact()
    {
        $input = Request::all();

        $data = $this->service()->getUserByVerifiedContact($input);

        return ApiResponse::json($data);
    }

    public function postUpgradeUserToMerchant()
    {
        $input = Request::all();

        /** @var Service $service */
        $service = $this->service();
        $data = $service->upgradeUserToMerchant($input);

        return ApiResponse::json($data);
    }

    public function postResendVerificationMail()
    {
        $data = $this->service()->resendVerificationMail();

        return $data;
    }

    public function postResendVerificationOtp()
    {
        $input = Request::all();

        $data = $this->service()->resendVerificationOtp($input);

        return $data;
    }

    public function postResetPassword()
    {
        $input = Request::all();

        $data = $this->service()->postResetPassword($input);

        return ApiResponse::json($data);
    }

    public function postResetPasswordByEmailForCoCreated()
    {
        $input = Request::all();

        $data = $this->service()->sendResetPasswordSegmentEventAdmin($input);

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
        /** @var Service $userService */
        $userService = $this->service();
        $response = $userService->sendOtp($this->input);

        return ApiResponse::json($response);
    }

    public function mobileOauthLogout()
    {
        $input = Request::all();

        $data = $this->service()->mobileOauthLogout($input);

        return ApiResponse::json($data);
    }

    public function sendOtpWithContact()
    {
        $response = $this->service()->sendOtpWithContact($this->input);

        return ApiResponse::json($response);
    }

    public function verifyOtpWithToken()
    {
        $response = $this->service()->verifyOtpWithToken($this->input);

        return ApiResponse::json($response);
    }

    public function verifyContactWithOtp()
    {
        $response = $this->service()->verifyContactWithOtp($this->input);

        return ApiResponse::json($response);
    }

    public function switchMerchantWithToken()
    {
        $response = $this->service()->switchMerchantWithToken($this->input);

        return ApiResponse::json($response);
    }

    public function mobileOauthRefreshToken()
    {
        $response = $this->service()->mobileOauthRefreshToken($this->input);

        return ApiResponse::json($response);
    }

    public function verifyEmailWithOtp()
    {
        $response = $this->service()->verifyEmailWithOtp($this->input);

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

    public function fetchMerchantIdsForUserContact(string $contact)
    {
        $response = $this->service()->fetchMerchantIdsForUserContact($contact);

        return ApiResponse::json($response);
    }

    public function fetchPrimaryUserContact(string $merchantId)
    {
        $response = $this->service()->fetchPrimaryUserContact($merchantId);

        return ApiResponse::json($response);
    }

    /**
     *  User is sending otp to update his/her own contact Mobile
     * @return mixed
     */
    public function postSendOtpForContactMobileUpdate()
    {
        $input = Request::all();

        $data = $this->service()->sendOtpForContactMobileUpdate($input);

        return ApiResponse::json($data);
    }

    /**
     * Update contact mobile of a user using userId
     * by Restricted Merchant or Admin
     * @return mixed
     */
    public function updateContactMobile()
    {
        $input = Request::all();

        $data = $this->service()->updateContactMobile($input);

        return ApiResponse::json($data);
    }

    /**
     * Lock/Unlock action for user account
     *
     * @param string $id
     * @param string $action
     *
     * @return mixed
     */
    public function accountLockUnlock(string $id, string $action)
    {
        $input = Request::all();

        $data = $this->service()->accountLockUnlock($id, $action);

        return ApiResponse::json($data);
    }

    public function verifyUserThroughMode(string $medium)
    {
        $input = Request::all();

        $input[Entity::MEDIUM] = $medium;

        $data = $this->service()->verifyUserThroughMode($input);

        return ApiResponse::json($data);
    }

    public function getUserForMerchant(string $userId)
    {
        $data = $this->service()->getUserForMerchant($userId);

        return ApiResponse::json($data);
    }

    public function optInForWhatsapp()
    {
        $input = Request::all();

        return $this->service()->optInForWhatsapp($input);
    }

    public function optOutForWhatsapp()
    {
        $input = Request::all();

        return $this->service()->optOutForWhatsapp($input);
    }

    public function optInStatusForWhatsapp()
    {
        $input = Request::all();

        return $this->service()->optInStatusForWhatsapp($input);
    }

    public function getUserDetails()
    {
        $input = Request::all();

        return $this->service()->getDetails($input);
    }

    public function getInternationalUserDetails()
    {
        $input = Request::all();

        return $this->service()->getInternationalDetails($input);
    }

    public function getUserDetailsUnified()
    {
        $input = Request::all();

        return $this->service()->getDetailsUnified($input);
    }

    public function getUserRoles($Id, $merchantId)
    {
        return $this->service()->getUserRoles($Id, $merchantId);
    }

    public function sendXMobileAppDownloadLinkSms()
    {
        $input = Request::all();

        /** @var Service $userService */
        $userService = $this->service();

        $response = $userService->sendXMobileAppDownloadLinkSms($input);

        return ApiResponse::json($response);
    }

    public function postSaveDeviceDetails()
    {
        $input = Request::all();

        $response = $this->service()->saveDeviceDetails($input);

        return ApiResponse::json($response);
    }

    public function verifyContactMobile()
    {
        $input = Request::all();

        $data = $this->service()->verifyContactMobile($input);

        return ApiResponse::json($data);
    }

    public function sendOtpForAddEmail()
    {
        $input = Request::all();

        /** @var Service $userService */
        $userService = $this->service();
        $response = $userService->sendOtpForAddEmail($input);

        return ApiResponse::json($response);
    }

    public function verifyOtpForAddEmail()
    {
        $input = Request::all();

        /** @var Service $userService */
        $userService = $this->service();
        $response = $userService->verifyOtpForAddEmail($input);

        return ApiResponse::json($response);
    }

    public function updateContactNumberForSubMerchantUser()
    {
        $header = Request::header('khatabook-use-case');

        if($header != 'true')
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $input = Request::all();

        $data = $this->service()->updateContactNumberForSubMerchantUser($input['input']);

        return ApiResponse::json($data);
    }

    /**
     * Change the user's username.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the result of the username change operation.
    */
    public function postUpdateUserName() 
    {
        $input = Request::all();
        
        $response = $this->service()->postUpdateUserName($input);

        return APIResponse::json($response);
    }
}
