<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Key;
use RZP\Models\Report;
use RZP\Models\Gateway;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Partner;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\AccessMap;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\InheritanceMap;

class MerchantController extends Controller
{
    public function postCreateMerchant()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function syncStakeholderFromMerchant()
    {
        $input = Request::all();

        $data = $this->service()->syncStakeholderFromMerchant($input);

        return ApiResponse::json($data);
    }

    public function getMerchantDetailsForAccountService(string $accountId)
    {
        $data = $this->service()->getMerchantDetailsForAccountService($accountId);

        return ApiResponse::json($data);
    }

    public function postCreateSubMerchant()
    {
        $input = Request::all();

        $data = $this->service()->createSubMerchant($input);

        return ApiResponse::json($data);
    }

    public function getMerchantDataForSegment()
    {
        $data = $this->service()->getMerchantDataForSegmentAnalysis();

        return ApiResponse::json($data);
    }

    public function postCreateSubMerchantViaBatch()
    {
        $input = Request::all();

        $data = $this->service()->createSubMerchantViaBatch($input);

        return ApiResponse::json($data);
    }

    public function updateHdfcDebitEmiPaymentMethods()
    {
        $input = Request::all();

        $data = $this->service()->updateHdfcDebitEmiPaymentMethods($input);

        return ApiResponse::json($data);
    }

    public function bulkOnboardSubMerchantViaBatch()
    {
        $input = Request::all();

        $data = $this->service()->bulkOnboardSubMerchantViaBatch($input);

        return ApiResponse::json($data);
    }

    public function createLinkedAccount()
    {
        $input = Request::all();

        $response = $this->service()->createLinkedAccount($input);

        return ApiResponse::json($response);
    }

    public function postSwitchProductMerchant()
    {
        $this->service()->switchProductMerchant();

        return ApiResponse::json([]);
    }

    public function getBillingLabelSuggestions()
    {
        $data = $this->service()->getBillingLabelSuggestions();

        return ApiResponse::json($data);
    }

    public function patchMerchantBillingLabelAndDba()
    {
        $input = Request::all();

        $data = $this->service()->patchMerchantBillingLabelAndDba($input);

        return ApiResponse::json($data);
    }

    public function putMerchant($id)
    {
        // this is temporary logging: to get all admins who uses this route
        $this->trace->info(TraceCode::MERCHANT_EDIT_REQUEST, []);

        $input = Request::all();

        $data = $this->service()->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function putMerchantRiskAttributes($id)
    {
        $input = Request::all();

        $data = $this->service()->editRiskAttributes($id, $input);

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

    public function getUserStatusForEmailUpdateSelfServe()
    {
        $input = Request::all();

        $data = $this->service()->getUserStatusForEmailUpdateSelfServe($input);

        return ApiResponse::json($data);
    }

    public function putEditEmailAndTransferOwnershipToEmailUser()
    {
        $input = Request::all();

        $data = $this->service()->editMerchantEmailAndTransferOwnershipToEmailUser($input);

        return ApiResponse::json($data);
    }

    public function putCreateNewUserAndTransferOwnerShip()
    {
        $input = Request::all();

        $data = $this->service()->editMerchantEmailCreateNewUserAndTransferOwnerShip($input);

        return ApiResponse::json($data);
    }

    /**
     *  This corrects mismatch in owners of different products
     *  Don't use lightly
     */
    public function correctMerchantOwnerForBanking($id)
    {
        $input = Request::all();

        $data = $this->service()->correctMerchantOwnerForBanking($id, $input);

        return ApiResponse::json($data);
    }

    public function updateLinkedAccountMerchantEmail()
    {
        $input = Request::all();

        $data = $this->service()->editLinkedAccountEmail($input);

        return ApiResponse::json($data);
    }

    public function putMerchantConfig()
    {
        $input = Request::all();

        $data = $this->service()->editConfig($input);

        return ApiResponse::json($data);
    }

    public function getPaymentFailureAnalysis()
    {
        $input = Request::all();

        $data = $this->service()->getPaymentFailureAnalysis($input);

        return ApiResponse::json($data);
    }

    public function postMerchantEmail2fa()
    {
        $input = Request::all();

        $data = $this->service()->editEmail2FA($input);

        return ApiResponse::json($data);
    }

    public function postMerchantConfigLogo()
    {
        if (Request::hasFile('logo'))
        {
            $input['logo'] = Request::file('logo');

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

    public function onboardMerchantOnGateway($id)
    {
        $input = Request::all();

        $data = $this->service()->onboardMerchant($id, $input);

        return ApiResponse::json($data);
    }

    public function getMerchants()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getKeySecret($keyId)
    {
        $data = (new Key\Core)->getKeySecret($keyId);

        return ApiResponse::json($data);
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

    public function proxyGetPricingPlan()
    {
        $data = $this->service()->proxyGetPricingPlan();

        return ApiResponse::json($data);
    }

    public function postCreateTerminal($id)
    {
        $input = Request::all();

        $data = $this->service(E::TERMINAL)->createTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function postCreateTerminalWithId($merchantId)
    {
        $input = Request::all();

        $data = $this->service(E::TERMINAL)->createTerminalWithId($merchantId, $input);

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

    // This is used when merchant dashboard fetches terminals via proxy auth
    public function proxyGetTerminals()
    {
        $input = Request::all();

        $mid =  $this->ba->getMerchant()->getId();

        $data = $this->service(E::TERMINAL)->proxyGetTerminals($mid, $input);

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

        $data = $this->service()->action($id, $input, $input[Merchant\Constants::USE_WORKFLOWS] ?? true);

        return ApiResponse::json($data);
    }

    public function postBankAccount($id = null)
    {
        $input = Request::all();

        $data = $this->service()->addBankAccount($id, $input);

        return ApiResponse::json($data);
    }


    public function putBankAccountUpdatePostPennyTestingWorkflow()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountUpdatePostPennyTestingWorkflow($input);

        return ApiResponse::json($data);
    }

    public function putBankAccountUpdate()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountUpdate($input);

        return ApiResponse::json($data);
    }

    public function putBankAccount($id)
    {
        $input = Request::all();

        $data = $this->service()->editBankAccount($id, $input);

        return ApiResponse::json($data);
    }

    public function getBankAccountChangeStatus($id)
    {
        $input = Request::all();

        $data = $this->service()->getBankAccountChangeStatus($id, $input);

        return ApiResponse::json($data);
    }

    public function getProductInternationalStatus()
    {
        $response = $this->service(E::MERCHANT)->getProductInternationalStatus();

        return ApiResponse::json($response);
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

    public function getDisabledBanks()
    {
        $data = $this->service(E::MERCHANT_DETAIL)->getDisabledBanks();

        return ApiResponse::json($data);
    }

    public function getBanks($id)
    {
        $data = $this->service()->getBanks($id);

        return ApiResponse::json($data);
    }

    public function getOrg(string $id)
    {
        $data = $this->service()->getOrgDetails($id);

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

    public function editMethods()
    {
        $input = Request::all();

        $data = $this->service()->editMethods($input);

        return ApiResponse::json($data);
    }

    public function editMerchantMethods($mid)
    {
        $input = Request::all();

        $data = $this->service()->editMerchantMethods($mid, $input);

        return ApiResponse::json($data);
    }

    public function getAccountBalance()
    {
        $data = $this->service()->fetchBalance();

        return ApiResponse::json($data);
    }

    public function getAccountBalances()
    {
        $input = Request::all();

        $data = $this->service()->fetchAccountBalances($input);

        return ApiResponse::json($data);
    }

    public function getAccountBalancesByAccountNumberSuffix($accountSuffix)
    {
        $input = Request::all();
        $input['account_number_suffix'] = $accountSuffix;

        $data = $this->service()->fetchAccountBalances($input);

        return ApiResponse::json($data);
    }

    public function getPrimaryBalance()
    {
        $data = $this->service()->getPrimaryBalance();

        return ApiResponse::json($data);
    }

    public function getBalanceByMerchantId(string $merchantId)
    {
        $data = $this->service()->fetchBalance($merchantId);

        return ApiResponse::json($data);
    }

    public function updateLockedBalance(string $balanceId)
    {
        $input = Request::all();

        $data = $this->service()->updateLockedBalance($input, $balanceId);

        return ApiResponse::json($data);
    }

    // This is on proxy Auth
    public function getAccountConfig()
    {
        $data = $this->service()->fetchConfig();

        return ApiResponse::json($data);
    }

    public function getAccountConfigInternal()
    {
        $data = $this->service()->fetchConfig($isInternal = true);

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

    public function getInternalCheckoutPreferences($merchantId)
    {
        $data = $this->service()->getInternalCheckoutPreferences($merchantId);

        return ApiResponse::json($data);
    }

    // get methods which are disabled for automatic enablement based on the category of the merchant
    public function getAutoDisabledMethods($merchantId)
    {
        $data = $this->service()->getAutoDisabledMethods($merchantId);

        return ApiResponse::json($data);
    }

    public function patchMerchantBeneficiaryCode()
    {
        $data = $this->service()->patchMerchantBeneficiaryCode();

        return ApiResponse::json($data);
    }

    public function getMerchantBeneficiary($channel)
    {
        $data = $this->service()->getMerchantBeneficiary($this->input, $channel);

        return ApiResponse::json($data);
    }

    public function toggleInternational()
    {
        $data = $this->service()->toggleInternational($this->input);

        return ApiResponse::json($data);
    }

    public function postMerchantBeneficiary($channel)
    {
        $input = Request::all();

        $data = $this->service()->postMerchantBeneficiary($input, $channel);

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

    public function generateBankingInvoice()
    {
        $input = Request::all();

        return $this->service('merchant_invoice')->requestBankingInvoice($input);
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

    public function getDummyRazorX()
    {
        $response = $this->service()->getDummyRazorX();

        return ApiResponse::json($response);
    }

    public function postMerchantsNotifyHoliday()
    {
        $input = Request::all();

        $data = $this->service()->notifyMerchantsHoliday($input);

        return ApiResponse::json($data);
    }

    /**
     * Input JSON sample:
     * {
     *   "methods": {
     *     "credit_card": 1,
     *     "debit_card": 0,
     *     "upi": 1,
     *     "emi":0
     *   },
     *   "merchants": ["10000000000000", "ACIg0vIkvgCALm"]
     * }
     *
     * @return mixed
     */
    public function updateMethodsForMultipleMerchants()
    {
        $input = Request::all();

        $data = $this->service()->updateMethodsForMultipleMerchants($input);

        return ApiResponse::json($data);
    }

    public function updateMerchantsBulk()
    {
        $input = Request::all();

        $data = $this->service()->updateMerchantsBulk($input);

        return ApiResponse::json($data);
    }

    public function updateChannelForMultipleMerchants()
    {
        $input = Request::all();

        $data = $this->service()->updateChannelForMultipleMerchants($input);

        return ApiResponse::json($data);
    }

    public function updateBankAccountForMultipleMerchants()
    {
        $input = Request::all();

        $data = $this->service()->updateBankAccountForMultipleMerchants($input);

        return ApiResponse::json($data);
    }

    public function updateMerchantFraudType()
    {
        $input = Request::all();

        $data =  $this->service(E::MERCHANT_DETAIL)->updateMerchantFraudType($input);

        return ApiResponse::json($data);
    }

    public function getOffers(string $mid)
    {
        $data = $this->service()->getOffers($mid);

        return ApiResponse::json($data);
    }

    public function updateMerchantFeatures()
    {
        $input = Request::all();

        $data = $this->service()->addOrRemoveMerchantFeatures($input);

        return ApiResponse::json($data);
    }

    public function getMerchantFeatures()
    {
        $data = $this->service()->getMerchantFeatures();

        return ApiResponse::json($data);
    }

    public function getEarlySettlementPricingForMerchant()
    {
        $data = $this->service()->getEarlySettlementPricingForMerchant();

        return ApiResponse::json($data);
    }

    public function bulkSubmerchantAssign()
    {
        $input = Request::all();

        $response = $this->service()->bulkSubmerchantAssign($input);

        return ApiResponse::json($response);
    }

    public function getScheduledEarlySettlementPricingForMerchant()
    {
        $data = $this->service()->getScheduledEarlySettlementPricingForMerchant();

        return ApiResponse::json($data);
    }

    public function getInstantRefundsPricingForMerchant()
    {
        $data = $this->service()->getInstantRefundsPricingForMerchant();

        return ApiResponse::json($data);
    }

    public function enableScheduledEs()
    {
        $data = $this->service()->enableScheduledEs();

        return ApiResponse::json($data);
    }

    // --------------------- Credits API Handlers -----------------------------------------

    public function postCreateCreditsLog(Credits\Service $service, $id)
    {
        $input = Request::all();

        $data = $service->grantCreditsForMerchant($id, $input);

        return ApiResponse::json($data);
    }

    public function getCreditsLog(Credits\Service $service, $id)
    {
        $data = $service->fetchCreditsLog($id);

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

    public function bulkCreateMerchantCredits(Credits\Service $service)
    {
        $input = Request::all();

        $response = $service->bulkCreateCredits($input);

        return ApiResponse::json($response);
    }

    public function bulkCreateMerchantCreditsBatch(Credits\Service $service)
    {
        $input = Request::all();

        $response = $service->bulkCreateCreditsBatch($input);

        return ApiResponse::json($response);
    }

    public function getCreditsBalancesOfMerchantForProduct(Credits\Balance\Service $service, $product)
    {
        $response = $service->getCreditsBalancesOfMerchantForProduct($product);

        return ApiResponse::json($response);
    }

// --------------------- End Credits API Handlers -----------------------------------------

    public function getBvsValidationArtefactDetails(string $merchantId, string $validationArtefact)
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getBvsValidationArtefactDetails(
            $merchantId, $validationArtefact
        );

        return ApiResponse::json($response);
    }

    // Activation Form Handlers
    public function getActivationDetails()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->fetchMerchantDetails();

        return ApiResponse::json($response);
    }

    public function isAdminLoggedInAsMerchant()
    {
        $response = $this->service(E::MERCHANT)->isAdminLoggedInAsMerchant();

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

        $response = $this->service(E::MERCHANT_DETAIL)->saveMerchantDetailsForActivation($input);

        return ApiResponse::json($response);
    }

    public function putEditMerchantDetailsAfterLock($id)
    {
        $input = Request::all();

        // this is temporary logging: to get all admins who uses this route
        $this->trace->info(TraceCode::MERCHANT_DETAILS_EDIT_REQUEST, []);

        $response = $this->service(E::MERCHANT_DETAIL)->editMerchantDetails($id, $input);

        return ApiResponse::json($response);
    }

    public function uploadMerchant()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->uploadMerchant($input);

        return ApiResponse::json($response);
    }

    public function putEditMerchantDetailsAfterLockPartner($id)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->editMerchantDetailsByPartner($id, $input);

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

    public function getUsers()
    {
        $data = $this->service()->getUsers();

        return ApiResponse::json($data);
    }

    public function getInternalUsers($merchantId)
    {
        $headers = Request::header();

        $product = $headers['x-product-name'][0];

        $data = $this->service()->getInternalUsers($merchantId, $product);

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

    public function getGstinSelfServeStatus()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getGstinSelfServeStatus();

        return ApiResponse::json($response);
    }

    public function postGstinSelfServe()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateGstinSelfServe($input);

        return ApiResponse::json($response);
    }

    public function postGstinUpdateWorkflow()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateMerchantGstinDetailsOnSelfServeWorkflowApprove($input);

        return ApiResponse::json($response);
    }

    public function updateActivationArchive(string $id)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateActivationArchive($id, $input);

        return ApiResponse::json($response);
    }

    public function updateActivationStatus(string $id)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateActivationStatus($id, $input);

        return ApiResponse::json($response);
    }

    public function updateActivationStatusPartner($id)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateActivationStatusByPartner($id, $input);

        return ApiResponse::json($response);
    }

    public function getActivationStatusChangeLog(string $id)
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getActivationStatusChangeLog($id);

        return ApiResponse::json($response);
    }

    public function updateWebsiteDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateWebsiteDetails($input);

        return ApiResponse::json($response);
    }

    public function putBusinessWebsiteUpdatePostWorkflow()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->putBusinessWebsiteUpdatePostWorkflow($input);

        return ApiResponse::json($response);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getWebsiteStatus()
    {
        $response = $this->service(E::MERCHANT)->getWebsiteStatus();

        return ApiResponse::json($response);
    }

    public function getBusinessCategories()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getBusinessCategories();

        return ApiResponse::json($response);
    }


    /**
     * /**
     *
     * Gets Business Category and SubCategory list
     * based on a string entered by user
     *
     *  @return mixed
     */
    public function getBusinessDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->getBusinessDetails($input);

        return ApiResponse::json($response);
    }

    /**
     * Gets list of companies and their matadata
     * based on a string entered by user
     *
     *  @return mixed
     */
    public function getCompanySearchList()
    {
        $input = Request::all();

        $results = $this->service(E::MERCHANT_DETAIL)->getCompanySearchList($input);

        return ApiResponse::json($results);
    }

    /**
     * Gets list of gst numbers associate to personal pan and company pan of merchant
     *
     *  @return mixed
     */
    public function getGstInList()
    {
        $input = Request::all();

        $results = $this->service(E::MERCHANT_DETAIL)->getGstInList($input);

        return ApiResponse::json($results);
    }
    /**
     * Returns clarification reason against each field
     *
     * @return mixed
     */
    public function getNeedsClarificationReasons()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getNeedsClarificationReasons();

        return ApiResponse::json($response);
    }

    public function updateKeyAccess(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updateKeyAccess($id, $input);

        return ApiResponse::json($response);
    }

    public function getRejectionReasons()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getRejectionReasons();

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

    public function getAssociatedAccounts(string $merchantId)
    {
        $data = $this->service()->fetchAssociatedAccounts($merchantId);

        return ApiResponse::json($data);
    }

    public function getAffiliatedPartners(string $merchantId)
    {
        $data = $this->service()->fetchAffiliatedPartners($merchantId);

        return ApiResponse::json($data);
    }

    public function addTags($id)
    {
        $input = Request::all();

        $response = $this->service()->addTags($id, $input, true);

        return ApiResponse::json($response);
    }

    public function deleteTag($id, $tagName)
    {
        $response = $this->service()->deleteTag($id, $tagName);

        return ApiResponse::json($response);
    }

    public function bulkTagMerchants()
    {
        RuntimeManager::setTimeLimit(1800);

        RuntimeManager::setMemoryLimit("1024M");

        $input = Request::all();

        $response = $this->service()->bulkTag($input);

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

    public function patchMerchantDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->patchMerchantDetails($input);

        return ApiResponse::json($response);
    }

    /**
     * returns merchant info along with merchant_details, to be used by internal apps
     */
    public function internalGetMerchant(string $merchantId)
    {
        $response = $this->service()->internalGetMerchant($merchantId);

        return ApiResponse::json($response);
    }

    public function internalGetMerchantSubmissionDate($merchantId)
    {
        $response = $this->service()->internalGetMerchantSubmissionDate($merchantId);

        return ApiResponse::json($response);
    }

    public function internalGetMerchantRejectionReasons(string $merchantId)
    {
        $response = $this->service()->getRejectionReasons($merchantId);

        return ApiResponse::json($response);
    }

    /**
     * returns merchant name and website only, to be used by internal apps
     */
    public function getMerchantBulk()
    {
        $input = Request::all();

        $response = $this->service()->getMerchantBulk($input);

        return ApiResponse::json($response);
    }

    public function sendMerchantEmail($id)
    {
        $input = Request::all();

        $response = $this->service()->sendMerchantEmail($id, $input);

        return ApiResponse::json($response);
    }

    /**
     * Bulk updates merchant attributes against given CSV input(refer service method).
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkEditMerchantAttributes()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->bulkEditMerchantAttributes($this->input);

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

        $response = (new Merchant\Service)->sendOAuthNotification($input, $type);

        return ApiResponse::json($response);
    }

    public function getPublicGatewayDowntimeData()
    {
        $data = $this->service(E::GATEWAY_DOWNTIME)->getDowntimeDataForMerchant();

        return ApiResponse::json($data);
    }

    public function createBatches($id)
    {
        $input = Request::all();

        $response = (new Merchant\Service)->createBatches($id, $input);

        return ApiResponse::json($response);
    }

    public function getPreSignupDetails()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getPreSignupDetails();

        return $response;
    }

    public function putPreSignupDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->editPreSignupDetails($input);

        return $response;
    }

    public function postSubMerchantUser($merchantId)
    {
        $input = Request::all();

        $response = $this->service()->createSubMerchantUser($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function sendPayoutMail()
    {
        $input = Request::all();

        $response = $this->service()->sendPayoutMailForMultipleMerchants($input);

        return ApiResponse::json($response);
    }

    /**
     * Gets connected applications against given merchant id.
     * Returns serialized collection of Merchant\AccessMap\Entity.
     */
    public function getConnectedApplications(string $merchantId)
    {
        $input = Request::all();

        $response = (new AccessMap\Service)->getConnectedApplications($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function postMapOAuthApplication(string $merchantId)
    {
        $input = Request::all();

        $response = (new AccessMap\Service)
                        ->mapOAuthApplication($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function deleteMapOAuthApplication(string $merchantId, string $appId)
    {
        $response = (new AccessMap\Service)
                        ->deleteMapOAuthApplication($merchantId, $appId);

        return ApiResponse::json($response);
    }

    public function enableEmiMerchantSubvention(string $id, string $emiPlanId)
    {
        $input = Request::all();

        $data = $this->service()->enableEmiMerchantSubvention($id, $emiPlanId, $input);

        return ApiResponse::json($data);
    }

    public function bulkAssignReviewer()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->bulkAssignReviewer($input);

        return ApiResponse::json($response);
    }

    public function merchantsMtuUpdate()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->merchantsMtuUpdate($input);

        return ApiResponse::json($response);
    }

    public function getMerchantActivationReviewers()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getMerchantActivationReviewers();

        return ApiResponse::json($response);
    }

    public function updateMerchantAccessMapFromTokens()
    {
        $data = (new AccessMap\Service)->updateMapFromTokens();

        return ApiResponse::json($data);
    }

    /**
     * @param string $merchantId
     *
     * @return \Illuminate\Http\Response
     */
    public function createPartnerAccessMap(string $merchantId)
    {
        $response = $this->service()->createPartnerAccessMap($merchantId);

        return ApiResponse::json($response);
    }

    public function createPartnerSubmerchantMap()
    {
        $input = Request::all();

        $response = $this->service()->createPartnerSubmerchantMap($input);

        return ApiResponse::json($response);
    }

    public function fetchPartnerIntent()
    {
        $response = $this->service()->fetchPartnerIntent();

        return ApiResponse::json($response);
    }

    public function updatePartnerIntent()
    {
        $input = Request::all();

        $response = $this->service()->updatePartnerIntent($input);

        return ApiResponse::json($response);
    }

    /**
     * @param string $merchantId
     *
     * @return \Illuminate\Http\Response
     */
    public function deletePartnerAccessMap(string $merchantId)
    {
        $this->service()->deletePartnerAccessMap($merchantId);

        return ApiResponse::json([], 204);
    }

    public function updatePartnerAccessMap(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service()->updatePartnerAccessMap($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function getSubmerchant(string $submerchantId)
    {
        $input = Request::all();

        $response = $this->service()->getSubmerchant($submerchantId, $input);

        return ApiResponse::json($response);
    }

    public function listSubmerchants()
    {
        $input = Request::all();

        $response = $this->service()->listSubmerchants($input);

        return ApiResponse::json($response);
    }

    public function updatePartnerType()
    {
        $input = Request::all();

        $response = $this->service()->updatePartnerType($input);

        return ApiResponse::json($response);
    }

    public function backFillMerchantApplications()
    {
        $input = Request::all();

        $response = $this->service()->backFillMerchantApplications($input);

        return ApiResponse::json($response);
    }

    public function backFillReferredApplication()
    {
        $input = Request::all();

        $response = $this->service()->backFillReferredApplication($input);

        return ApiResponse::json($response);
    }

    public function postMerchantBeneficiaryThroughApi($channel)
    {
        $input = Request::all();

        $data = $this->service()->registerBeneficiariesThroughApi($input, $channel);

        return ApiResponse::json($data);
    }

    public function updateLinkedAccountConfig()
    {
        $input = Request::all();

        $data = $this->service()->updateLinkedAccountConfig($input);

        return ApiResponse::json($data);
    }

    public function saveInstantActivationDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->saveInstantActivationDetails($input);

        return ApiResponse::json($response);
    }

    public function getRazorxTreatment($featureFlag)
    {
        $response = $this->service(E::MERCHANT)->getRazorxTreatment($featureFlag);

        return ApiResponse::json($response);
    }

    public function getRazorxTreatmentInBulk()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT)->getRazorxTreatmentInBulk($input);

        return ApiResponse::json($response);
    }

    /**
     * Input JSON sample:
     * {
     *   "pricing_plan_id": "1AXludj60w4pSp",
     *   "merchant_ids": ["10000000000000", "100000Razorpay"]
     * }
     *
     * @return mixed
     */
    public function bulkAssignPricing()
    {
        $input = Request::all();

        $response = $this->service()->bulkAssignPricing($input);

        return ApiResponse::json($response);
    }

    public function bulkUpdatePricingPlanOnEligibilityCron()
    {
        $input = Request::all();

        $data = $this->service()->fetchEligiblePricingPlansAndUpdateCorporatePricingRule($input['count']);

        return ApiResponse::json($data);
    }

    /**
     * Input JSON sample:
     * {
     *   "schedule": {
     *     "schedule_id": "40000000000000",
     *     "type": "settlement"
     *   },
     *   "merchant_ids": ["10000000000000", "ACIg0vIkvgCALm"]
     * }
     *
     * @return mixed
     */
    public function bulkAssignSchedule()
    {
        $input = Request::all();

        $response = $this->service()->bulkAssignSchedule($input);

        return ApiResponse::json($response);
    }

    public function submitSupportCallRequest()
    {
        $response = $this->service()->submitSupportCallRequest($this->input);

        return ApiResponse::json($response);
    }

    public function canSubmitSupportCallRequest()
    {
        $response = [
            'response'  =>  $this->service()->canSubmitSupportCallRequest(),
        ];

        return ApiResponse::json($response);
    }

    public function getMerchantSupportOptionFlags()
    {
        $response = $this->service()->getMerchantSupportOptionFlags();

        return ApiResponse::json($response);
    }
    /**
     * Syncs merchant entity between mysql and elastic search
     *
     * This api sync only frequently changing attributes.
     *
     * @return mixed
     */
    public function syncMerchantsToEs()
    {
        $input = Request::all();

        $response = $this->service()->syncMerchantsToEs($input);

        return ApiResponse::json($response);
    }

    public function bulkRegenerateBalanceIds()
    {
        $input = Request::all();

        $response = $this->service()->bulkRegenerateBalanceIds($input);

        return ApiResponse::json($response);
    }

    public function getMerchantPartnerStatus()
    {
        $input = Request::all();

        $data = $this->service()->fetchMerchantPartnerStatus($input);

        return ApiResponse::json($data);
    }

    /**
     * Used when partner wants to send the link to submerchant for password setting.
     *
     * @param string $id submerchant id.
     *
     * @return mixed
     */
    public function sendSubmerchantPasswordResetLink(string $id)
    {
        $data = $this->service()->sendSubmerchantPasswordResetLink($id);

        return ApiResponse::json($data);
    }

    public function resetSettlementSchedule()
    {
        $input = Request::all();

        $response = $this->service()->resetSettlementSchedule($input);

        return ApiResponse::json($response);
    }

    public function change2faSetting()
    {
        $input = Request::all();

        $response = $this->service()->change2faSetting($input);

        return ApiResponse::json($response);
    }

    public function applyRestrictedSettings()
    {
        $input = Request::all();

        $response = $this->service()->applyRestrictedSettings($input);

        return ApiResponse::json($response);
    }

    public function deleteSuspendedMerchantsFromMailingList()
    {
        $input = Request::all();

        $this->service()->removeSuspendedMerchantsFromMailingList($input);
    }

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function fetchReferral()
    {
        $response = $this->service()->fetchReferral();

        return ApiResponse::json($response);
    }

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function createReferral()
    {
        $response = $this->service()->createReferral();

        return ApiResponse::json($response);
    }

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function putAdditionalWebsite(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->putAdditionalWebsite($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function deleteAdditionalWebsites(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->deleteAdditionalWebsites($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function getInheritanceParent(string $merchantId)
    {
        $response = $this->service(E::MERCHANT_INHERITANCE_MAP)->getInheritanceParent($merchantId);

        return ApiResponse::json($response->toArrayPublic());
    }

    public function postInheritanceParent(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INHERITANCE_MAP)->postInheritanceParent($merchantId, $input);

        return ApiResponse::json($response->toArrayPublic());
    }

    public function deleteInheritanceParent(string $merchantId)
    {
        $response = $this->service(E::MERCHANT_INHERITANCE_MAP)->deleteInheritanceParent($merchantId);

        return ApiResponse::json($response);
    }

    public function postInheritanceParentBulk()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INHERITANCE_MAP)->postInheritanceParentBulk($input);

        return ApiResponse::json($response);
    }

    public function migrationBankingVAs()
    {
        $input = Request::all();

        $response = $this->service()->migrationBankingVAs($input);

        return ApiResponse::json($response);
    }


    /**
     * @return mixed
     */
    public function merchantsBulkUpdate()
    {
        $input = Request::all();

        $response = $this->service()->merchantsBulkUpdate($input);

        return ApiResponse::json($response);
    }

    /**
     * @return mixed
     */
    public function getBatchActionEntities()
    {
        $response = $this->service()->getBatchActionEntities();

        return ApiResponse::json($response);
    }

    /**
     * @return mixed
     */
    public function getBatchActions()
    {
        $response = $this->service()->getBatchActions();

        return ApiResponse::json($response);
    }

    public function requestInternationalProduct()
    {
        $input = Request::all();

        $response = $this->service()->requestInternationalProduct($input);

        return ApiResponse::json($response);
    }

    /**
     * @return mixed
     */
    public function retryPennyTestingCron()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->retryPennyTestingCron();

        return ApiResponse::json($response);
    }

    public function getGlobalMerchantConfigs($mid)
    {
        $response = $this->service()->getGlobalMerchantConfigs($mid);

        return ApiResponse::json($response);
    }

    /**
     * This API is to capture merchant preferences.
     * Preferences are organized as Group(module), Type(key) & Value.
     * This API does either create or update of preferences.
     * Preferences are matched by group & type.
     * E.g. Use cases: In X, merchant preferences are used for App suggestions
     * Also it can help taking inputs required for lead scoring.
     * @param string $group
     * @return mixed
     */
    public function postMerchantPreferences(string $group)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_ATTRIBUTE)->upsert($group, $input);

        return ApiResponse::json($response);
    }

    /**
     *This is clearly a hack which was done just for nitro, since it was really really required to store
     * this preference in the merchant attributes and the post merchant preferences was unwrapping the array being sent
     * Spent fair time on debugging, but DeADLiNe 🤷‍♂️
     */
    public function postMerchantPreferencesNitroHack()
    {
        $input = Request::all();
        $response = $this->service(E::MERCHANT_ATTRIBUTE)->upsertPreferencesNitroHack($input);
        return ApiResponse::json($response);
    }

    /**
     * Get Merchant preferences by group & type
     * @param string $group
     * @param string|null $type
     * @return mixed
     */
    public function getMerchantPreferences(string $group, string $type = null)
    {
        $response = $this->service(E::MERCHANT_ATTRIBUTE)->getPreferencesByGroupAndType($group, $type);

        return ApiResponse::json($response);
    }

    public function getPersonalisedMethods()
    {
        $input = Request::all();

        $data = $this->service()->getPersonalisedMethods($input);

        return ApiResponse::json($data);
    }

    public function postVerifyMerchantAttributes(string $verificationType)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->verifyMerchantAttributes($input, $verificationType);

        return ApiResponse::json($response);
    }

    /**
     * See Merchant\Service's bootstrapAccessMapsCacheOfStork function.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bootstrapAccessMapsCacheOfStork()
    {
        $summary = $this->service()->bootstrapAccessMapsCacheOfStork($this->input);

        return ApiResponse::json($summary);
    }

    public function migrateImpersonationGrants()
    {
        $summary = $this->service()->migrateImpersonationGrants($this->input);

        return ApiResponse::json($summary);
    }

    public function postPartnerAccessMapBulk()
    {
        $input = Request::all();

        $response = $this->service()->partnerAccessMapBulkUpsert($input);

        return ApiResponse::json($response);
    }

    public function postSoftLimitBreachOnAutoKYC()
    {
        $response = $this->service()->handleSoftLimitBreachOnAutoKYC();

        return ApiResponse::json($response);
    }

    public function postHardLimitBreachOnAutoKYC()
    {
        $response = $this->service()->handleHardLimitBreachOnAutoKYC();

        return ApiResponse::json($response);
    }

    public function handleAutoKycEscalationCron()
    {
        $response = $this->service()->handleAutoKycEscalationCron();

        return ApiResponse::json($response);
    }

    public function updateMerchantStore()
    {
        $input = Request::all();

        $response = $this->service()->updateMerchantStore($input);

        return ApiResponse::json($response);
    }

    public function fetchMerchantStore()
    {
        $input = Request::all();

        $response = $this->service()->fetchMerchantStore($input);

        return ApiResponse::json($response);
    }

    public function handleReport()
    {
        $input = Request::all();

        $response = $this->service()->handleReport($input);
    }

    public function handleOnboardingEscalationsCron()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_ONBOARDING_ESCALATIONS)->handleOnboardingEscalationsCron($input);

        return ApiResponse::json($response);
    }

    public function fetchOnboardingEscalations()
    {
        $response = $this->service(E::MERCHANT_ONBOARDING_ESCALATIONS)->fetchOnboardingEscalations();

        return ApiResponse::json($response);
    }

    /**
     * This function is called from oauth service.
     * It is responsible for sending banking accounts webhook to pure play partners.
     */
    public function sendBankingAccountsViaWebhook(string $id)
    {
        $response = $this->service()->triggerMerchantBankingAccountsWebhook($id);

        return ApiResponse::json($response);
    }


    public function getRewardsForCheckout()
    {
        $response = $this->service()->getRewardsForCheckout();

        return ApiResponse::json($response);
    }

    public function postInstallAppOnAppStore()
    {
        $input = Request::all();

        $response = $this->service()->installAppOnAppStoreForMerchant($input);

        return ApiResponse::json($response);
    }

    public function getInstalledAppsOnAppStore(string $id)
    {
        $response = $this->service()->getInstalledAppsOnAppStore($id);

        return ApiResponse::json($response);
    }

    public function updateSuggestedMerchantDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateBusinessSuggestedAddressAndPin($input);

        return ApiResponse::json($response);
    }

    public function fetchProductUsedByMerchants()
    {
        $input = Request::all();

        $response = $this->service()->fetchProductUsedByMerchants($input);

        return ApiResponse::json($response);
    }

    public function getAovConfig()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getAovConfig();

        return ApiResponse::json($response);
    }

    public function getMerchantTnc($tncId)
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getMerchantTncById($tncId);

        return ApiResponse::json($response);
    }

    public function postMerchantTnc()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->saveMerchantTnc($input);

        return ApiResponse::json($response);
    }

    public function postMerchantCheckoutDetail()
    {
        $input = Request::all();

        $response = $this->service()->saveMerchantCheckoutDetail($input);

        return ApiResponse::json($response);
    }

    public function getMerchantCheckoutDetail()
    {
        $response = $this->service()->fetchMerchantCheckoutDetail();

        return ApiResponse::json($response);
    }

    public function getMerchantBusinessDetail($merchantId)
    {
        $response = $this->service(E::MERCHANT_BUSINESS_DETAIL)->fetchBusinessDetailsForMerchant($merchantId);

        return ApiResponse::json($response);
    }

    public function postMerchantBusinessDetail($merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_BUSINESS_DETAIL)->saveBusinessDetailsForMerchant($merchantId,$input);

        return ApiResponse::json($response);
    }

    public function createPartnerActivationForPartners()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->createPartnerActivationForPartners($input);

        return ApiResponse::json($response);
    }

    public function getRZPTrustedBadgeDetails()
    {
        $response = $this->service()->getRZPTrustedBadgeDetails();

        return ApiResponse::json($response);
    }

    public function bulkFraudNotify()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_BULK_FRAUD_NOTIFY)->notify($input);

        return ApiResponse::json($response);
    }

    public function healthChecker($checkerType)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_HEALTH_CHECKER)->isLive($input, $checkerType);

        return ApiResponse::json($response);
    }

    public function getMerchantRiskData(string $id)
    {
        $response = $this->service()->getMerchantRiskData($id);

        $statusCode = $response['status'];

        unset($response['status']);

        return ApiResponse::json($response, $statusCode);
    }

    public function healthCheckerPeriodicCron($checkerType)
    {
        $response = $this->service(E::MERCHANT_HEALTH_CHECKER)->periodicCron($checkerType);

        return ApiResponse::json($response);
    }

    public function healthCheckerMilestoneCron($checkerType)
    {
        $response = $this->service(E::MERCHANT_HEALTH_CHECKER)->milestoneCron($checkerType);

        return ApiResponse::json($response);
    }

    public function healthCheckerRiskScoreCron($checkerType)
    {
        $response = $this->service(E::MERCHANT_HEALTH_CHECKER)->riskScoreCron($checkerType);

        return ApiResponse::json($response);
    }

    public function healthCheckerRetryCron($checkerType)
    {
        $response = $this->service(E::MERCHANT_HEALTH_CHECKER)->retryCron($checkerType);

        return ApiResponse::json($response);
    }

    public function healthCheckerReminderCron($checkerType)
    {
        $response = $this->service(E::MERCHANT_HEALTH_CHECKER)->reminderCron($checkerType);

        return ApiResponse::json($response);
    }

    public function fraudCheckerMilestoneCron($category)
    {
        $response = $this->service(E::MERCHANT_FRAUD_CHECKER)->milestoneCron($category);

        return ApiResponse::json($response);
    }

    public function fireHubspotEventFromDashboard()
    {
        $input = Request::all();

        return $this->service()->fireHubspotEventFromDashboard($input);
    }

    public function createSalesforceLeadFromDashboard()
    {
        $input = Request::all();

        return $this->service()->createSalesforceLeadFromDashboard($input);
    }

    public function handleMerchantActionNotificationCron()
    {
        $response = $this->service()->handleMerchantActionNotificationCron();

        return ApiResponse::json($response);
    }

    public function completeSubmerchantOnboarding($submerchantId)
    {
        $input = Request::all();

        $response = $this->service()->completeSubmerchantOnboarding($submerchantId, $input);

        return ApiResponse::json($response);
    }

    public function getPurposeCodeDetails()
    {
        $data = $this->service()->getPurposeCodeDetails();

        return ApiResponse::json($data);
    }

    public function patchMerchantPurposeCode()
    {
        $input = Request::all();

        $response = $this->service()->patchMerchantPurposeCode($input);

        return ApiResponse::json($response);
    }

    public function postSaveBusinessWebsite(string $urlType)
    {
        $input = Request::all();

        $this->service(E::MERCHANT_DETAIL)->postSaveBusinessWebsite($urlType, $input);

        return ApiResponse::json([]);
    }

    /**
     * @return mixed
     */
    public function getWebsiteSelfServeWorkflowDetails()
    {
        $workflowInfo = $this->service(E::MERCHANT)->getWebsiteSelfServeWorkflowDetails();

        return ApiResponse::json($workflowInfo);
    }

    public function getDecryptedWebsiteCommentForWebsiteSelfServe(string $actionId)
    {
        $decryptedInfo = $this->service(E::MERCHANT_DETAIL)->getDecryptedWebsiteCommentForWebsiteSelfServe($actionId);

        return ApiResponse::json(['decrypted_info' => $decryptedInfo]);
    }

    public function toggleFeeBearer()
    {
        $input = Request::all();

        $response = $this->service()->toggleFeeBearer($input);

        return ApiResponse::json($response);
    }

    public function postIncreaseTransactionLimitSelfServe()
    {
        $input = Request::all();

        $response = $this->service()->postIncreaseTransactionLimitSelfServe($input);

        return ApiResponse::json($response);
    }

    public function postTransactionLimitWorkflowApprove()
    {
        $input = Request::all();

        $response = $this->service()->postTransactionLimitWorkflowApprove($input);

        return ApiResponse::json($response);
    }

    public function getMerchantWorkflowDetails(string $workflowType)
    {
        $response = $this->service()->getMerchantWorkflowDetails($workflowType);

        return ApiResponse::json($response);
    }

    public function postAddAdditionalWebsiteSelfServe(string $urlType)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->postAddAdditionalWebsiteSelfServe($urlType, $input);

        return ApiResponse::json($response);
    }

    public function putAddAdditionalWebsiteSelfServePostWorkflowApproval()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->putAddAdditionalWebsiteSelfServePostWorkflowApproval($input);

        return ApiResponse::json($response);
    }

    public function getAdditionalWebsiteWorkflowStatus()
    {
        $status = $this->service(E::MERCHANT_DETAIL)->getAdditionalWebsiteWorkflowStatus();

        return ApiResponse::json(['status' => $status]);
    }

    public function fetchCouponCodes()
    {
        $input = Request::all();

        $response = (new Merchant\MerchantPromotions\Service())->fetchCouponCodes($input);

        return ApiResponse::json($response);
    }

    public function getShippingInfo()
    {
        $input = Request::all();

        $response = (new Merchant\ShippingInfo\Service())->getShippingInfo($input);

        return ApiResponse::json($response);
    }

    public function applyCoupon()
    {
        $input = Request::all();

        $response = (new Merchant\MerchantPromotions\Service())->applyCoupon($input);

        return ApiResponse::json($response['data'], $response['status_code']);
    }

    public function removeCoupon()
    {
        $input = Request::all();

        (new Merchant\MerchantPromotions\Service())->removeCoupon($input);

        return ApiResponse::json([], 200);
    }

    public function updateFetchCouponsUrl()
    {
        $input = Request::all();

        $this->service()->updateFetchCouponsUrl($input);

        return ApiResponse::json([], 201);
    }

    public function updateShippingInfoUrl()
    {
        $input = Request::all();

        $this->service()->updateShippingInfoUrl($input);

        return ApiResponse::json([], 201);
    }

    public function updateCodSlabs()
    {
        $input = Request::all();

        $this->service()->updateCodSlabs($input);

        return ApiResponse::json([], 201);
    }

    public function updateApplyCouponUrl()
    {
        $input = Request::all();

        $this->service()->updateApplyCouponUrl($input);

        return ApiResponse::json([], 201);
    }

    public function updateShippingSlabs()
    {
        $input = Request::all();

        $this->service()->updateShippingSlabs($input);

        return ApiResponse::json([], 201);
    }

    public function fetchMerchantsByparams()
    {
        $input = Request::all();

        $response = $this->service()->fetchMerchantsByParams($input);

        return ApiResponse::json($response);
    }
}
