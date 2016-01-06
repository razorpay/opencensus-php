<?php

use Http\AppResponse;
use Models\Merchant;
use Models\MerchantDetails;
use Razorpay\Mailers\ContactFormMailer;

class MerchantController extends BaseController
{
    /**
     * Get the current merchant of the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getMerchant()
    {
       $user = Auth::user()->user();
   
       $merchant = (new Merchant\Service)->fetchCurrentMerchantForUser($user);
    
       $merchantDetails = (new MerchantDetails\Service)->fetchDetails();

       $data = $merchant + $merchantDetails;

       return AppResponse::jsonResponse([], $data);
    }

    public function postRegister()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->register($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postResendConfirmation()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->resendConfirmation($input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Update a team member on the given merchant.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\Response
     */
    public function updateTeamMember($userId)
    {
        $input = Input::all();

        $user = Auth::user()->user();

        list($error, $data) = (new Merchant\Service)->updateTeamMemberForOwner($userId, $user, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Remove the team member on the given merchant.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\Response
     */
    public function removeTeamMember($userId)
    {
        $input = Input::all();

        $user = Auth::user()->user();

        $error = (new Merchant\Service)->removeTeamMemberForOwner($userId, $user, $input);

        return AppResponse::jsonResponse($error);
    }

    public function getCsv()
    {
        $input = Input::only('id', 'secret');

        if ((isset($input['id']) === false) or
            (isset($input['secret']) === false))
        {
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rzp.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, array('key_id', 'key_secret'));
        fputcsv($output, array($input['id'], $input['secret']));
    }

    public function getApihost()
    {
        return AppResponse::jsonResponse([], $_ENV['API_URL']);
    }

    public function getKeys($mode)
    {
        $merchant = Auth::user()->user()->currentMerchant;

        $keys = (new Merchant\Service)->fetchKeysFromApi($merchant->id, $mode);

        return AppResponse::jsonResponse([], $keys);
    }

    public function postNewKey($mode)
    {
        $merchant = Auth::user()->user()->currentMerchant;

        list($error, $data) = (new Merchant\Service)->createKey($merchant->id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postKeys($mode)
    {
        $input = Input::all();

        $input['merchant_id'] = Auth::user()->user()->getCurrentMerchantId();

        list($error, $data) = (new Merchant\Service)->rollKeys($input, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getConfirm($token)
    {
        $error = (new Merchant\Service)->confirm($token);

        return AppResponse::jsonResponse($error);
    }

    public function getActivationDetails()
    {
        $response = (new MerchantDetails\Service)->fetchDetails();

        return AppResponse::jsonResponse([], $response);
    }

    public function postActivation()
    {
        $error = (new MerchantDetails\Service)->submitDetails();

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationStep($id)
    {
        $input = Input::all();

        if ((int) $id !== 5)
        {
            $error = (new MerchantDetails\Service)->saveDetails($id, $input);
        }
        else
        {
            $error = (new MerchantDetails\Service)->checkUploads();
        }

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationFile()
    {
        $input = Input::all();

        $error = (new MerchantDetails\Service)->saveUploadedFile($input);

        return AppResponse::jsonResponse($error);
    }

    public function optionsContact()
    {
        $response = AppResponse::jsonResponse([]);
        $response->header('Access-Control-Allow-Origin', 'https://razorpay.com');

        return $response;
    }

    public function postContact()
    {
        $input = Input::all();

        // @todo: Shift this validation away from here
        if ((isset($input['email']) === false) or
            (isset($input['name']) === false) or
            (filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) or
            (is_string($input['name']) === false))
        {
            $error[] = 'Please specify both name and email and in correct format';

            return AppResponse::jsonResponse($error);
        }

        (new ContactFormMailer)->with($input)->contact()->queue()->deliver();

        $response = AppResponse::jsonResponse([]);
        $response->header('Access-Control-Allow-Origin', 'https://razorpay.com');

        return $response;
    }

    public function getWebhooks($mode)
    {
        list($error, $data) = (new Merchant\Service)->getWebhooks($mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postAddWebhook($mode)
    {
        $input = Input::all();

        list($error, $data)  = (new Merchant\Service)->createWebhook($mode, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function putEditWebhook($mode, $id)
    {
        $input = Input::all();

        list($error, $data)  = (new Merchant\Service)
            ->editWebhook($mode, $id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Fetches Merchant Balance
     * @return array array containing both balances
     */
    public function getBalance($mode)
    {
        $this->checkMode($mode);

        $id = Auth::user()->user()->getCurrentMerchantId();

        $data = (new Merchant\Service)->fetchMerchantBalance($id);

        return AppResponse::jsonResponse([], $data[$mode]['balance']);
    }
}
