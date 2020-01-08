<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class PayoutLinkController extends Controller
{
    use Traits\HasCrudMethods;

    public function update(string $id)
    {
        return ApiResponse::json('Not Supported');
    }

    public function delete(string $id)
    {
        return ApiResponse::json('Not Supported');
    }

    public function getFundAccountsOfContact(string $payoutLinkId)
    {
        $response = $this->service()->getFundAccountsOfContact($payoutLinkId, $this->input);

        return ApiResponse::json($response);
    }

    /**
     * Route to update the merchant level settings for payout links
     * @param $merchantId
     * @return
     */
    public function updateSettings($merchantId)
    {
        $response = $this->service()->updateSettings($merchantId, $this->input);

        return ApiResponse::json($response);
    }

    /**
     * Route to get the merchant level settings for payout links
     * @param $merchantId
     * @return
     */
    public function getSettings($merchantId)
    {
        $response = $this->service()->getSettings($merchantId, $this->input);

        return ApiResponse::json($response);
    }

    /**
     * This api call will take the fund-account details, and initiate the payout
     * @param string $payoutLinkId
     * @return array
     */
    public function initiate(string $payoutLinkId)
    {
        $response = $this->service()->initiate($payoutLinkId, $this->input);

        return ApiResponse::json($response);
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId)
    {
        $response = $this->service()->generateAndSendCustomerOtp($payoutLinkId, $this->input);

        return ApiResponse::json($response);
    }

    public function verifyCustomerOtp(string $payoutLinkId)
    {
        $response = $this->service()->verifyCustomerOtp($payoutLinkId, $this->input);

        return ApiResponse::json($response);
    }

    public function viewHostedPage($payoutLinkId)
    {
        $response = $this->service()->viewHostedPage($payoutLinkId);

        return $response;
    }

    public function cancel(string $payoutLinkId)
    {
        $data = $this->service()->cancel($payoutLinkId);

        return ApiResponse::json($data);
    }
}
