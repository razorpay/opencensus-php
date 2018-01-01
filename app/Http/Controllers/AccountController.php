<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant;

class AccountController extends Controller
{
    use Traits\HasCrudMethods;

	protected $service = Merchant\Account\Service::class;

    /**
     * Returns all types of settlement destinations
     * Currently, only bank accounts are returned
     *
     * @param string $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchSettlementDestinations(string $accountId)
    {
        $response = $this->service()->getSettlementDestinations($accountId);

        return ApiResponse::json($response);
    }

    /**
     * Adds a new bank account to the merchant account
     *
     * @param string $accountId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postBankAccounts(string $accountId)
    {
        $input = Request::all();

        $response = $this->service()->addSettlementDestination($accountId, $input);

        return ApiResponse::json($response);
    }
}
