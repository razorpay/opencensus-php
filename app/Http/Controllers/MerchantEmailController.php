<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Gateway;

class MerchantEmailController extends Controller
{
    /**
     * Fetch a merchant's additional different type of emails from databases based on MerchantId
     *
     * @param $merchantId
     *
     * @return mixed
     */
    public function fetchMerchantEmails($merchantId)
    {
        $data = $this->service()->fetchEmails($merchantId);

        return ApiResponse::json($data);
    }

    /**
     * CREATE and EDIT  a merchant's additional different type of emails
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function postMerchantEmails($merchantId)
    {
        $input = Request::all();

        $data  = $this->service()->createEmails($merchantId, $input);

        return ApiResponse::json($data);
    }

    /**
     * DELETE  a merchant's additional different type of emails and return '1' if  delete operation successful else throw error
     *
     * @param string $merchantId
     * @param string $type
     *
     * @return mixed
     */
    public function deleteMerchantEmails($merchantId, $type)
    {
        $data = $this->service()->deleteEmailByType($merchantId, $type);

        return ApiResponse::json($data);
    }

    /**
     * Fetch a merchant's additional single type of emails from databases based on Merchant ID and type
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function fetchMerchantEmailByType($merchantId, $type)
    {
        $data = $this->service()->fetchEmailByType($merchantId, $type);

        return ApiResponse::json($data);
    }

    public function proxyGetSupportDetails()
    {
        $merchant =  $this->ba->getMerchant();

        $data = $this->service()->getSupportDetails($merchant);

        return ApiResponse::json($data);
    }

    public function proxyCreateSupportDetails()
    {
        $input = Request::all();

        $merchant =  $this->ba->getMerchant();

        $data = $this->service()->proxyCreateSupportDetails($merchant, $input);

        return ApiResponse::json($data);
    }

    public function proxyEditSupportDetails()
    {
        $input = Request::all();

        $merchant =  $this->ba->getMerchant();

        $data = $this->service()->proxyEditSupportDetails($merchant, $input);

        return ApiResponse::json($data);
    }
}
