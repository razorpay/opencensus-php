<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class BankingAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function fetchBankingDetailsForMerchant()
    {
        $entities = $this->service()->fetchBankingDetailsForMerchant();

        return ApiResponse::json($entities);
    }
}
