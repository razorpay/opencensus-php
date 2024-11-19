<?php
namespace App\Http\Controllers;

use Auth;
use Input;

use App\Api;
use App\User;
use App\Generic;
use App\Merchant;
use App\Http\AppResponse;
use App\Mailers\MiscMailer;
use Illuminate\Http\Request;
use App\Admin\ApiRequestAny;
use App\Splitz\Service as SplitzService;
use App\MerchantDetails\Service as DetailService;

class MerchantController extends Controller
{
    public function postResendConfirmation()
    {
        list($error, $data) = (new Merchant\Service)->resendConfirmation();

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

    public function ezetapVoidApi()
    {
        $input = Input::all();

        list($error, $keys) = (new Merchant\Service)->ezetapVoidApi($input);

        return AppResponse::jsonResponse($error, $keys);
    }

    public function ezetapReceiptApi($razorpayReferenceId) {
        $input = Input::all();

        list($error, $keys) = (new Merchant\Service)->ezetapReceiptApi($razorpayReferenceId);

        return AppResponse::jsonResponse($error, $keys);
    }

    public function ezetapRefundApi()
    {
        $input = Input::all();

        list($error, $keys) = (new Merchant\Service)->ezetapRefundApi($input);

        return AppResponse::jsonResponse($error, $keys);
    }

    public function storeAppKeys()
    {
        $input = Input::all();

        $merchant = Auth::user()->currentMerchant();

        list($error, $keys) = (new Merchant\Service)->storeAppKeys($merchant->id, $input);

        return AppResponse::jsonResponse($error, $keys);
    }

    public function fetchAppKeys()
    {
        $merchant = Auth::user()->currentMerchant();

        list($error, $keys) = (new Merchant\Service)->fetchAppKeys($merchant->id);

        return AppResponse::jsonResponse($error, $keys);
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

    public function generateChatBotToken()
    {

        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->generateChatBotToken($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function pushRazorassistEvent()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->pushRazorassistEvent($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function generateSupersetToken()
    {
        list($error, $data) = (new Merchant\Service)->generateSupersetToken();

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

    public function getSupportChatJwtToken()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->getSupportChatJwtToken($input);

        return AppResponse::jsonResponse($error,$data);
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

        list($error, $data, $httpCode) = (new Merchant\Service)->registerSubMerchant($input);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postSignup()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->savePreSignupDetails($input);

        return AppResponse::jsonResponse($error, $data);
    }
    public function getBusinessTypes()
    {
        list($error, $data) = (new Merchant\Service)->getBusinessTypes();

        return AppResponse::jsonResponse($error,$data);
    }

    public function getCustomersForAutocomplete($mode)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Api\Service)->fetchCollectionForAutocomplete($mode, 'customer');

        return AppResponse::jsonResponse($error, $data);
    }

    public function getItemsForAutocomplete(Request $request, string $mode)
    {
        $this->checkMode($mode);

        $input = $request->all();

        list($error, $data) = (new Api\Service)->fetchCollectionForAutocomplete($mode, 'item', $input);

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

    public function downloadReport(string $logId)
    {
        $adminUser = Auth::guard('api')->user();

        $clientType = ['client_type' => 'merchant'];

        $reportingLogUrl = "reporting/logs/$logId";

        if (empty($adminUser) === false)
        {
            $clientType = ['client_type' => 'admin'];
            $reportingLogUrl = "admin-reporting/logs/$logId";
        }

        $request = new ApiRequestAny($clientType);

        list($error, $data) = $request->send($reportingLogUrl, 'GET');

        if ((empty($error) === true) and
            (empty($data) === false) and
            (empty($data['file_id']) === false))
        {
            $fileId = $data['file_id'];

            return $this->downloadFileFromUFH($fileId);
        }

        return AppResponse::jsonResponse("Some error occurred.", null);
    }

    public function downloadFileFromUFH(string $fileId)
    {
        $adminUser = Auth::guard('api')->user();

        $clientType = ['client_type' => 'merchant'];

        $ufhFileUrl = "ufh/file/$fileId/get-signed-url";

        if (empty($adminUser) === false)
        {
            $clientType = ['client_type' => 'admin'];

            $ufhFileUrl = "admin-ufh/file/$fileId/get-signed-url";
        }

        // Re-create to avoid any GC-related bugs
        $request = new ApiRequestAny($clientType);

        list($error, $data) = $request->send($ufhFileUrl, 'GET');

        // Trigger download
        if ((empty($error) === true) and
            (empty($data) === false))
        {
            if (isset($data['signed_url']))
            {
                return redirect($data['signed_url']);
            }
        }
        return AppResponse::jsonResponse("Some error occurred.", null);
    }

    public function removeUser(string $mode, string $userId)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Merchant\Service)->removeUser($mode, $userId);

        return AppResponse::jsonResponse($error, $data);
    }

    public function validateCoupon()
    {
        $input = Input::all();

        list($error, $data) = (new Merchant\Service)->validateCouponCode($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMerchantExperiments()
    {
         $data = (new Merchant\Service)->getExperiments();

        return AppResponse::jsonResponse([], $data);
    }

    public function getMerchantFeatures()
    {
        $data = (new Merchant\Service)->getMerchantFeatures();

        return AppResponse::jsonResponse([], $data);
    }

    public function getSplitzExperiments()
    {
        $currentUser = Auth::guard('user')->user();

        $merchantId  = $currentUser->currentMerchant()->id;

        $data = (new SplitzService())->getSplitzVariantBulk($merchantId);

        return AppResponse::jsonResponse([], $data);
    }

    public function getMerchantDetails()
    {
        $response = [];

        $response = (new DetailService)->updateMerchantDetails($response);

        return AppResponse::jsonResponse([], $response);
    }

    public function getMerchantTags()
    {
        $data = [];

        $currentUser = Auth::guard('user')->user();

        $currentMerchant = $currentUser->currentMerchant();

        if (empty($currentMerchant) === false)
        {
            $currentMerchantId  = $currentMerchant->id;

            $data = (new Merchant\Service)->getMerchantTags($currentMerchantId);
        }

        return AppResponse::jsonResponse([], $data);
    }

    public function getMerchantNavigationList()
    {
        $data = (new Merchant\Service)->getMerchantNavigationList();

        return AppResponse::jsonResponse([], $data);
    }

    public function handleMagicAnalyticsOAuthCallbackURL(Request $request, string $provider)
    {
        $path = $request->path();

        $queryParams = $request->query();

        $input = [
            'path' => $path,
            'provider' => $provider,
            'query_params' => $queryParams,
        ];

        $targetUrl = (new Api\Service)->handleMagicAnalyticsOAuthCallbackURL($input);

        return redirect($targetUrl, 303);
    }
}
