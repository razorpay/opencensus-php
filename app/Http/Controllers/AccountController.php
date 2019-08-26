<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant;

class AccountController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Merchant\Account\Service::class;

    public function createLinkedAccount()
    {
        $input = Request::all();

        $entity = $this->service()->createLinkedAccount($input);

        return ApiResponse::json($entity);
    }

    /**
     * Return all settlement destinations.
     * Currently, bank accounts are the only settlement destinations, later,
     * more types (e.g. wallet, upi etc) can come.
     *
     * @param string $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchSettlementDestinations(string $accountId)
    {
        $response = $this->service()->fetchSettlementDestinations($accountId);

        return ApiResponse::json($response);
    }

    /**
     * Adds / updates a bank account that is linked to the merchant account
     *
     * @param string $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrChangeBankAccount(string $accountId)
    {
        $response = $this->service()->createOrChangeBankAccount($accountId, $this->input);

        return ApiResponse::json($response);
    }

    public function listLinkedAccounts()
    {
        $input = Request::all();

        $response = $this->service()->listLinkedAccounts($input);

        return ApiResponse::json($response);
    }

    public function createAccount()
    {
        $input = Request::all();

        $response = $this->service()->createAccount($input);

        return ApiResponse::json($response);
    }

    /**
     * @param string $accountId
     * @param string $action
     *
     * @return mixed
     */
    public function performAction(string $accountId, string $action)
    {
        $response = $this->service()->performAction($accountId, $action);

        return ApiResponse::json($response);
    }

    public function fetchAccount(string $accountId)
    {
        $response = $this->service()->fetchAccount($accountId);

        return ApiResponse::json($response);
    }

    public function editAccount(string $accountId)
    {
        $input = Request::all();

        $response = $this->service()->editAccount($accountId, $input);

        return ApiResponse::json($response);
    }

    public function listAccounts()
    {
        $input = Request::all();

        $response = $this->service()->listAccounts($input);

        return ApiResponse::json($response);
    }
}
