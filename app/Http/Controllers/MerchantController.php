<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Key;
use RZP\Models\Report;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Credits;

class MerchantController extends Controller
{
    public function postCreateMerchant()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function postCreateSubMerchant()
    {
        $input = Request::all();

        $data = $this->service()->createSubMerchant($input);

        return ApiResponse::json($data);
    }

    public function putMerchant($id)
    {
        $input = Request::all();

        $data = $this->service()->edit($id, $input);

        return ApiResponse::json($data);
    }

    /**
     *  This updates the merchant email
     *  Don't use lightly
     */
    public function putMerchantEmail($id)
    {
        $input = Request::all();

        $data = $this->service()->editEmail($id, $input);

        return ApiResponse::json($data);
    }

    public function putMerchantConfig()
    {
        $input = Request::all();

        $data = $this->service()->editConfig($input);

        return ApiResponse::json($data);
    }

    public function postMerchantConfigLogo()
    {
        if (Request::hasFile('logo'))
        {
            $input['logo'] = Request::file("logo");

            $data = $this->service()->editConfig($input);

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
        $data = $this->service()->deleteMerchantLogo();

        return ApiResponse::json($data);
    }

    // This is on Internal Auth
    public function getMerchant($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function getMerchants()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postCreateKeys($merchantId)
    {
        $data = $this->service()->createKey($merchantId);

        return ApiResponse::json($data);
    }

    public function getKeys($merchantId)
    {
        $data = $this->service()->fetchKeys($merchantId);

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

        $keys = $this->service()->updateKey($merchantId, $keyId, $input);

        return ApiResponse::json($keys);
    }

    public function postAssignPricingPlan($id)
    {
        $input = Request::all();

        $data = $this->service()->assignPricingPlan($id, $input);

        return ApiResponse::json($data);
    }

    public function assignSettlementSchedule($id)
    {
        $input = Request::all();

        $data = $this->service()->assignSettlementSchedule($id, $input);

        return ApiResponse::json($data);
    }

    public function migrateToSchedules()
    {
        $input = Request::all();

        $data = $this->service()->migrateMerchantToSettlementSchedules($input);

        return ApiResponse::json($data);
    }

    public function getPricingPlan($id)
    {
        $data = $this->service()->getPricingPlan($id);

        return ApiResponse::json($data);
    }

    public function postCreateTerminal($id)
    {
        $input = Request::all();

        $data = $this->service(E::TERMINAL)->createTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function postCopyTerminal($mid, $tid)
    {
        $input = Request::all();

        $data = $this->service(E::TERMINAL)->copyTerminal($mid, $tid, $input);

        return ApiResponse::json($data);
    }

    public function getTerminals($mid)
    {
        $input = Request::all();

        $data = $this->service(E::TERMINAL)->getTerminals($mid, $input);

        return ApiResponse::json($data);
    }

    public function getTerminal($mid, $tid)
    {
        $data = $this->service(E::TERMINAL)->getTerminal($mid, $tid);

        return ApiResponse::json($data);
    }

    public function deleteTerminal($mid, $tid)
    {
        $data = $this->service(E::TERMINAL)->deleteTerminal($mid, $tid);

        return ApiResponse::json($data);
    }

    public function putTerminal($mid, $tid)
    {
        $input = Request::all();

        $data = $this->service(E::TERMINAL)->modifyTerminal($mid, $tid, $input);

        return ApiResponse::json($data);
    }

    public function postActivate($id)
    {
        $data = $this->service()->activate($id);

        return ApiResponse::json($data);
    }

    public function postSendActivationMail()
    {
        $input = Request::all();

        $data = $this->service()->sendActivationEmail($input);

        return ApiResponse::json($data);
    }

    public function postLiveEnable($id)
    {
        $data = $this->service()->liveEnable($id);

        return ApiResponse::json($data);
    }

    public function postLiveDisable($id)
    {
        $data = $this->service()->liveDisable($id);

        return ApiResponse::json($data);
    }

    public function putAction($id)
    {
        $input = Request::all();

        $data = $this->service()->action($id, $input);

        return ApiResponse::json($data);
    }

    public function postBankAccount($id)
    {
        $input = Request::all();

        $data = $this->service()->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function getBankAccount($id)
    {
        $data = $this->service()->getBankAccount($id);

        return ApiResponse::json($data);
    }

    /** Fetches merchant's own Bank Account details */
    public function getOwnBankAccount()
    {
        $data = $this->service()->getOwnBankAccount();

        return ApiResponse::json($data);
    }

    public function postGenerateTestBankAccounts()
    {
        $data = $this->service()->generateTestBankAccounts();

        return ApiResponse::json($data);
    }

    public function getBanksPublic()
    {
        $data = $this->service()->getEnabledBanks();

        return ApiResponse::json($data);
    }

    public function getBanks($id)
    {
        $data = $this->service()->getBanks($id);

        return ApiResponse::json($data);
    }

    public function setBanks($id)
    {
        $input = Request::all();

        $data = $this->service()->setPaymentBanks($id, $input);

        return ApiResponse::json($data);
    }

    public function putMethods($merchantId)
    {
        $input = Request::all();

        $data = $this->service()->setPaymentMethods($merchantId, $input);

        return ApiResponse::json($data);
    }

    public function getAccountBalance()
    {
        $data = $this->service()->fetchBalance();

        return ApiResponse::json($data);
    }

    // This is on proxy Auth
    public function getAccountConfig()
    {
        $data = $this->service()->fetchConfig();

        return ApiResponse::json($data);
    }

    public function postAmountCredits($id)
    {
        $input = Request::all();

        $data = $this->service()->editAmountCredits($id, $input);

        return ApiResponse::json($data);
    }

    public function getPaymentMethods()
    {
        $data = $this->service()->getPaymentMethods();

        return ApiResponse::json($data);
    }

    public function getCheckoutPreferences()
    {
        $input = Request::all();

        $data = $this->service()->getCheckoutPreferences($input);

        return ApiResponse::json($data);
    }

    public function patchMerchantBeneficiaryCode()
    {
        $data = $this->service()->patchMerchantBeneficiaryCode();

        return ApiResponse::json($data);
    }

    public function getMerchantBeneficiaryFile()
    {
        $data = $this->service()->getMerchantBeneficiaryFile();

        return ApiResponse::json($data);
    }

    public function getMerchantWebhooks($id)
    {
        $data = $this->service()->getMerchantWebhooks($id);

        return ApiResponse::json($data);
    }

    public function postWebhook()
    {
        $input = Request::all();

        $data = $this->service()->createWebhook($input);

        return ApiResponse::json($data);
    }

    public function putWebhook($id)
    {
        $input = Request::all();

        $data = $this->service()->editWebhook($id, $input);

        return ApiResponse::json($data);
    }

    public function getWebhook($id)
    {
        $data = $this->service()->getWebhook($id);

        return ApiResponse::json($data);
    }

    public function getWebhooks()
    {
        $data = $this->service()->getWebhooks();

        return ApiResponse::json($data);
    }

    public function postMerchantBeneficiaryFile()
    {
        $input = Request::all();

        $data = $this->service()->postMerchantBeneficiaryFile($input);

        return ApiResponse::json($data);
    }

    public function getCheckout()
    {
        $input = Request::all();

        $prefs = $this->service()->getCheckoutPreferences($input);

        $data = $this->getCheckoutCommon($input);

        $data['preferences'] = $prefs;

        return ApiResponse::generateResponse($data);
    }

    public function getCheckoutPublic()
    {
        $input = Request::all();

        $data = $this->getCheckoutCommon($input);

        return \View::make('checkout.checkout')
                    ->with($data);
    }

    public function getPublicEntityReport($entity)
    {
        $input = Request::all();

        $report = new Report\Types\BasicEntityReport($entity);

        return $report->getReport($input);
    }

    public function getPublicEntityReportUrl($entity)
    {
        $input = Request::all();

        $report = new Report\Types\BasicEntityReport($entity);

        $data = $report->getReportUrl($input);

        return ApiResponse::json($data);
    }

    public function getBrokerTransactionReport()
    {
        $input = Request::all();

        $report = new Report\Types\BrokerTransactionReport(E::TRANSACTION);

        return $report->getReport($input);
    }

    public function getDSPTransactionReport()
    {
        $input = Request::all();

        $report = new Report\Types\DSPTransactionReport(E::TRANSACTION);

        $data = $report->getReport($input);

        return ApiResponse::json($data);
    }

    public function getRPPOrderReport()
    {
        $input = Request::all();

        $report = new Report\Types\RPPOrderReport(E::ORDER);

        $data = $report->getReportUrl($input);

        return ApiResponse::json($data);
    }

    public function getIrctcRefundReport()
    {
        $input = Request::all();

        $report = new Report\Types\IrctcRefundReport(E::REFUND);

        $data = $report->getReport($input);

        return ApiResponse::json($data);
    }

    public function getInvoiceReport()
    {
        $input = Request::all();

        return (new Report\Types\InvoiceReport)->getInvoiceReport($input);
    }

    /**
     * Sends an email to every merchant
     * with all transactions from yesterday
     */
    public function sendDailyReport()
    {
        $input = Request::all();

        $response = $this->service()->sendDailyReportForAllMerchants($input);

        return ApiResponse::json($response);
    }

    public function getDummyFeatures()
    {
        $input = Request::all();

        return ApiResponse::json($input);
    }

    public function postMerchantsNotifyHoliday()
    {
        $input = Request::all();

        $data = $this->service()->notifyMerchantsHoliday($input);

        return ApiResponse::json($data);
    }

    public function updateMethodsForMultipleMerchants()
    {
        $input = Request::all();

        $data = $this->service()->updateMethodsForMultipleMerchants($input);

        return ApiResponse::json($data);
    }

    public function updateHoldFundsForMultipleMerchants()
    {
        $input = Request::all();

        $data = $this->service()->updateHoldFundsForMultipleMerchants($input);

        return ApiResponse::json($data);
    }

    public function updateBankAccountForMultipleMerchants()
    {
        $input = Request::all();

        $data = $this->service()->updateBankAccountForMultipleMerchants($input);

        return ApiResponse::json($data);
    }

    public function getOffers(string $mid)
    {
        $data = $this->service()->getOffers($mid);

        return ApiResponse::json($data);
    }

    public function updateMerchantFeatures($id)
    {
        $input = Request::all();

        $data = $this->service()->addOrRemoveMerchantFeatures($input);

        return ApiResponse::json($data);
    }

    public function getMerchantFeatures($id)
    {
        $data = $this->service()->getMerchantFeatures();

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


    // Activation Form Handlers
    public function getActivationDetails()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->fetchMerchantDetails();

        return ApiResponse::json($response);
    }

    public function postUploadActivationFile()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->uploadActivationFileMerchant($input);

        return ApiResponse::json($response);
    }

    public function postUploadActivationFileAdmin($merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->uploadActivationFileAdmin($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function getActivationFiles(string $id)
    {
        $response = $this->service(E::MERCHANT_DETAIL)->fetchActivationFiles($id);

        return ApiResponse::json($response);
    }

    public function postSaveActivationDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->saveMerchantDetails($input);

        return ApiResponse::json($response);
    }

    public function putEditMerchantDetailsAfterLock($id)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->editMerchantDetails($id, $input);

        return ApiResponse::json($response);
    }

    public function postMerchantDetailMigrate()
    {
        $input = Request::all();

        $response = (new Detail\FileMigration)->migrateMerchantDocuments($input);

        return ApiResponse::json($response);
    }

    // == / Activation Form Handlers ==

    public function getDummyAccount()
    {
        $response = ['id' => $this->ba->getMerchant()->getId()];

        return ApiResponse::json($response);
    }

    public function getUsers($id)
    {
        $data = $this->service()->getUsers($id);

        return ApiResponse::json($data);
    }

    public function getGSTDetails()
    {
        $response = $this->service()->getGSTDetails();

        return ApiResponse::json($response);
    }

    public function editGSTDetails()
    {
        $input = Request::all();

        $response = $this->service()->editGSTDetails($input);

        return ApiResponse::json($response);
    }

    public function getReferredMerchants()
    {
        $response = $this->service()->fetchReferredMerchants();

        return ApiResponse::json($response);
    }

    public function getTags($id)
    {
        $response = $this->service()->getTags($id);

        return ApiResponse::json($response);
    }

    public function addTags($id)
    {
        $input = Request::all();

        $response = $this->service()->addTags($id, $input);

        return ApiResponse::json($response);
    }

    public function deleteTag($id, $tagName)
    {
        $response = $this->service()->deleteTag($id, $tagName);

        return ApiResponse::json($response);
    }

    public function markGratisTransactionPostpaid()
    {
        $input = Request::all();

        $response = $this->service()->markGratisTransactionPostpaid($input);

        return ApiResponse::json($response);
    }

    public function postAnalytics()
    {
        $input = Request::all();

        $response = $this->service()->fetchAnalytics($input);

        return ApiResponse::json($response);
    }

    public function getMerchantDetails()
    {
        $response = $this->service()->getMerchantDetails();

        return ApiResponse::json($response);
    }

    /**
     * Sends OAuth notification mails. This route is called by auth service.
     *
     * @param string $type - Type of event, e.g. app_authorized (When merchant
     *                       authorizes an application we send the merchant a mail)
     *
     * @return ApiResponse
     */
    public function sendOAuthNotification(string $type)
    {
        $input = Request::all();

        $response = (new Merchant\Service)->sendOAuthMail($input, $type);

        return ApiResponse::json($response);
    }

    public function getPublicGatewayDowntimeData()
    {
        $data = $this->service(Entity::GATEWAY_DOWNTIME)->getDowntimeDataForMerchant();

        return ApiResponse::json($data);
    }

    public function createBatches($id)
    {
        $input = Request::all();

        $response = (new Merchant\Service)->createBatches($id, $input);

        return ApiResponse::json($response);
    }
}
