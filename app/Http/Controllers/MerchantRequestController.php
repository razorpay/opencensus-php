<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Constants\Entity as E;

class MerchantRequestController extends Controller
{
    public function getAll()
    {
        $data = $this->service(E::MERCHANT_REQUEST)->getAll($this->input);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $data = $this->service(E::MERCHANT_REQUEST)->get($id);

        return ApiResponse::json($data);
    }

    public function getForFeatureTypeAndName(string $type, string $featureName)
    {
        $data = $this->service(E::MERCHANT_REQUEST)->getForFeatureTypeAndName($type, $featureName);

        return ApiResponse::json($data);
    }

    public function getStatusLog(string $id)
    {
        $data = $this->service(E::MERCHANT_REQUEST)->getStatusLog($id, $this->input);

        return ApiResponse::json($data);
    }

    public function create()
    {
        $response = $this->service(E::MERCHANT_REQUEST)->create($this->input);

        return ApiResponse::json($response);
    }

    public function update(string $id)
    {
        $response = $this->service(E::MERCHANT_REQUEST)->update($id, $this->input);

        return ApiResponse::json($response);
    }

    public function bulkUpdate()
    {
        $response = $this->service(E::MERCHANT_REQUEST)->bulkUpdate($this->input);

        return ApiResponse::json($response);
    }

    public function getRejectionReasons()
    {
        $response = $this->service(E::MERCHANT_REQUEST)->getRejectionReasons();

        return ApiResponse::json($response);
    }

    public function issueOneTimeToken()
    {
        $response = $this->service(E::MERCHANT_REQUEST)->issueOneTimeToken();

        return ApiResponse::json($response);
    }
}
