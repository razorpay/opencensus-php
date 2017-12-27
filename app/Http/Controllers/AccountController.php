<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant;

class AccountController extends Controller
{
    use Traits\HasCrudMethods;

	protected $service = Merchant\Account\Service::class;

    public function postAccountFiles(string $id)
    {
        $input = Request::all();

        $response = $this->service()->uploadFiles($id, $input);

        return ApiResponse::json($response);
    }

    public function patchAccountDetails(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updateDetails($id, $input);

        return ApiResponse::json($response);
    }

    public function fetchSettlementDestinations(string $id)
    {
        $response = $this->service()->getSettlementDestinations($id);

        return ApiResponse::json($response);
    }

    public function postBankAccounts(string $id)
    {
        $input = Request::all();

        $response = $this->service()->addSettlementDestination($id, $input);

        return ApiResponse::json($response);
    }
}
