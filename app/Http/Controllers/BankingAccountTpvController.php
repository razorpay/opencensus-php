<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\BankingAccountTpv;

class BankingAccountTpvController extends Controller
{
    protected $service = BankingAccountTpv\Service::class;

    public function adminCreateTpv()
    {
        $input = Request::all();

        $data = $this->service()->adminCreateTpv($input);

        return ApiResponse::json($data);
    }

    public function adminEditTpv(string $id)
    {
        $input = Request::all();

        $data = $this->service()->adminEditTpv($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchMerchantTpvs()
    {
        $data = $this->service()->fetchMerchantTpvs();

        return ApiResponse::json($data);
    }

    public function fetchMerchantTpvsWithFav($mid)
    {
        $input = Request::all();

        $data = $this->service()->fetchMerchantTpvsWithFav($input, $mid);

        return ApiResponse::json($data);
    }

    public function manualAutoApproveTpv()
    {
        $input = Request::all();

        $data = $this->service()->manualAutoApproveTpv($input);

        return ApiResponse::json($data);
    }

    public function createTpvFromXDashboard()
    {
        $input = Request::all();

        $data = $this->service()->createTpvFromXDashboard($input);

        return ApiResponse::json($data);
    }
}
