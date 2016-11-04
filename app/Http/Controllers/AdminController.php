<?php
namespace App\Http\Controllers;

use App\Http\AppResponse;
use App\Http\SlackResponse;

use App\Admin;
use App\Admin\Entity;
use App\Merchant;

use App;
use Auth;
use Input;
use Config;
use OAuthFacade;
use Redirect;

class AdminController extends Controller
{

    protected $redirectTo = '/admin';
    protected $guard = 'admin';

    // Org Name/Key => Org ID
    // We'll hardcode this for now
    const ORG_CHART = [
        'RZP' => 1
    ];

    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Defines the actions for an Admin on the dashboard
    |
    */

    public function __construct()
    {
        $this->admin = Auth::guard('admin')->user();
    }

    public function getIndex()
    {
        // Fetch Org details

        list($error, $org) = (new Admin\Service)->getOrg(self::ORG_CHART['RZP']);

        // Do whatever you want to with $org now ...

        if ($org['auth_type'] === 'password')
        {
            // It is the default anyway
            // return view('admin.tmpgetIndex');
        }
        else if ($org['auth_type'] === 'google_oauth')
        {
            $googleOAuth = $this->triggerGoogleOAuth();

            if (! empty($googleOAuth)) return $googleOAuth;
        }

        // Default is auth_type = 'password'
        return view('admin.tmpgetIndex');
    }

    public function triggerGoogleOAuth()
    {
        $code = Input::get('code');
        $googleService = OAuthFacade::consumer('Google');

        // If the user is not logged in
        if (!Auth::guard('admin')->check())
        {
            // if code is provided get user data and sign in
            if ($code !== null or env('OAUTH_MOCK') === true)
            {
                $error = (new Admin\Service)->loginWithGoogle($code, $googleService);

                if (empty($error))
                {
                    return redirect('/admin');
                }
                else
                {
                    return AppResponse::jsonResponse($error, []);
                }
            }
            else
            {
                return redirect((string) $googleService->getAuthorizationUri());
            }
        }
    }

    public function postSignin()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->login($input);

        return AppResponse::jsonResponse($error);
    }

    public function getAdmin()
    {
        return AppResponse::jsonResponse([], $this->admin->toArray());
    }

    public function getAdminActivity()
    {
        $id = Auth::guard('admin')->user()->id;

        $activity = (new Admin\Service)->getAdminActivity($id);

        return AppResponse::jsonResponse([], $activity);
    }

    public function deleteOtherAdminActivity()
    {
        $id = Auth::guard('admin')->user()->id;

        (new Admin\Service)->deleteAllOtherAdminSessions($id);

        return AppResponse::jsonResponse([]);
    }

    public function deleteAdminActivity($sessionId)
    {
        (new Admin\Service)->deleteOneAdminSessions($sessionId);

        return AppResponse::jsonResponse([]);
    }

    public function getLogout()
    {
        Auth::guard('admin')->logout();

        return AppResponse::jsonResponse([]);
    }

    public function getKeepAlive()
    {
        $error = [];
        $response = (new Admin\Service)->updateKeepAlive();

        if ($response === false)
        {
            // Auth::admin()->logout();
            // $error = ['You have been logged out'];
        }

        return AppResponse::jsonResponse($error, $response);
    }

    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->changePassword($input, Auth::guard('admin')->user());

        return AppResponse::jsonResponse($error);
    }

    public function putEdit($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->editAdmin($input, $id);

        return AppResponse::jsonResponse($error);
    }

    public function getMerchantList()
    {
        $input = Input::all();

        $merchants = (new Admin\Service)->listMerchants($input);

        return AppResponse::jsonResponse([], $merchants);
    }

    public function getMerchantLogin($id)
    {
        $error = (new Admin\Service)->loginUsingPrimaryOwner($id);

        if(empty($error) === false)
            return AppResponse::jsonResponse($error);

        return redirect('/');
    }

    public function getMerchant($id)
    {
        list($error, $data) = (new Admin\Service)->fetchFullMerchantDetails($id);

        return AppResponse::jsonResponse($error, $data);
    }


    public function getMerchantTerminal($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postMerchantTerminal($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postMerchantTerminal($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postMerchantTerminal($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postMerchantPricing($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postMerchantPricing($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMerchantActivation($id)
    {
        $dashboardOnly = Input::get('dashboard', false);
        $error = (new Admin\Service)->activateMerchant($id, $dashboardOnly);

        return AppResponse::jsonResponse($error);
    }

    public function getMerchantLiveEnable($id)
    {

        $error = (new Admin\Service)->liveEnableMerchant($id);

        return AppResponse::jsonResponse($error);
    }

    public function getMerchantLiveDisable($id)
    {
        $error = (new Admin\Service)->liveDisableMerchant($id);

        return AppResponse::jsonResponse($error);
    }

    public function getMerchantArchive($id)
    {

        $error = (new Admin\Service)->archiveMerchant($id);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Calls the Creevey service over a queue to capture screenshots
     * @param  string $id Merchant Id
     */
    public function captureMerchantScreenshot($id)
    {
        $error = (new Admin\Service)->captureScreenshot($id);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Returns an HTML View for now
     * @param  string $id merchant id
     */
    public function getMerchantScreenshot($id)
    {
        $links = (new Admin\Service)->getScreenshot($id);
        return View::make('admin.screenshots', ['links' => $links]);
    }

    public function saveMerchantScreenshot($id)
    {
        $input = \Input::all();
        $error = (new Admin\Service)->saveScreenshot($id, $input);
        return AppResponse::jsonResponse($error);
    }

    public function getMerchantUnarchive($id)
    {

        $error = (new Admin\Service)->unarchiveMerchant($id);

        return AppResponse::jsonResponse($error);
    }

    public function postEditMethods($id)
    {
        $input = \Input::all();

        $error = (new Admin\Service)->editMethods($id, $input);

        return AppResponse::jsonResponse($error);
    }

    public function getLockMerchantDetails($id)
    {
        $error = (new Admin\Service)->lockMerchant($id);

        return AppResponse::jsonResponse($error);
    }

    public function getUnlockMerchantDetails($id)
    {
        $error = (new Admin\Service)->unlockMerchant($id);

        return AppResponse::jsonResponse($error);
    }

    public function getMerchantDetails($id)
    {
        list($error, $data) = (new Admin\Service)->fetchMerchantAndActivationDetails($id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMerchantFeatures($id)
    {
        list($error, $data) = (new Admin\Service)->fetchMerchantFeatures($id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMerchantBalance($id)
    {
        $data = (new Merchant\Service)->fetchMerchantBalance($id);

        return AppResponse::jsonResponse([], $data);
    }

    public function getMerchantBanks($id)
    {
        $data = (new Admin\Service)->fetchMerchantBanks($id);

        return AppResponse::jsonResponse([], $data);
    }

    public function getSupportedNetworks()
    {
        $data = (new Admin\Service)->fetchPaymentNetworks();

        return AppResponse::jsonResponse([], $data->toArray());
    }

    public function postEditMerchant($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postEditMerchant($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function putEditMerchantEmail($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postEditMerchantEmail($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function putEditBankDetails($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postEditBankDetails($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postEditMerchantComment($id)
    {
        $comment = Input::get('comment');

        list($error, $data) = (new Admin\Service)->postEditMerchantComment($id, $comment);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postMerchantBanks($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postMerchantBanks($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postAddAdjustment($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postAddAdjustment($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postInitiateSetl($channel)
    {
        list($error, $data) = (new Admin\Service)->postInitiateSetl($channel);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postAddIIN()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->postAddIIN($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPaymentRefunds($mode, $paymentId)
    {
        list($error, $data) = (new Admin\Service)->getPaymentRefunds($mode, $paymentId);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getVerifyPayment($mode, $id)
    {
        $this->checkMode($mode);

        list($error, $data) = (new Admin\Service)->getVerifyPayment($mode, $id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postAuthorizeFailedPayment($mode, $id)
    {
        list($error, $data) = (new Admin\Service)->authorizeFailedPayment($mode, $id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postRefundAuthorizedPayment($mode, $merchantId, $id)
    {
        list($error, $data) = (new Admin\Service)->refundAuthorizedPayment($mode, $merchantId, $id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postRefund($mode, $merchantId, $id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->refundPayment($mode, $merchantId, $id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postCapture($mode, $merchantId, $id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->capturePayment($mode, $merchantId, $id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPricingList()
    {
        list($error, $data) = (new Admin\Service)->fetchPricingPlans();

        return AppResponse::jsonResponse($error, $data);
    }

    public function getPricingRules($id)
    {
        list($error, $data) = (new Admin\Service)->fetchPricingPlan($id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postPricingRules($id)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->addPricingPlanRule($id, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function deletePricingPlanRule($planId, $ruleId)
    {
        list($error, $data) = (new Admin\Service)
            ->deletePricingPlanRule($planId, $ruleId);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postNewPricingPlan()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->createPricingPlan($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMultipleEntities($mode, $entity, $format = 'json')
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->fetchMultipleEntities($mode, $entity, $input);

        if ($format === 'csv' and empty($error) === true)
        {
            (new Admin\Service)->logDataExport($entity, $input);

            return AppResponse::csvResponse($data['items']);
        }
        else
        {
            return AppResponse::jsonResponse($error, $data);
        }
    }

    public function getEntityById($mode, $entity, $id)
    {
        list($error, $data) = (new Admin\Service)->fetchEntityById($mode, $entity, $id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getAdmins()
    {
        $admins = (new Admin\Service)->getAdmins();

        return AppResponse::jsonResponse([], $admins);
    }

    public function getDeleteAdmin($id)
    {
        $error = (new Admin\Service)->deleteAdmin($id);

        return AppResponse::jsonResponse($error);
    }

    public function postAddAdmin()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->add($input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Promotes a user to a superadmin
     */
    public function postPromoteAdmin($id)
    {
        list($error) = (new Admin\Service)->promote($id);

        return AppResponse::jsonResponse($error);
    }

    public function getMerchantHdfcExcel($id)
    {
        list($error, $file) = (new Admin\Service)->generateMerchantHdfcExcel($id);

        if (empty($error) === false)
            return AppResponse::jsonResponse($error);

        $file->download('xlsx');
    }

    public function getBeneficiaryFile()
    {
        $input = Input::all();

        list($error, $url) = (new Admin\Service)->getBeneficiaryFile($input);

        if(empty($error) === false)
        {
            return AppResponse::jsonResponse($error);
        }

        return Redirect::to($url);
    }

    public function generateBeneficiaryFile()
    {
        $error = (new Admin\Service)->generateBeneficiaryFile();
        return AppResponse::jsonResponse($error);
    }

    public function postSendTestNewsletter()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->sendTestNewsletter($input);
        return AppResponse::jsonResponse($error, $data);
    }

    public function postSendNewsletter()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->sendNewsletter($input);
        return AppResponse::jsonResponse($error, $data);
    }

    public function triggerError()
    {
        list($error, $data) = (new Admin\Service)->triggerError();
        return AppResponse::jsonResponse($error, $data);
    }

    public function deleteTerminal($mode, $terminalId)
    {
        list($error, $data) = (new Admin\Service)->deleteTerminal($mode, $terminalId);
        return AppResponse::jsonResponse($error, $data);
    }

    public function editTerminal($mode, $terminalId)
    {
        $input = Input::all();
        list($error, $data) = (new Admin\Service)->editTerminal($mode, $terminalId, $input);
        return AppResponse::jsonResponse($error, $data);
    }

    public function toggleTerminal($mode, $terminalId)
    {
        $input = Input::all();
        list($error, $data) = (new Admin\Service)->toggleTerminal($mode, $terminalId, $input);
        return AppResponse::jsonResponse($error, $data);
    }

    public function verifyAllPayments()
    {
        list($error, $data) = (new Admin\Service)->verifyAllPayments();
        return AppResponse::jsonResponse($error, $data);
    }

    public function generateNetBankingRefunds()
    {
        $input = Input::all();
        list($error, $data) = (new Admin\Service)->generateNetBankingRefunds($input);
        return AppResponse::jsonResponse($error, $data);
    }

    public function passThrough($path = '')
    {
        list($error, $response) = (new Admin\Service)->makeRawApiCall($path);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postReconcileSettlement()
    {
        $path = 'settlements/reconcile';
        list($error, $response) = (new Admin\Service)->makeRawApiCall($path);

        return AppResponse::jsonResponse($error, $response);
    }

    public function editCredits($merchantId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)
            ->editCredits($merchantId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postTagMerchant($merchantId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)
            ->tagMerchant($merchantId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function syncMerchantFeatures($merchantId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)
            ->syncMerchantFeatures($merchantId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function postSetMerchantInternational($merchantId)
    {
        $input = Input::only('international');

        list($error, $response) = (new Admin\Service)
            ->postSetMerchantInternational($merchantId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function getMerchantTags($merchantId)
    {
        list($error, $response) = (new Admin\Service)
            ->getMerchantTags($merchantId);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
     * Confirm a merchant account manually
     */
    public function postConfirmMerchant($merchantId)
    {
        list($error, $data) = $response = (new Admin\Service)
            ->confirmMerchant($merchantId);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
     * Edit an existing IIN
     * @param  int $iin 6 digit IIN
     */
    public function putEditIIN($iin)
    {
        $input = Input::all();
        list($error, $data) = $response = (new Admin\Service)
            ->editIIN($iin, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
     * This is currently not supported on the API
     * so we just return an error
     * @param  int $iin IIN to delete
     */
    public function deleteIIN($iin)
    {
        /*list($error, $data) = $response = (new Admin\Service)
            ->deleteIin($iin);*/

        $error = ["IIN Delete not implemented on API"];

        return AppResponse::jsonResponse($error, []);
    }

    /**
     * Deletes an EMI Plan
     * @param  string $emiId EMI Plan Id
     */
    public function deleteEMIPlan($emiId)
    {
        list($error, $data) = $response = (new Admin\Service)
            ->deleteEmi($emiId);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postAddEMIPlan()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)
            ->addEMI($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postSlackQuery()
    {
        $input = Input::all();

        list($message, $data) = (new Admin\Service)
            ->querySlack($input);

        return SlackResponse::jsonResponse($message, $data);
    }

    public function getMerchantAggregations($mode, $resource)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)
            ->getMerchantAggregations($mode, $resource, $input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getSingleMerchantAggregations($mode, $merchant, $resource)
    {
        list($error, $data) = (new Admin\Service)
            ->getSingleMerchantAggregations($merchant, $mode, $resource);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getCompanyInfo($cin)
    {
        $company = new Admin\Company($cin);
        return AppResponse::jsonResponse([], $company->fetch());
    }

    public function getMerchantBankAccount($merchantId)
    {
        list($error, $bankAccount) = (new Admin\Service)
            ->fetchBankAccount($merchantId);

        return AppResponse::jsonResponse($error, $bankAccount);
    }

    public function postReconciliate($mode)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->makeReconciliateRequest($input);

        return AppResponse::jsonResponse($error, $response);
    }

    /**
    * Expects date input in format "3 august 2016"
    */
    public function updateDayAggregations($mode)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->updateMerchantDayAggregations($mode, $input);

        return AppResponse::jsonResponse($error, $response);
    }
    // ----- Credits -----

    // Get log of merchant's credit entries
    public function getMerchantCreditsLog($merchantId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->getMerchantCreditsLog($merchantId, $input['mode']);

        return AppResponse::jsonResponse($error, $response);
    }

    // Add free credits for the merchant
    public function addMerchantCredits($merchantId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->addMerchantCredits($merchantId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    public function deleteMerchantCredit($merchantId, $creditId)
    {
        $input = Input::all();

        list($error, $response) = (new Admin\Service)->deleteMerchantCredit($merchantId, $creditId, $input);

        return AppResponse::jsonResponse($error, $response);
    }

    // ----- /Credits -----
}
