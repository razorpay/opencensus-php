<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Key;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;
use RZP\Models\Terminal;

class MerchantController extends Controller
{
    public function postCreateMerchant()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function postCreateSubMerchant()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->createSubMerchant($input);

        return ApiResponse::json($data);
    }

    public function putMerchant($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    /**
     *  This updates the merchant email
     *  Don't use lightly
     */
    public function putMerchantEmail($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->editEmail($id, $input);

        return ApiResponse::json($data);
    }

    public function putMerchantConfig()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->editConfig($input);

        return ApiResponse::json($data);
    }

    public function postMerchantConfigLogo()
    {
        if (Request::hasFile('logo'))
        {
            $input['logo'] = Request::file("logo");

            $data = (new Merchant\Service)->editConfig($input);

            return ApiResponse::json($data);
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOGO_NOT_PRESENT
            );
        }
    }

    public function deleteMerchantConfigLogo()
    {
        $data = (new Merchant\Service)->deleteMerchantLogo();

        return ApiResponse::json($data);
    }

    // This is on Internal Auth
    public function getMerchant($id)
    {
        $data = (new Merchant\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function getMerchants()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postCreateKeys($merchantId)
    {
        $data = (new Merchant\Service)->createKey($merchantId);

        return ApiResponse::json($data);
    }

    public function getKeys($merchantId)
    {
        $data = (new Merchant\Service)->fetchKeys($merchantId);

        return ApiResponse::json($data);
    }

    public function getKeySecret($keyId)
    {
        $data = (new Key\Core)->getKeySecret($keyId);

        return ApiResponse::json($data);
    }

    public function putKeys($merchantId, $keyId)
    {
        $input = Request::all();

        $keys = (new Merchant\Service)->updateKey($merchantId, $keyId, $input);

        return ApiResponse::json($keys);
    }

    public function postAssignPricingPlan($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->assignPricingPlan($id, $input);

        return ApiResponse::json($data);
    }

    public function assignSettlementSchedule($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->assignSettlementSchedule($id, $input);

        return ApiResponse::json($data);
    }

    public function getPricingPlan($id)
    {
        $data = (new Merchant\Service)->getPricingPlan($id);

        return ApiResponse::json($data);
    }

    public function postCreateTerminal($id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->createTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function postCopyTerminal($mid, $tid)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->copyTerminal($mid, $tid, $input);

        return ApiResponse::json($data);
    }

    public function getTerminals($mid)
    {
        $data = (new Terminal\Service)->getTerminals($mid);

        return ApiResponse::json($data);
    }

    public function getTerminal($mid, $tid)
    {
        $data = (new Terminal\Service)->getTerminal($mid, $tid);

        return ApiResponse::json($data);
    }

    public function deleteTerminal($mid, $tid)
    {
        $data = (new Terminal\Service)->deleteTerminal($mid, $tid);

        return ApiResponse::json($data);
    }

    public function putTerminal($mid, $tid)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->modifyTerminal($mid, $tid, $input);

        return ApiResponse::json($data);
    }

    public function postActivate($id)
    {
        $data = (new Merchant\Service)->activate($id);

        return ApiResponse::json($data);
    }

    public function postLiveEnable($id)
    {
        $data = (new Merchant\Service)->liveEnable($id);

        return ApiResponse::json($data);
    }

    public function postLiveDisable($id)
    {
        $data = (new Merchant\Service)->liveDisable($id);

        return ApiResponse::json($data);
    }

    public function postBankAccount($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function getBankAccount($id)
    {
        $data = (new Merchant\Service)->getBankAccount($id);

        return ApiResponse::json($data);
    }

    /** Fetches merchant's own Bank Account details */
    public function getOwnBankAccount()
    {
        $data = (new Merchant\Service)->getOwnBankAccount();

        return ApiResponse::json($data);
    }

    public function postGenerateTestBankAccounts()
    {
        $data = (new Merchant\Service)->generateTestBankAccounts();

        return ApiResponse::json($data);
    }

    public function getBanksPublic()
    {
        $data = (new Merchant\Service)->getEnabledBanks();

        return ApiResponse::json($data);
    }

    public function getBanks($id)
    {
        $data = (new Merchant\Service)->getBanks($id);

        return ApiResponse::json($data);
    }

    public function setBanks($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->setPaymentBanks($id, $input);

        return ApiResponse::json($data);
    }

    public function putMethods($merchantId)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->setPaymentMethods($merchantId, $input);

        return ApiResponse::json($data);
    }

    public function getBalance($id)
    {
        $data = (new Merchant\Service)->fetchBalance($id);

        return ApiResponse::json($data);
    }

    public function getAccountBalance()
    {
        $data = (new Merchant\Service)->fetchBalance();

        return ApiResponse::json($data);
    }

    // This is on proxy Auth
    public function getAccountConfig()
    {
        $data = (new Merchant\Service)->fetchConfig();

        return ApiResponse::json($data);
    }

    public function postAmountCredits($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->editAmountCredits($id, $input);

        return ApiResponse::json($data);
    }

    public function getPaymentMethods()
    {
        $data = (new Merchant\Service)->getPaymentMethods();

        return ApiResponse::json($data);
    }

    public function getCheckoutPreferences()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->getCheckoutPreferences($input);

        return ApiResponse::json($data);
    }

    public function getMerchantBeneficiaryFile()
    {
        $data = (new Merchant\Service)->getMerchantBeneficiaryFile();

        return ApiResponse::json($data);
    }

    public function getMerchantWebhooks($id)
    {
        $data = (new Merchant\Service)->getMerchantWebhooks($id);

        return ApiResponse::json($data);
    }

    public function postWebhook()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->createWebhook($input);

        return ApiResponse::json($data);
    }

    public function putWebhook($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->editWebhook($id, $input);

        return ApiResponse::json($data);
    }

    public function getWebhook($id)
    {
        $data = (new Merchant\Service)->getWebhook($id);

        return ApiResponse::json($data);
    }

    public function getWebhooks()
    {
        $data = (new Merchant\Service)->getWebhooks();

        return ApiResponse::json($data);
    }

    public function postMerchantBeneficiaryFile()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->postMerchantBeneficiaryFile($input);

        return ApiResponse::json($data);
    }

    public function getCheckout()
    {
        $input = Request::all();

        $prefs = (new Merchant\Service)->getCheckoutPreferences($input);

        $data = $this->getCheckoutCommon();

        $data['preferences'] = $prefs;

        return ApiResponse::generateResponse($data);
    }

    public function getCheckoutPublic()
    {
        $data = $this->getCheckoutCommon();

        return \View::make('checkout.checkout')
                    ->with($data);
    }

    protected function getCheckoutCommon()
    {
        $input = Request::all();

        $context = $this->config->get('app.context');

        $url = $this->config->get('app.checkout');

        $urlMap = $this->config->get('url.checkout');

        $cdnUrlMap = $this->config->get('url.cdn');

        $framejs = '/v1/checkout-frame.js';

        $css = '/v1/css/checkout.css';

        $font = '/lato';

        $data = [];

        if (in_array($context, array_keys($urlMap)))
        {
            $url = $urlMap[$context];
        }
        else if (isset($input['checkout']))
        {
            $url = $input['checkout'];
        }

        $data['checkout'] = $url;
        $data['framejs'] = $url . $framejs;
        $data['css'] = $url . $css;
        $data['font'] = $cdnUrlMap['production'].$font;

        return $data;
    }

    public function getPublicEntityReport($entity)
    {
        $input = Request::all();

        return (new \RZP\Models\Base\Report)->getReport($input, $entity);
    }

    public function getInvoiceReport()
    {
        $input = Request::all();

        return (new \RZP\Models\Base\Report)->getInvoice($input);
    }

    public function getInvoiceReportV2()
    {
        $input = Request::all();

        return (new \RZP\Models\Base\Report)->getInvoiceV2($input);
    }

    /**
     * Sends an email to every merchant
     * with all transactions from yesterday
     */
    public function sendDailyReport()
    {
        $input = Request::all();

        $response = (new \RZP\Models\Merchant\Service)->sendDailyReportForAllMerchants($input);

        return ApiResponse::json($response);
    }

    /**
    * Gets the list of beta fetures enabled for merchant
    * @param  string $id merchant id
    * @return array      array of feature names
    */
    public function getMerchantFeatures($id)
    {
        $data = (new Merchant\Service)->getMerchantFeatures($id);

        return ApiResponse::json($data);
    }

    /**
     * Adds or updated the list of beta fetures for an merchant
     * @param  string     $id     merchant id
     * @return merchant           updated entity
     */
    public function postMerchantFeatures($id)
    {
        $input = Request::all();

        $data = (new Merchant\Service)->addOrUpdateMerchantFeatures($id, $input);

        return ApiResponse::json($data);
    }

    public function getDummyFeatures()
    {
        $input = Request::all();

        return ApiResponse::json($input);
    }

    public function postMerchantsNotifyHoliday()
    {
        $input = Request::all();

        $data = (new Merchant\Service)->notifyMerchantsHoliday($input);

        return ApiResponse::json($data);
    }

// --------------------- Credits API Handlers -----------------------------------------

    public function postCreateCreditsLog(Credits\Service $service, $id)
    {
        $input = Request::all();

        $data = $service->grantCreditsForMerchant($id, $input);

        return ApiResponse::json($data);
    }

    public function getCreditsLog(Credits\Service $service, $mid, $id)
    {
        $data = $service->fetchCreditsLog($mid, $id);

        return ApiResponse::json($data);
    }

    public function putCreditsLog(Credits\Service $service, $mid, $id)
    {
        $input = Request::all();

        $data = $service->updateCreditsLog($mid, $id, $input);

        return ApiResponse::json($data);
    }

    public function getCreditsLogs(Credits\Service $service)
    {
        $input = Request::all();

        $data = $service->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function deleteCreditsLog(Credits\Service $service, $mid, $id)
    {
        $data = $service->deleteCreditsLog($mid, $id);

        return ApiResponse::json($data);
    }

// --------------------- End Credits API Handlers -----------------------------------------
}
