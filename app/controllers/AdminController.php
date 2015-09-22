<?php

use Http\AppResponse;
use Models\Admin;
use Models\Merchant;

class AdminController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | Admin Controller
    |--------------------------------------------------------------------------
    |
    | Defines the actions for an Admin on the dashboard
    |
    */

    public function getIndex()
    {
        return View::make('admin.tmpgetIndex');
    }

    public function postSignin()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->login($input);

        return AppResponse::jsonResponse($error);
    }

    public function getAdmin()
    {
        $admin = Auth::admin()->get()->toArray();

        return AppResponse::jsonResponse([], $admin);
    }

    public function getLogout()
    {
        Auth::admin()->logout();

        return AppResponse::jsonResponse([]);
    }

    public function getKeepAlive()
    {
        return AppResponse::jsonResponse([]);
    }

    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->changePassword($input, Auth::admin()->get());

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
        $merchant = (new Merchant\Service)->fetch($id);

        Auth::merchant()->loginUsingId($merchant['id']);

        return Redirect::to('/');
    }

    public function getMerchant($id)
    {
        $details = (new Admin\Service)->fetchMerchantDetails($id);

        if (isset($details['confirm_token']))
        {
            return AppResponse::jsonResponse(['Merchant not confirmed']);
        }

        $terminal = (new Admin\Service)->fetchMerchantTerminal($id);

        $pricing_plan = (new Admin\Service)->fetchMerchantPricing($id);

        $data = array(
                    'details' => $details,
                    'terminals' => $terminal,
                    'pricing_plan' => $pricing_plan
                );

        return AppResponse::jsonResponse([], $data);
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
        $error = (new Admin\Service)->activateMerchant($id);

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

    public function getMerchantMethodEnable($id, $method)
    {

        $error = (new Admin\Service)->enableMerchantMethod($id, $method);

        return AppResponse::jsonResponse($error);
    }

    public function getMerchantMethodDisable($id, $method)
    {
        $error = (new Admin\Service)->disableMerchantMethod($id, $method);

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

    public function getMerchantBalance($id)
    {
        $data = (new Admin\Service)->fetchMerchantBalance($id);

        return AppResponse::jsonResponse([], $data);
    }

    public function getMerchantBanks($id)
    {
        $data = (new Admin\Service)->fetchMerchantBanks($id);

        return AppResponse::jsonResponse([], $data);
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

    public function getVerifyPayment($id)
    {
        list($error, $data) = (new Admin\Service)->getVerifyPayment($id);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postAuthorizeFailedPayment($id)
    {
        list($error, $data) = (new Admin\Service)->authorizeFailedPayment($id);

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

    public function postNewPricingPlan()
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->createPricingPlan($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getMultipleEntities($mode, $entity)
    {
        $input = Input::all();

        list($error, $data) = (new Admin\Service)->fetchMultipleEntities($mode, $entity, $input);

        return AppResponse::jsonResponse($error, $data);
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
}
