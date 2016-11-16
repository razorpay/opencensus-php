<?php
namespace App\Http\Controllers;

use App\Http\AppResponse;
use App\Merchant;
use App\MerchantDetails;
use App\Mailers\ContactFormMailer;
use App\Api;
use Input;
use Auth;

class MerchantController extends Controller
{
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

        list($error, $data) = (new Merchant\Service)->updateTeamMemberForOwner($userId, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Get the user list for the currently logged in merchant
     * Only accessible to owners
     *
     * @return \Illuminate\Http\Response
     */
    public function getUsersListWithInvites()
    {
        $data = (new Merchant\Service)->getUsersListWithInvites();

        return AppResponse::jsonResponse(null, $data);
    }

    /**
     * Remove the team member on the given merchant.
     *
     * @param  string  $userId
     * @return \Illuminate\Http\Response
     */
    public function removeTeamMember($userId)
    {
        $error = (new Merchant\Service)->removeTeamMemberForOwner($userId);

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
        $merchant = Auth::user()->currentMerchant;

        list($error, $keys) = (new Merchant\Service)->fetchKeysFromApi($merchant->id, $mode);

        return AppResponse::jsonResponse($error, $keys);
    }

    public function postNewKey($mode)
    {
        $merchant = Auth::user()->currentMerchant;

        list($error, $data) = (new Merchant\Service)->createKey($merchant->id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postKeys($mode)
    {
        $input = Input::all();

        $input['merchant_id'] = Auth::user()->getCurrentMerchantId();

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

        $id = Auth::user()->getCurrentMerchantId();

        $data = (new Merchant\Service)->fetchMerchantBalance($id);

        return AppResponse::jsonResponse([], $data[$mode]);
    }

    /**
     * Completely dashboard side function
     */
    public function getReferredMerchants()
    {
        $id = Auth::user()->getCurrentMerchantId();
        $data = (new Merchant\Service)->fetchReferredMerchants($id);

        return AppResponse::jsonResponse([], $data);
    }

    public function getMerchantConfig()
    {
        $id = Auth::user()->getCurrentMerchantId();

        list($error, $data) = (new Merchant\Service)->fetchMerchantConfig($id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function putMerchantConfig()
    {
        $id = Auth::user()->getCurrentMerchantId();
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)
            ->updateMerchantConfig($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postMerchantConfigLogo()
    {
        $input = Input::all();

        $merchantId = Auth::user()->getCurrentMerchantId();
        $input['merchant_id'] = $merchantId;

        list($error, $data) = (new Merchant\Service)->updateMerchantLogoConfig($merchantId, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Registers a new sub-merchant account
     * This will automatically have the correct
     * referral field and dashboard users added.
     */
    public function postRegisterSubMerchant()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)
            ->registerSubMerchant($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getBankAccount()
    {
        list($error, $data) = (new Merchant\Service)
            ->fetchBankAccount();

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Upload Batch File
     * @param  string $mode Live/Test Mode
     * @return Array       Array of error and response
     */
    public function uploadBatchFile($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $response) = (new Api\Service)->uploadBatchFile($mode, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
     * Fetch Multiple Batches
     * @param  string $mode Live/Test Mode
     * @return Array       Array of error and response
     */
    public function fetchMultipleBatches($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $response) = (new Api\Service)->fetchMultipleBatches($mode, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
     * Fetch batch by id
     * @param  string $mode Live/Test Mode
     * @param  string $id   Batch Id
     * @return Array       Array of error and response
     */
    public function fetchBatchById($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $response) = (new Api\Service)->fetchBatchById($mode, $id);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
     * Download Batch file
     * @param  string $mode Live/Test Mode
     * @param  string $id   Batch Id
     * @return Array       Array of error and response
     */
    public function downloadBatchFile($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $response) = (new Api\Service)->downloadBatchFile($mode, $id);

        if (empty($response) !== true)
        {
            return redirect($response['url']);
        }
        else
        {
            return AppResponse::jsonResponse($error, $response);
        }
    }

    /**
     * Retry given batch
     * @param  string $mode Live/Test Mode
     * @param  string $id   Batch Id
     * @return Array       Array of error and response
     */
    public function retryBatchFile($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $response) = (new Api\Service)->retryBatchFile($mode, $id);

        return AppResponse::jsonResponse($error, $response);
    }

    public function getInvitationDetails($token)
    {
        list($error, $response) = (new Merchant\Service)->getInvitationDetails($token);

        return AppResponse::jsonResponse($error, $response);
    }
}
