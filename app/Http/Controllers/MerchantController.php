<?php
namespace App\Http\Controllers;

use App;
use Auth;
use Input;
use App\Api;
use App\Merchant;
use App\MerchantDetails;
use App\Http\AppResponse;
use Illuminate\Http\Request;
use App\Mailers\ContactFormMailer;
use App\Mailers\MiscMailer;

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
        $merchant = Auth::user()->currentMerchant();

        list($error, $keys) = (new Merchant\Service)->fetchKeysFromApi($merchant->id, $mode);

        return AppResponse::jsonResponse($error, $keys);
    }

    public function postNewKey($mode)
    {
        $merchant = Auth::user()->currentMerchant();

        list($error, $data) = (new Merchant\Service)->createKey($merchant->id, $mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postActivation()
    {
        $input = ['submit' => true];

        list($error, $data) = (new Merchant\Service)->saveActivationData($input);

        if (empty($error) === true)
        {
            if ($data['can_submit'] === false)
            {
                $error = ['Some mandatory fields are required'];
            }
        }

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationStep($stepNumber)
    {
        $input = Input::all();

        $uploadStep = 5;

        $error = [];

        if ((int) $stepNumber !== $uploadStep)
        {
            list($error, $data) = (new Merchant\Service)->saveActivationData($input);
        }

        return AppResponse::jsonResponse($error);
    }

    public function postSaveActivationFile()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->saveActivationFilesData($input);

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

        list($error, $data) = (new Merchant\Service)->fetchInvoices($mode);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postCreateInvoice($mode)
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->createInvoice($mode, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function sendInvoiceNotification($mode, $invoiceId, $medium)
    {
        list($error, $data) = (new Merchant\Service)->sendInvoiceNotification($mode, $invoiceId, $medium);

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

        list($error, $data) = (new Merchant\Service)->registerSubMerchant($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Registers a new user account for a
     * sub-merchant with his own email.
     */
    public function postRegisterSubUser()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->registerSubMerchantUser($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postSignup()
    {
        $id = Auth::user()->currentMerchant()->id;

        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->savePreSignupDetails($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSignup()
    {
        $id = Auth::user()->currentMerchant()->id;

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

    public function sendFeedback()
    {
        $input = Input::all();

        $mailer = new MiscMailer();

        $mailer->sendFeedbackToSupport($input['email'], $input['subject'], $input['message'])->queueAndDeliver();

        return AppResponse::jsonResponse([], ['success' => true]);
    }
}
