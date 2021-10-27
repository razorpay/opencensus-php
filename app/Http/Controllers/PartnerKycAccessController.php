<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Partner;

class PartnerKycAccessController extends Controller
{
    protected $service = Partner\KycAccessState\Service::class;

    public function createRequestForKyc()
    {
        $input = Request::all();

        $response = $this->service()->createRequestForSubMerchantKyc($input);

        return ApiResponse::json($response);
    }

    public function confirmRequestForKyc()
    {
        $input = Request::all();

        $data = $this->service()->confirmRequestForSubMerchantKyc($input);

        $response = ApiResponse::json($data);

        return $this->addCorsHeaders($response);
    }

    public function confirmRequestForKycCors()
    {
        $response = ApiResponse::json([]);

        return $this->addCorsHeaders($response);
    }

    protected function addCorsHeaders($response)
    {
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS' );
        $response->headers->set('Access-Control-Allow-Origin', $this->app['config']->get('app.razorpay_website_url'));
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }

    public function revokeKycAccess()
    {
        $input = Request::all();

        $response = $this->service()->revokeKycAccess($input);

        return ApiResponse::json($response);
    }
}
