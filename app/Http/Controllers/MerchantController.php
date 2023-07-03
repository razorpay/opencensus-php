<?php

namespace RZP\Http\Controllers;

use App;
use Http\Client\Common\Exception\ServerErrorException;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Request;
use ApiResponse;
use RZP\Constants\Entity as E;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Constants\Entity;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Key;
use RZP\Models\Merchant\CheckoutExperiment;
use RZP\Models\Merchant\Methods;
use RZP\Models\Merchant\PaymentLimit\Service;
use RZP\Models\Report;
use RZP\Models\Gateway;
use RZP\Models\User\Role;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Partner;
use RZP\Base\RuntimeManager;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\AccessMap;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\InheritanceMap;
use RZP\Services\SumoLogic\Service as SumoLogicService;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\Merchant\OneClickCheckout\Config\Service as OneClickCheckoutConfigService;
use RZP\Models\Merchant\OneClickCheckout;


class MerchantController extends Controller
{
    const SET_COOKIE_HEADER = 'set-cookie';

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

    public function getMerchantDetailsForAccountServiceReverseMap(string $accountId)
    {
        $data = $this->service()->getMerchantDetailsForAccountServiceReverseMap($accountId);

        return ApiResponse::json($data);
    }

    public function getUpdatedAccountsForAccountService()
    {
        $input = Request::all();

        $data = $this->service()->getUpdatedAccountsForAccountService($input);

        return ApiResponse::json($data);
    }

    public function postCreateSubMerchant()
    {
        $input = Request::all();

        $data = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_SERVICE], function () use ($input) {

            return $this->service()->createSubMerchant($input);
        });

        return ApiResponse::json($data);
    }

    public function getMerchantDataForSegment()
    {
        $data = $this->service()->getMerchantDataForSegmentAnalysis();

        return ApiResponse::json($data);
    }

    public function getAppScalabilityConfig()
    {
        $data = $this->service()->getAppScalabilityConfig();

        return ApiResponse::json($data);
    }

    public function putAppChangeUserFTUX()
    {
        $input = Request::all();

        $data = $this->service()->changeAppMerchantUserFTUX($input);

        return ApiResponse::json($data);
    }

    public function postIncrMerchantUserProductSession()
    {
        $data = $this->service()->merchantUserIncrementProductSession();

        return ApiResponse::json($data);
    }

    public function getMerchantPaymentsWithOrderSource()
    {
        $input = Request::all();

        $data = $this->service()->getMerchantPaymentsWithOrderSource($input);

        return ApiResponse::json($data);
    }

    public function postCreateSubMerchantViaBatch()
    {
        $input = Request::all();

        $data = Tracer::inspan(['name' => HyperTrace::CREATE_SUBMERCHANT_BATCH], function () use ($input) {

            return $this->service()->createSubMerchantViaBatch($input);
        });

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

        $data = Tracer::inspan(['name' => 'submerchant_onboarding_batch'], function () use ($input)
        {
            return $this->service()->bulkOnboardSubMerchantViaBatch($input);
        });

        return ApiResponse::json($data);
    }

    public function createLinkedAccount()
    {
        $input = Request::all();

        $response = $this->service()->createLinkedAccount($input);

        return ApiResponse::json($response);
    }

    public function updateLinkedAccountBankAccount(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updateLinkedAccountBankAccount($id, $input);

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
    /*
     * Adding this as part of mocking penny testing validation
     * events as part of ITF test cases
     * */
    public function mockBvsValidationEvent()
    {
        $input = Request::all();

        $data = $this->service(E::MERCHANT_DETAIL)->mockBvsValidationEvent($input);

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
        // this is temporary logging: to get all admins who use this route
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

        $this->validateRoleForPutMerchantConfig($input);

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

    public function validateDeleteTerminalv3($mid, $tid)
    {
        $data = $this->service(E::TERMINAL)->validateDeleteTerminalv3($mid, $tid);

        return ApiResponse::json($data);
    }

    public function deleteTerminalv3($mid, $tid)
    {
        $data = $this->service(E::TERMINAL)->deleteTerminalv3($mid, $tid);

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

    public function fundAdditionTPV()
    {
        $input = Request::all();

        $data = $this->service()->fundAdditionTPV($input);

        return ApiResponse::json($data);
    }

    public function addFundsViaWebhook($type)
    {
        $input = Request::all();

        $data = $this->service()->addFundsViaWebhook($type, $input);

        return ApiResponse::json($data, 200);
    }

    public function putBankAccountUpdate()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountUpdate($input);

        return ApiResponse::json($data);
    }

    public function postBankAccountFileUpload()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountFileUpload($input);

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
        $input = Request::all();

        $response = $this->service(E::MERCHANT)->getProductInternationalStatus($input);

        return ApiResponse::json($response);
    }

    public function getBankAccount($id)
    {
        $input = Request::all();

        $type = $input['type'] ?? null;

        $data = $this->service()->getBankAccount($id,$type);

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

    public function getTermsAndConditionPopupStatus()
    {
        $response = $this->service()->getTermsAndConditionPopupStatus();

        return ApiResponse::json($response);

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

        $merchant = $this->app['basicauth']->getMerchant();

        $repo = App::getFacadeRoot()['repo'];

        if (isset($data[Balance\Entity::TYPE]) === true &&
            isset($data[Balance\Entity::AMOUNT_CREDITS]) === true &&
            $data[Balance\Entity::TYPE] === Balance\Type::PRIMARY &&
            $merchant !== null &&
            $merchant->isFeatureEnabled(Feature::OLD_CREDITS_FLOW) === false)
        {
            $data[Balance\Entity::AMOUNT_CREDITS] = $repo->credits->getMerchantCreditsOfType($merchant->getId(), Credits\Type::AMOUNT);
        }

        return ApiResponse::json($data);
    }

    public function getAccountBalances()
    {
        $input = Request::all();

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

    public function getAccountConfigForCheckoutInternal()
    {
        $input = Request::all();

        $data = $this->service()->fetchConfigForCheckoutInternal($input);

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

    public function getPaymentMethodsWithOffersForCheckout()
    {
        $input = Request::all();

        $data = $this->service()->getPaymentMethodsWithOffersForCheckout($input);

        return ApiResponse::json($data);
    }

    public function getPaymentMethodsById($merchantId)
    {
        $data = $this->service()->getPaymentMethodsById($merchantId);

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

        $responseHeaders = [];

        /** @var BasicAuth $ba */
        $ba = $this->app['basicauth'];
        $merchantId = $ba->getMerchantId() ?? '';

        if ((new CheckoutExperiment([], $merchantId))->shouldRoutePreferencesTrafficThroughCheckoutService(
            $this->app['config']->get('app.checkout_service_preferences_splitz_experiment_id')
        )) {
            $merchantKey = $ba->getPublicKey() ?? '';

            if ($merchantKey !== '') {
                $input['key_id'] = $merchantKey;
            }

            /** @var HttpResponse */
            $preferencesResponse = $this->app['checkout_service']->getCheckoutPreferencesFromCheckoutService($input);

            $preferencesResponseHeaders = $preferencesResponse->headers->all();
            $setCookieHeader = $preferencesResponseHeaders[self::SET_COOKIE_HEADER][0] ?? '';

            if (!empty($setCookieHeader)) {
                $responseHeaders[self::SET_COOKIE_HEADER] = $setCookieHeader;
            }

            $prefs = $preferencesResponse->getOriginalContent();
        } else {
            $prefs = $this->service()->getCheckoutPreferences($input);
        }

        $data = $this->getCheckoutCommon($input);

        $data['preferences'] = $prefs;

        return ApiResponse::generateResponse($data)
            ->withHeaders($responseHeaders);
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

    public function adminActions()
    {
        $input = Request::all();

        return $this->service('merchant_invoice')->adminActions($input);
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

        return $response;
    }

    public function fetchMerchantDetailsForAccountingIntegrations()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->fetchMerchantDetailsForAccountingIntegrations();

        return ApiResponse::json($response);
    }

    public function getBusinessTypes()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getBusinessTypes();

        return $response;
    }

    public function getMerchantSupportedPlugins()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getMerchantSupportedPlugins();

        return ApiResponse::json($response);
    }

    public function merchantIdentityVerification()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->merchantIdentityVerification($input);

        return ApiResponse::json($response);
    }

    public function processIdentityVerificationDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->processIdentityVerificationDetails($input);

        return ApiResponse::json($response);
    }

    public function getMerchantInfo($id)
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getMerchantInfo($id);

        return $response;
    }

    public function fetchAllMerchantEntitiesRelatedInfo()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT)->fetchAllMerchantEntitiesRelatedInfo($input['merchant_list'], ($input['type'] ?? ""));

        return $response;
    }

    public function getMerchantPlugin($id)
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getMerchantPlugin($id);

        return ApiResponse::json($response);
    }

    public function createLogSearch()
    {

        $input = Request::all();

        return (new SumoLogicService())->logSearch($input);
    }

    public function getMerchantLogs()
    {
        $input = Request::all();

        $response = (new SumoLogicService())->logSearch($input);

        return $response;
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

    public function otpSendViaEmail()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->otpSendViaEmail($input);

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

    public function postApplyCoupon()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->postApplyCoupon($input);

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

    public function postMerchantClarificationDetails($mid)
    {
        $input = Request::all();

        $this->trace->info(TraceCode::CLARIFICATION_DETAILS_EDIT_REQUEST, []);

        $response = $this->service(E::CLARIFICATION_DETAIL)->createClarificationDetailAdmin($mid, $input);

        return ApiResponse::json($response);
    }

    public function postMerchantResponseToClarifications($id="")
    {
        $input = Request::all();

        $this->trace->info(TraceCode::CLARIFICATION_DETAILS_EDIT_REQUEST, $input);

        if (strlen($id) === 0){
            $id = $this->ba->getMerchantId();
        }

        $response = $this->service(E::CLARIFICATION_DETAIL)->saveMerchantResponseToClarifications($input, $id);

        return ApiResponse::json($response);
    }

    public function getMerchantNcRevampEligibility($id=null)
    {
        $response = $this->service(E::CLARIFICATION_DETAIL)->getMerchantNcRevampEligibility($id);

        return ApiResponse::json($response);
    }

    public function getMerchantClarificationDetails($id=null)
    {
        $response = $this->service(E::CLARIFICATION_DETAIL)->getClarificationDetail($id);

        return ApiResponse::json($response);
    }

    public function sendWhatsappNotification($id)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->sendWhatsappNotification($id, $input);

        return ApiResponse::json($response);
    }

    public function getRequestDocumentList()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->getRequestDocumentList($input);

        return ApiResponse::json($response);
    }

    public function uploadMerchant()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->uploadMerchant($input);

        return ApiResponse::json($response);
    }

    public function uploadMiqBatch()
    {
        $input = Request::all();

        $response =$this->service(E::MERCHANT_DETAIL)->uploadMiqBatch($input);

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
        $input = Request::all();

        $data = $this->service()->getMerchantUsers($input);

        return ApiResponse::json($data);
    }

    public function getInternalUsers($merchantId)
    {
        $headers = Request::header();

        $product = $headers['x-product-name'][0];

        if( empty($headers['x-role-id'][0]) === true ) {

            $data = $this->service()->getInternalUsers($merchantId, $product);

        } else {

            $data = $this->service()->getInternalUsersByRole($merchantId, $product, $headers['x-role-id'][0]);

        }

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

    public function getNCAdditionalDocuments()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getNCAdditionalDocuments();

        return ApiResponse::json($response);
    }

    public function updateActivationStatusInternal(string $id)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->updateActivationStatusInternal($id, $input);

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

    public function putMerchantContactUpdatePostWorkflow()
    {
        $input = Request::all();

        $data = $this->service(E::MERCHANT_DETAIL)->merchantContactUpdatePostWorkflow($input);

        return ApiResponse::json($data);
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

    public function getBusinessCategoriesV2()
    {
        $response = $this->service(E::MERCHANT_DETAIL)->getBusinessCategoriesV2();

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

    public function batchTagMerchants()
    {
        $input = Request::all();

        $response = $this->service()->bulkTagBatch($input);

        return ApiResponse::json($response);
    }

    public function getCapitalTags()
    {
        $response = $this->service()->getCapitalTags();

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

    public function internalGetMerchantDetails($merchantId)
    {
        $response = $this->service()->internalGetMerchantDetails($merchantId);

        return ApiResponse::json($response);
    }

    public function fetchMerchantDetailsForAccountReceivables()
    {
        $response = $this->service(Entity::MERCHANT_DETAIL)->fetchMerchantDetailsForAccountReceivables();

        return ApiResponse::json($response);
    }

    public function getSmartDashboardMerchantDetails()
    {
        $response = $this->service()->getSmartDashboardMerchantDetails();

        return ApiResponse::json($response);
    }

    public function patchMerchantDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->patchMerchantDetails($input);

        return ApiResponse::json($response);
    }

    public function patchSmartDashboardMerchantDetails()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_DETAIL)->patchSmartDashboardMerchantDetails($input);

        return ApiResponse::json($response);
    }

    public function externalGetMerchantCompositeDetails($id)
    {
        $response = $this->service()->externalGetMerchantCompositeDetails($id);

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

    public function internalGetPaymentInstruments()
    {
        $response = (new Methods\Service)->internalGetPaymentInstruments();

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

    public function getMerchantNcCount(string $merchantId)
    {
        $response = $this->service()->getMerchantNcCount($merchantId);

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
                        ->mapOAuthApplication($merchantId, $input, true);

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

        $response = Tracer::inspan(['name' => HyperTrace::LIST_SUBMERCHANTS], function () use ($input) {

            return $this->service()->listSubmerchants($input);
        });

        return ApiResponse::json($response);
    }

    public function updatePartnerType()
    {
        $input = Request::all();

        $response = Tracer::inspan(['name' => HyperTrace::UPDATE_PARTNER_TYPE_SERVICE], function () use ($input) {

            return $this->service()->updatePartnerType($input);
        });

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
        $input = Request::all();

        $data = $this->service()->sendSubmerchantPasswordResetLink($id, $input);

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
        $response = Tracer::inspan(['name' => HyperTrace::FETCH_REFERRAL], function () {

            return $this->service()->fetchReferral();
        });

        return ApiResponse::json($response);
    }

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function createReferral()
    {
        $response = Tracer::inspan(['name' => HyperTrace::CREATE_REFERRAL], function () {

            return $this->service()->createReferral();
        });

        return ApiResponse::json($response);
    }

    public function fetchPartnerReferralViaBatch()
    {
        $input = Request::all();

        $response = $this->service()->fetchPartnerReferralViaBatch($input);

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

    /**
     * @return mixed
     */
    public function retryStoreLegalDocuments()
    {
        RuntimeManager::setTimeLimit(600);

        $response = $this->service()->retryStoreLegalDocuments();

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

    public function postMerchantPreferencesBulk(string $group)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_ATTRIBUTE)->upsertBulk($group, $input);

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

    public function getMerchantPreferencesAdmin($merchantId, string $group, string $type = null)
    {
        $response = $this->service(E::MERCHANT_ATTRIBUTE)->getPreferencesByGroupAndTypeAdminForBanking($merchantId,$group, $type);

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

    public function getMerchantActivationEligibility($mid)
    {

        $response = $this->service()->getMerchantActivationEligibility($mid);

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

    public function handleOnboardingCronJobs()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_ONBOARDING_ESCALATIONS)->handleOnboardingCrons($input);

        return ApiResponse::json($response);
    }

    public function postMerchantPopularProductsCron()
    {
        $response = $this->service()->handleMerchantPopularProductsCron();

        return ApiResponse::json($response);
    }

    public function handleNoDocOnboardingEscalationsCron()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_ONBOARDING_ESCALATIONS)->handleNoDocOnboardingEscalationsCron($input);

        return ApiResponse::json($response);
    }

    public function handleBankingOrgOnboardingEscalationsCron()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_ONBOARDING_ESCALATIONS)->handleBankingOrgOnboardingEscalationsCron($input);

        return ApiResponse::json($response);
    }

    public function handleOnboardingCrons(string $cronType)
    {
        $input = Request::all();

        $response = $this->service()->handleCron($cronType, $input);

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
        $response = $this->service(E::MERCHANT_WEBSITE)->getMerchantTncById($tncId);

        return ApiResponse::json($response);
    }

    public function getMerchantTncByMerchantId($merchantId)
    {
        $response = $this->service(E::MERCHANT_WEBSITE)->getMerchantTncByMerchantId($merchantId);

        return ApiResponse::json($response);
    }

    public function postMerchantTnc()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_WEBSITE)->saveMerchantTnc($input);

        return ApiResponse::json($response);
    }

    public function postWebsiteSectionAction()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_WEBSITE)->postWebsiteSectionAction($input);

        return ApiResponse::json($response);
    }

    public function getWebsiteSectionDownload($id,$sectionName)
    {
        $input = Request::all();
        $input['action']       = 'download';
        $input['section_name'] = $sectionName;
        $input['merchant_id']  = $id;

        return $this->service(E::MERCHANT_WEBSITE)->postWebsiteSectionAction($input);
    }

    public function saveMerchantWebsiteSection()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_WEBSITE)->saveMerchantWebsiteSection($input);

        return ApiResponse::json($response);
    }

    public function getMerchantWebsiteSection()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_WEBSITE)->getMerchantWebsiteSection($input);

        return ApiResponse::json($response);
    }


    public function getMerchantWebsiteSectionPage(string $sectionName)
    {
        $input = Request::all();

        $input[\RZP\Models\Merchant\Website\Constants::SECTION_NAME] = $sectionName;

        return ApiResponse::json($this->service(E::MERCHANT_WEBSITE)->getMerchantWebsiteSectionPage($input));
    }

    public function getPublicWebsiteSectionPage(string $sectionName,String $id)
    {
        $input = Request::all();

        $input[\RZP\Models\Merchant\Website\Constants::SECTION_NAME] = $sectionName;

        $input['id'] = $id;

        $response= ApiResponse::json($this->service(E::MERCHANT_WEBSITE)->getPublicWebsiteSectionPage($input));

        $this->addCorsHeaders($response,'GET, OPTIONS');

        return $response;
    }

    public function getPublicWebsiteSectionPageLinks(String $id)
    {
        $response = ApiResponse::json($this->service(E::MERCHANT_WEBSITE)->getPublicWebsiteSectionPageLinks($id));

        $this->addCorsHeaders($response,'GET, OPTIONS');

        return $response;
    }

    public function getMerchantPolicyDetails()
    {
        $response = $this->service(E::MERCHANT_WEBSITE)->getMerchantPolicyDetails();

        return ApiResponse::json($response);
    }

    protected function addCorsHeaders($response, string $methods): void
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        $response->headers->set('Access-Control-Allow-Methods', $methods);
    }

    public function postAdminSectionAction(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_WEBSITE)->postAdminSectionAction($merchantId,$input);

        return ApiResponse::json($response);
    }


    public function saveAdminWebsiteSection(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_WEBSITE)->saveAdminWebsiteSection($merchantId,$input);

        return ApiResponse::json($response);
    }

    public function getAdminWebsiteSection(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_WEBSITE)->getAdminWebsiteSection($merchantId,$input);

        return ApiResponse::json($response);
    }


    public function postAppsflyerAttributionDetails()
    {
        $input = Request::all();

        $this->service(E::MERCHANT_DETAIL)->postAppsflyerAttributionDetails($input);

        return ApiResponse::json([]);
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

    public function bulkFraudNotify($source)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_BULK_FRAUD_NOTIFY)->notify($input, $source);

        return ApiResponse::json($response);
    }

    public function bulkFraudNotifyPostBatch()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_BULK_FRAUD_NOTIFY)->notifyPostBatch($input);

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

    public function getMerchantDetailsForSFConverge($id)
    {
        $response = $this->service()->getMerchantDetailsForSFConverge($id);

        return ApiResponse::json($response);
    }

    public function getTerminalDetailsForSFConverge($merchantid)
    {
        $response = $this->service()->getTerminalDetailsForSFConverge($merchantid);

        return ApiResponse::json($response);
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

    public function patchAdminPurposeCode()
    {
        $input = Request::all();

        $response = $this->service()->patchAdminPurposeCode($input);

        return ApiResponse::json($response);
    }

    public function getHsCodeDetails()
    {
        $data = $this->service()->getHsCodeDetails();

        return ApiResponse::json($data);
    }

    public function putMerchantContact(string $id)
    {
        $input = Request::all();

        $this->service(E::MERCHANT_DETAIL)->updateMerchantContact($id, $input);

        return ApiResponse::json([]);
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

    public function getMerchantWorkflowDetails(string $workflowType, string $merchantId = null)
    {
        $response = $this->service()->getMerchantWorkflowDetails($workflowType, $merchantId);

        return ApiResponse::json($response);
    }

    public function getMerchantWorkflowDetailsBulk( string $merchantId = null)
    {
        $input = Request::all();

        $response = $this->service()->getMerchantWorfklowDetailsBulk($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function postMerchantWorkflowClarification(string $workflowType)
    {
        $input = Request::all();

        $this->service()->postMerchantWorkflowClarification($workflowType, $input);

        return ApiResponse::json([], 200);
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

        $response = $this->service(E::MERCHANT_DETAIL)->putAddAdditionalWebsiteSelfServePostWorkflowApproval($input, true);

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

        try
        {
            $response = (new Merchant\MerchantPromotions\Service())->fetchCouponCodes($input);

            return ApiResponse::json($response);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof Exception\BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::SERVER_ERROR_MERCHANT_FETCH_COUPONS_EXTERNAL_CALL_EXCEPTION:
                    case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                    case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                    case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }

    public function getShippingInfo()
    {
        $input = Request::all();

        try
        {
            $response = (new Merchant\ShippingInfo\Service())->getShippingInfo($input);
            return ApiResponse::json($response);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof Exception\BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION:
                    case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                    case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                    case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
                    case ErrorCode::SERVER_ERROR_SHOPIFY_SERVICE_FAILURE:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }

    public function applyCoupon()
    {
        $input = Request::all();
        try
        {
            $response = (new Merchant\MerchantPromotions\Service())->applyCoupon($input);

            return ApiResponse::json($response['data'], $response['status_code']);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof Exception\BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::SERVER_ERROR_MERCHANT_FETCH_COUPONS_EXTERNAL_CALL_EXCEPTION:
                    case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                    case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                    case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }

    public function removeCoupon()
    {
        $input = Request::all();

        try
        {
            (new Merchant\MerchantPromotions\Service())->removeCoupon($input);

            return ApiResponse::json([], 200);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof Exception\BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                    case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                    case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
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

    public function updateMerchantPlatform()
    {
        $input = Request::all();

        $this->service()->updateMerchantPlatform($input);

        return ApiResponse::json([], 201);
    }

    public function updateMerchant1ccConfigDark()
    {
        $input = Request::all();
        $this->service()->updateMerchant1ccConfigDark($input);
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

    public function updateCodServiceabilitySlabDark()
    {
        $input = Request::all();

        $this->service()->updateCodServiceabilitySlabDark($input);

        return ApiResponse::json([], 201);
    }

    public function fetchMerchantsByparams()
    {
        $input = Request::all();

        $response = $this->service()->fetchMerchantsByParams($input);

        return ApiResponse::json($response);
    }

    public function updateChargebackPOC()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_EMAIL)->updateChargebackPOC($input);

        return ApiResponse::json($response);
    }

    public function updateWhitelistedDomain()
    {
        $input = Request::all();

        $response = $this->service()->updateWhitelistedDomain($input);

        return ApiResponse::json($response);
    }

    public function updateShopify1ccConfig()
    {
        $input = Request::all();

        $response = (new Merchant\OneClickCheckout\AuthConfig\Service())->updateShopify1ccConfig($input);

        return ApiResponse::json($response);
    }

    public function updateShopify1ccCredentials($merchantId)
    {
        $input = Request::all();

        $response = (new Merchant\OneClickCheckout\AuthConfig\Service())->updateShopify1ccCredentials($input);

        return ApiResponse::json($response, 201);
    }

    public function update1ccConfig()
    {
        $input = Request::all();

        (new Merchant\OneClickCheckout\Config\Service())->update1ccConfig($input);

        return ApiResponse::json([]);
    }

    public function get1ccConfig()
    {
        $input = Request::all();

        return (new Merchant\OneClickCheckout\Config\Service())->get1ccConfig();
    }

    public function get1ccPrepayCodConfig(): array
    {
        return (new Merchant\OneClickCheckout\Config\Service())->get1ccPrepayCodConfig();
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function getInternal1ccPrepayCodConfig($merchantId): array
    {
        try
        {
            return (new Merchant\OneClickCheckout\Config\Service())->getInternal1ccPrepayCodConfig($merchantId);
        }
        catch (\Exception $ex)
        {
            if (($ex instanceof Exception\BadRequestException) === true)
            {
                $error = $ex->getError();
                $errorCode = $error->getInternalErrorCode();
                if ($errorCode == ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID)
                {
                    $data = ["error_class" => $error->getPublicErrorCode(), "internal_error_code" => $errorCode];
                    return ApiResponse::json($data, 500);
                }
            }
            throw $ex;
        }
    }

    public function getInternal1ccConfig($merchantId)
    {
        try
        {
            return (new Merchant\OneClickCheckout\Config\Service())->getInternal1ccConfig($merchantId);
        }
        catch (\Exception $ex)
        {
            if (($ex instanceof Exception\BadRequestException) === true)
            {
                $error = $ex->getError();
                $errorCode = $error->getInternalErrorCode();
                if ($errorCode == ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID)
                {
                    $data = ["error_class" => $error->getPublicErrorCode(), "internal_error_code" => $errorCode];
                    return ApiResponse::json($data, 500);
                }
            }
            throw $ex;
        }
    }

    public function getCheckout1ccConfig()
    {
        return (new Merchant\OneClickCheckout\Config\Service())->getCheckout1ccConfig();
    }

    public function getInternalShopifyCustomerAddresses($merchantId)
    {
        $input = Request::all();

        try
        {
            return (new Merchant\OneClickCheckout\Shopify\AddressIngestion\Service())->getInternalCustomerAddresses($merchantId, $input);
        }
        catch (\Exception $ex)
        {
            if (($ex instanceof Exception\BadRequestException) === true)
            {
                $error = $ex->getError();
                $errorCode = $error->getInternalErrorCode();
                switch ($errorCode)
                {
                    case ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_NOT_CONFIGURED:
                    case ErrorCode::BAD_REQUEST_ERROR_INVALID_ONE_CC_MERCHANT:
                    case ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID:
                        $data = ["error_class" => $error->getPublicErrorCode(), "internal_error_code" => $errorCode];
                        return ApiResponse::json($data, 500);
                }
            }
            throw $ex;
        }
    }

    public function get1ccMerchantPreferences()
    {
        $data = $this->service()->get1ccMerchantPreferences();

        return ApiResponse::json($data);
    }

    public function getFUXDetailsForPartner()
    {
        $response = Tracer::inspan(['name' => HyperTrace::GET_FUX_DETAILS_FOR_PARTNER_SERVICE], function() {
            return $this->service()->getFUXDetailsForPartner();
        });

        return ApiResponse::json($response);
    }

    public function bulkMigrateAggregatorToResellerPartner()
    {
        $input = Request::all();

        $this->service()->bulkMigrateAggregatorToResellerPartner($input);

        return ApiResponse::json([]);
    }

    public function migrateAggregatorToResellerPartner()
    {
        $input = Request::all();

        $response = $this->service()->migrateAggregatorToResellerPartner($input);

        return ApiResponse::json([$response]);
    }
    public function createFraudBatch()
    {
        RuntimeManager::setMemoryLimit('1024M');
        $input = Request::all();
        $response = $this->service(E::MERCHANT_BULK_FRAUD_NOTIFY)->createFraudBatch($input);

        return ApiResponse::json($response);
    }

    public function postSettlementsEventsCron()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT)->settlementsEventsCron($input);

        return ApiResponse::json($response);
    }

    public function getEnhancedMerchantActivationDetails(string $merchantId)
    {

        $response = $this->service(E::MERCHANT_DETAIL)->getEnhancedActivationDetails($merchantId);

        return ApiResponse::json($response);
    }

    public function removeSubmerchantDashboardAccessOfPartner()
    {
        $input = Request::all();

        $this->service()->removeSubmerchantDashboardAccessOfPartner($input);

        return ApiResponse::json([]);
    }

    private function validateRoleForPutMerchantConfig(array $input)
    {
        $ba = $this->app['basicauth'];

        $manageAlertRoles = array(Role::OWNER, Role::ADMIN, Role::MANAGER);

        if($this->ba->isProxyAuth() === true
            && in_array($ba->getUserRole(), $manageAlertRoles) === false
            && (isset($input["amount_credits_threshold"]) === true
                or isset($input["fee_credits_threshold"]) === true
                or isset($input["refund_credits_threshold"]) === true
                or isset($input["balance_threshold"]) === true)
        )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND
            );
        }
    }

    public function createInternationalIntegration()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->createMerchantInternationalIntegration($input);

        return ApiResponse::json($response);
    }

    public function getInternationalIntegration($mid)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->getMerchantInternationalIntegrations($mid, $input);

        return ApiResponse::json($response);
    }

    public function deleteInternationalIntegration()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->deleteMerchantInternationalIntegrations($input);

        return ApiResponse::json($response);
    }

    public function patchHsCode()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->patchHsCode($input);

        return ApiResponse::json($response);
    }

    public function getMerchantHsCode()
    {
        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->getMerchantHsCode(null);

        return ApiResponse::json($response);
    }

    public function getAdminMerchantHsCode($merchantId)
    {
        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->getMerchantHsCode($merchantId);

        return ApiResponse::json($response);
    }

    // Send email reminders for invoice upload to international integrated merchants.
    // Currently for OPGSP_IMPORT
    public function sendInvoiceRemindersForInternationalIntegration()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->sendInvoiceRemindersForInternationalIntegration($input);

        return ApiResponse::json($response);
    }

    public function disable1ccMagicCheckout()
    {
        $input = Request::all();

        (new Merchant\OneClickCheckout\Config\Service())->disable1ccMagicCheckout($input);

        return ApiResponse::json([]);
    }

    public function CollectInfoMerchantDetailsPatch($merchantId){
        $input = Request::all();
        $response = $this->service(E::MERCHANT_DETAIL)->patchSmartDashboardMerchantDetails($input, $merchantId);
        return ApiResponse::json($response);
    }

    // Function to enable/disable feature flags from merchant dashboard
    public function addOrRemoveFeaturesForMerchant()
    {
        $input = Request::all();

        $data = $this->service()->addOrRemoveFeaturesForMerchant($input);

        return ApiResponse::json($data);
    }

    // Function to enable non 3ds card processing from merchant dashboard
    public function postEnableNon3dsSelfServe()
    {
        $response = $this->service()->postEnableNon3dsSelfServe();

        return ApiResponse::json($response);
    }

    //Function to approve the enabling of non-3ds card processing for merchants
    public function postEnableNon3dsWorkflowApprove()
    {
        $input = Request::all();

        $response = $this->service()->postEnableNon3dsWorkflowApprove($input);

        return ApiResponse::json($response);
    }

    // Get the non-3ds card processing enablement workflow details
    public function getEnableNon3dsDetails()
    {
        $response = $this->service()->getEnableNon3dsDetails();

        return ApiResponse::json($response);
    }

    public function getInternationalVirtualAccounts()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->getInternationalVirtualAccounts($input);

        return ApiResponse::json($response);
    }

    public function getInternationalVirtualAccountByVACurrency($va_currency)
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_INTERNATIONAL_INTEGRATIONS)
            ->getInternationalVirtualAccountByVACurrency($input,$va_currency);

        return ApiResponse::json($response);
    }

    public function applyGiftCard(string $orderId) {

        $input = Request::all();

        $response = (new Merchant\MerchantGiftCardPromotions\Service())->applyGiftCard($orderId, $input);

        return ApiResponse::json($response['data'], $response['status_code']);
    }

    public function removeGiftCard(string $orderId) {

        $input = Request::all();

        $response = (new Merchant\MerchantGiftCardPromotions\Service())->removeGiftCard($orderId, $input);

        return ApiResponse::json([], 200);
    }

    public function getMerchantConsents(string $merchantId)
    {
        $response = $this->service()->getMerchantConsents($merchantId);

        return ApiResponse::json($response);
    }

    public function saveMerchantConsents()
    {
        $input = Request::all();

        $response = $this->service()->saveMerchantConsents($input);

        return ApiResponse::json($response);
    }

    public function saveWebsitePlugin(string $merchantId)
    {
        $input = Request::all();

        $response = (new BusinessDetail\Service())->saveWebsitePlugin($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function uploadMaxPaymentLimitViaFile()
    {
        $input = Request::all();
        $data = (new Service())->uploadMaxPaymentLimitViaFile($input);
        return ApiResponse::json($data);
    }

    public function executeMaxPaymentLimitWorkflow()
    {
        $input = Request::all();
        $data = (new Service())->executeMaxPaymentLimitWorkflow($input);
        return ApiResponse::json($data);
    }

    /**
     * @throws \Throwable
     */
    public function updateMerchant1ccCouponConfig()
    {
        $input = Request::all();

        $response =  $this->service()->updateMerchant1ccCouponConfig($input);

        return ApiResponse::json($response);
    }

    public function onboardMerchantOnNetworkBulk()
    {
        $input = Request::all();

        $response = $this->service(E::MERCHANT_ATTRIBUTE)->onboardMerchantOnNetworkBulk($input);

        return ApiResponse::json($response);
    }

    public function updateShippingProviderConfig() {
        $input = Request::all();

        $response = (new Merchant\OneClickCheckout\Config\Service())->updateShippingProviderConfig($input);

        return ApiResponse::json($response);
    }

    public function fetchMerchantIpConfig()
    {
        $response = $this->service()->fetchMerchantIpConfig();

        return ApiResponse::json($response);
    }

    public function fetchMerchantIpConfigForAdmin(string $id)
    {
        $response = $this->service()->fetchMerchantIpConfigForAdmin($id);

        return ApiResponse::json($response);
    }

    public function createMerchantIpConfig()
    {
        $input = Request::all();

        $response = $this->service()->createOrEditMerchantIpConfig($input);

        return ApiResponse::json($response);
    }

    public function editOptStatusForMerchantIPConfig()
    {
        $input = Request::all();

        $response = $this->service()->editOptStatusForMerchantIPConfig($input);

        return ApiResponse::json($response);
    }

    public function getShopify1ccConfigs()
    {
        $input = Request::all();

        return (new Merchant\OneClickCheckout\Config\Service())->getShopify1ccConfigs($input);
    }

    public function isPartnershipMerchant($merchantId){

        $response = $this->service()->isPartnershipMerchant($merchantId);

        return ApiResponse::json($response);
    }

    /**
     * Used to perform Key & Keyless Public Auth for microservices which serve
     * requests to outside world.
     * Should be replaced by Edge (or) an auth microservice in the long term.
     *
     * @return HttpResponse|HttpJsonResponse
     *
     * @see Merchant\Service::validatePublicAuthOverInternalAuth()
     */
    public function validatePublicAuthOverInternalAuth(): HttpResponse|HttpJsonResponse
    {
        return $this->service()->validatePublicAuthOverInternalAuth(Request::instance());
    }

    /**
     * Fetches the capital applications for a given product for sub-merchants of a partner
     *
     * @return HttpResponse|HttpJsonResponse
     */
    public function getCapitalApplicationsForSubmerchants(): HttpResponse|HttpJsonResponse
    {
        $input = Request::all();

        return $this->service()->getCapitalApplicationsForSubmerchants($input);
    }

    public function saveMerchantAuthorizationToPartner($merchantId)
    {
        $input = Request::all();

        $response = $this->service()->saveMerchantAuthorizationToPartner($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function getMerchantAuthorizationForPartner($merchantId)
    {
        $input = Request::all();

        $response = $this->service()->getMerchantAuthorizationForPartner($merchantId, $input);

        return ApiResponse::json($response);
    }
    public function get1ccAddressIngestionConfig()
    {
        $input = Request::all();

        return (new Merchant\OneClickCheckout\Config\Service())->get1ccAddressIngestionConfig($input);
    }

    public function push1ccAddresses()
    {
        $input = Request::all();

        return $this->app['magic_address_provider_service']->push1ccAddresses($input);
    }

    public function push1ccWoocPluginInfo()
    {
        $input = Request::all();

        return $this->app['magic_checkout_plugin_service']->push1ccWoocPluginInfo($input);
    }

    public function isFeatureEnabledForPartnerOfSubmerchant(string $featureName)
    {
        $response = $this->service()->isFeatureEnabledForPartnerOfSubmerchant($featureName);

        return ApiResponse::json($response);
    }
    public function getWoocommerce1ccConfigs()
    {
        $input = Request::all();

        return (new Merchant\OneClickCheckout\Config\Service())->getWoocommerce1ccConfigs($input);
    }

    public function convert1ccPrepayCODOrders()
    {
        $input = Request::all();
        $this->trace->info(
            TraceCode::ONE_CC_PREPAY_WOOCOMMERCE_COD_ORDER_CONVERT,
            [
                'input'=> $input,
            ]);
        $prepayConfigs = (new OneClickCheckoutConfigService)->get1ccPrepayCodConfig();

        if ($prepayConfigs[(new OneClickCheckout\Constants)::ENABLED] === true)
        {
            $this->app['magic_prepay_cod_provider_service']->convert1ccPrepayCODOrders($input);
        }
        return ApiResponse::json([]);
    }

}
