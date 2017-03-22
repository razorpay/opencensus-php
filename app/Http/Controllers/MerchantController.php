<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

    public function getActivationDetails($accountId = null)
    {
        $service = new MerchantDetails\Service;

        if ($accountId !== null)
        {
            $service->forAccount($accountId);
        }

        $response = $service->fetchDetails();

        return AppResponse::jsonResponse([], $response);
    }

    public function postActivation($accountId = null)
    {
        $service = new MerchantDetails\Service;

        if ($accountId !== null)
        {
            $service->forAccount($accountId);
        }

        $error = $service->submitDetails();

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationStep($stepNumber, $accountId = null)
    {
        $input = Input::all();

        $service = new MerchantDetails\Service;

        $uploadStep = 5;

        if ($accountId !== null)
        {
            $service->forAccount($accountId);

            $uploadStep = 3;
        }

        if ((int) $stepNumber !== $uploadStep)
        {
            $error = $service->saveDetails($stepNumber, $input);
        }
        else
        {
            $error = $service->checkUploads();

        }

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationFile($accountId = null)
    {
        $input = Input::all();

        $service = new MerchantDetails\Service;

        if ($accountId !== null)
        {
            $service->forAccount($accountId);
        }

        $error = $service->saveUploadedFile($input);

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

    public function getInvoices($mode)
    {
        $this->checkMode($mode);

        $input = Input::all();

        list($error, $data) = (new Api\Service)->fetchCollection($input, $mode, 'invoice');

        return AppResponse::jsonResponse($error, $data);
    }

    public function sendInvoiceNotification($mode, $invoiceId, $medium)
    {
        list($error, $data) = (new Merchant\Service)->sendInvoiceNotification($mode, $invoiceId, $medium);

        return AppResponse::jsonResponse($error, $data);
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

    /**
     * Registers a new user account for a
     * sub-merchant with his own email.
     */
    public function postRegisterSubUser()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)
            ->registerSubMerchantUser($input);

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

    public function postSignup()
    {
        $id = Auth::user()->getCurrentMerchantId();

        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->savePreSignupDetails($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSignup()
    {
        $id = Auth::user()->getCurrentMerchantId();

        $error = $data = [];

        $data = (new Merchant\Service)->getPreSignupDetails($id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getCustomersForAutocomplete($mode)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchCollectionForAutocomplete($mode, 'customer');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getItemsForAutocomplete($mode)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchCollectionForAutocomplete($mode, 'item');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getAccounts(Request $request, $mode)
    {
        $this->checkMode($mode);

        $input = $request->all();

        list($error, $data) = (new Api\Service)->fetchCollectionForMarketplaceAccounts($input);

        return AppResponse::jsonResponse($error, $data);
    }
}
