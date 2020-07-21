<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\BankingAccount\Service;
use RZP\Constants\Entity as E;
use RZP\Models\BankingAccount;

class BankingAccountController extends Controller
{
    use Traits\HasCrudMethods;

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

    public function postCreateActivationCommentFromBatchService()
    {
        $input = Request::all();

        $response = $this->service(E::BANKING_ACCOUNT_COMMENT)->createFromBatchService($input);

        return ApiResponse::json($response);
    }
}
