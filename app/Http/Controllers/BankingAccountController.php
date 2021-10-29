<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Entity as E;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;


class BankingAccountController extends Controller
{
    /** @var ActivationDetail\Service $activationDetailService  */
    protected $activationDetailService;

    public function __construct()
    {
        $this->activationDetailService = resolve(ActivationDetail\Service::class);

        parent::__construct();
    }


    use Traits\HasCrudMethods;

    public function createDashboard()
    {
        $input = Request::all();

        $response = $this->service()->createByMerchant($input);

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
        $response = $this->activationDetailService->updateForBankingAccount($bankingAccountId, $this->input);

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

    public function getBankingAccountSalesPOCs()
    {
        $response = $this->service()->getBankingAccountSalesPOCs();

        return ApiResponse::json($response);
    }

    public function sendDailyUpdatesToAuditors(string $auditorType)
    {
        $response = $this->service()->sendDailyUpdatesToAuditors($auditorType);

        return ApiResponse::json($response);
    }

    public function checkPincodeServiceabilityByRBL($pincode)
    {
        $data =  $this->service()->CheckServiceableByRBL($pincode, true);

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
}
