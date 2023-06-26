<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Trace\Tracer;
use RZP\Constants\Entity as E;
use RZP\Constants\HyperTrace;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\BankingAccount\BankLms;


class BankingAccountController extends Controller
{
    /** @var ActivationDetail\Service $activationDetailService  */
    protected $activationDetailService;

    /** @var BankLms\Service $bankLmsService */
    protected $bankLmsService;

    public function __construct()
    {
        $this->activationDetailService = resolve(ActivationDetail\Service::class);

        $this->bankLmsService = resolve(BankLms\Service::class);

        parent::__construct();
    }

    use Traits\HasCrudMethods;

    public function createDashboard()
    {
        $input = Request::all();

        $response = $this->service()->createByMerchant($input);

        return ApiResponse::json($response);
    }

    // Start Bank LMS
    public function updatePartnerTypeToMerchantBankCaOnboarding()
    {
        $input = Request::all();

        $response = Tracer::inspan(['name' => HyperTrace::UPDATE_PARTNER_TYPE_SERVICE], function () use ($input) {

            return $this->bankLmsService->transformNormalMerchantToBankPartner($input);
        });

        return ApiResponse::json($response);
    }

    public function fetchBranchList()
    {
        $response = $this->bankLmsService->fetchBranchList();
        return ApiResponse::json($response);
    }

    public function fetchRmList()
    {
        $response = $this->bankLmsService->fetchRmList();
        return ApiResponse::json($response);
    }

    public function attachCaApplicationMerchantToBankPartnerBulk()
    {
        $input = Request::all();

        $data = $this->bankLmsService->attachCaApplicationMerchantToBankPartnerBulk($input);

        return ApiResponse::json($data);
    }

    public function fetchMultipleBankingAccountEntity()
    {
        $input = Request::all();

        $data = $this->bankLmsService->fetchMultipleBankingAccountEntity($input);

        return ApiResponse::json($data);
    }

    public function fetchBankingAccountEntityById(string $id)
    {
        $input = Request::all();

        $data = $this->bankLmsService->fetchBankingAccountById($id, $input);

        return ApiResponse::json($data);
    }

    public function updateBankingAccountLeadByBank(string $id)
    {
        $input = Request::all();

        $data = $this->bankLmsService->updateLeadDetails($id, $input);

        return ApiResponse::json($data);
    }

    public function assignBankPocUserToApplication(string $id)
    {
        $input = Request::all();

        $data = $this->bankLmsService->assignBankPartnerPocToApplication($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchBankingAccountActivationActivityById(string $id)
    {
        $input = Request::all();

        $data = $this->bankLmsService->fetchBankingAccountsActivationActivityById($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchBankingAccountActivationCommentsById(string $id)
    {
        $input = Request::all();

        $data = $this->bankLmsService->fetchBankingAccountsActivationCommentById($id, $input);

        return ApiResponse::json($data);
    }

    public function createBankingAccountActivationComment(string $id)
    {
        $input = Request::all();

        $data = $this->bankLmsService->createBankingAccountsActivationComment($id, $input);

        return ApiResponse::json($data);
    }

    public function downloadActivationMisForBank()
    {
        $input = Request::all();

        $data = $this->bankLmsService->downloadActivationMis($input);

        return ApiResponse::json($data);
    }

    public function requestActivationMisForBank()
    {
        $input = Request::all();

        $data = $this->bankLmsService->requestActivationMisReport($input);

        return ApiResponse::json($data);
    }

    // End Of Bank LMS

    public function getCustomerAppointmentDateOptions(string $city)
    {
        $input = Request::all();

        $response = $this->service()->getCustomerAppointmentDateOptions($city, $input);

        return ApiResponse::json($response);
    }

    public function updateDashboard(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updateByMerchant($id, $input);

        return ApiResponse::json($response);
    }

    public function activate(string $id)
    {
        $input = Request::all();

        $response = $this->service()->activate($id, $input);

        return ApiResponse::json($response);
    }

    public function postServiceablePincodes(string $channel)
    {
        $input = Request::all();

        $result = $this->service()->addOrRemoveServiceablePincodes($input, $channel);

        return ApiResponse::json($result);
    }

    public function processAccountInfoWebhook(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->processAccountInfoWebhook($channel, $input);

        return $response;
    }

    public function createRblLead()
    {
        $input = Request::all();

        $response = $this->service()->createMerchantAndBankingEntities($input);

        return $response;
    }

    public function bulkCreateBankingAccountsForYesbank()
    {
        $input = Request::all();

        $response = $this->service()->bulkCreateBankingAccountsForYesbank($input);

        return ApiResponse::json($response);
    }

    public function processGatewayBalanceUpdate($channel)
    {
        $response = $this->service()->processGatewayBalanceUpdate($channel);

        return ApiResponse::json($response);
    }

    public function getActivationStatusChangeLog(string $id)
    {
        $response = $this->service()->getActivationStatusChangeLog($id);

        return ApiResponse::json($response);
    }

    public function bulkAssignReviewer()
    {
        $response = $this->service()->bulkAssignReviewer($this->input);

        return ApiResponse::json($response);
    }

    public function postCreateActivationDetail(string $bankingAccountId)
    {
        $response = $this->activationDetailService->createForBankingAccount($bankingAccountId, $this->input);

        return ApiResponse::json($response);
    }

    public function createActivationComment(string $id)
    {
        $input = Request::all();

        $response = $this->service(E::BANKING_ACCOUNT_COMMENT)->createForBankingAccount($id, $input);

        return ApiResponse::json($response);
    }

    public function postActivationCommentsFromBatchService()
    {
        $input = Request::all();

        $response = $this->service(E::BANKING_ACCOUNT_COMMENT)->createCommentFromBatch($input);

        return ApiResponse::json($response);
    }

    public function getActivationComments(string $id)
    {
        $input = Request::all();

        $response = $this->service(E::BANKING_ACCOUNT_COMMENT)->fetchMultiple($id, $input);

        return ApiResponse::json($response);
    }

    public function getActivationCallLogs(string $id)
    {
        $input = Request::all();

        $response = $this->service(E::BANKING_ACCOUNT_CALL_LOG)->fetchMultiple($id, $input);

        return ApiResponse::json($response);
    }

    public function patchUpdateActivationComment(string $id)
    {
        $input = Request::all();

        $response = $this->service(E::BANKING_ACCOUNT_COMMENT)->update($id, $input);

        return ApiResponse::json($response);
    }

    public function patchActivationDetail(string $bankingAccountId)
    {
        $input = Request::all();

        $response = $this->activationDetailService->updateForBankingAccount($bankingAccountId, $input);

        return ApiResponse::json($response);
    }

    public function addActivationSlotBookingDetail(string $bankingAccountId)
    {
        $response = $this->activationDetailService->addSlotBookingDetailsForBankingAccount($bankingAccountId, $this->input);

        return ApiResponse::json($response);
    }

    public function postUpdateActivationDetailsFromBatchService()
    {
        $input = Request::all();

        $response = $this->service()->updateDetailsFromBatchService($input);

        return ApiResponse::json($response);
    }

    public function downloadActivationMis()
    {
        $input = Request::all();

        $response = $this->service()->downloadActivationMis($input);

        return ApiResponse::json($response);
    }

    public function requestActivationMisReport()
    {
        $input = Request::all();

        $response = $this->service()->requestActivationMisReport($input);

        return ApiResponse::json($response);
    }

    public function getBankingAccountSalesPOCs()
    {
        $response = $this->service()->getBankingAccountSalesPOCs();

        return ApiResponse::json($response);
    }

    public function getBankingAccountOpsMxPocs()
    {
        $response = $this->service()->getBankingAccountOpsMxPocs();

        return ApiResponse::json($response);
    }

    public function sendDailyUpdatesToAuditors(string $auditorType)
    {
        $response = $this->service()->sendDailyUpdatesToAuditors($auditorType);

        return ApiResponse::json($response);
    }

    public function checkPincodeServiceabilityByRBL($pincode)
    {
        $data =  $this->service()->CheckServiceableByRBLUsingBAS($pincode, false);

        return ApiResponse::json($data);
    }

    public function resetWebhookData(string $id)
    {
        $response = $this->service()->resetWebhookData($id);

        return ApiResponse::json($response);
    }

    public function verifyOtp(string $id)
    {
        $input = Request::all();

        $response = $this->activationDetailService->verifyOtpForContact($id, $input);

        return ApiResponse::json($response);
    }

    public function getBankingAccountForAccountNumber(string $accountNumber, string $merchantId)
    {
        $response = $this->service()->fetchBankingAccountForAccountNumber($accountNumber, $merchantId);

        return ApiResponse::json($response);
    }

    public function getBankingAccountForBalanceId(string $balanceId)
    {
        $response = $this->service()->getBankingAccountForBalanceId($balanceId);

        return ApiResponse::json($response);
    }

    public function getBankingAccountBeneficiary(string $accountNumber, string $ifsc)
    {
        $response = $this->service()->fetchBankingAccountBeneficiary($accountNumber, $ifsc);

        return ApiResponse::json($response);
    }

    public function sendNotificationToSPOC()
    {
        $response = $this->service()->notifyToSPOC();

        return ApiResponse::json($response);
    }

    public function fetchActivatedAccounts()
    {
        $input = Request::all();

        $response = $this->service()->fetchActivatedAccounts($input);

        return $response;
    }

    public function fetchMultipleRblApplicationsFromApiAndBas() {
        $input = Request::all();

        $response = $this->service()->fetchMultipleRblApplicationsFromApiAndBas($input);

        return $response;
    }

    public function fetchRblApplicationFromApiAndBas(string $bankingAccountId) {
        $input = Request::all();

        $response = $this->service()->fetchRblApplicationFromApiAndBas($bankingAccountId);

        return $response;
    }
}
