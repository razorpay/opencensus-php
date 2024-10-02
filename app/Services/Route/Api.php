<?php

namespace RZP\Services\Route;

use App;
use Razorpay\Edge\Passport\Passport;
use RZP\Exception;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Payment;
use RZP\Exception\RuntimeException;

class Api extends Base
{
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * To create a direct transfer in Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createDirectTransfer(array $input) : array
    {
        $jwt = $this->getJwtTokenFromRequestHeader();

        $this->setCustomHeaders([Passport::PASSPORT_JWT_V1 => $jwt]);

        return $this->sendRequest(Constant::DIRECT_TRANSFER_ENDPOINT, Requests::POST, $input);
    }

    /**
     * To fetch a transfer by ID from Route microservice
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchTransferById(string $transferId) : Transfer\Entity
    {
        $response = $this->fetchTransferByIdInternalRequest($transferId);

        $transfer = $this->forceFillTransferFromResponse($response);

        return $this->loadRelatedEntity($transfer);
    }

    protected function fetchTransferByIdInternalRequest(string $transferId) : array
    {
        $endpoint = sprintf(Constant::TRANSFER_FETCH_BY_ID_ENDPOINT, $transferId);

        $response = $this->sendRequest($endpoint, Requests::GET);

        return $response;
    }

    /**
     * To fetch a payment (dummy payment) by transfer ID and account ID from Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchPaymentByTransferIdAndAccountId(string $transferId, string $accountId): ?Payment\Entity
    {
        $response = $this->fetchPaymentByTransferIdAndAccountIdInternalRequest($transferId, $accountId);

        $payment = (new Payment\Entity)->forceFill($response);

        $payment->setExternal(true);

        $repo = App::getFacadeRoot()['repo'];

        $transfer = $repo->transfer->findOrFail($payment->getTransferId());

        $payment->transfer()->associate($transfer);

        return $payment;
    }

    protected function fetchPaymentByTransferIdAndAccountIdInternalRequest(string $transferId, string $accountId): array
    {
        $queryParams = http_build_query(['transfer_id' => $transferId, 'account_id' => $accountId]);

        $endpoint = Constant::PAYMENT_FETCH_ENDPOINT . '?' . $queryParams;

        $response = $this->sendRequest($endpoint, Requests::GET);

        return  $response['payment'];
    }

    /**
     * To fetch a payment (dummy payment) by ID from Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchPaymentById(string $paymentId): ?Payment\Entity
    {
        $response = $this->fetchPaymentByIdInternalRequest($paymentId);

        $payment = (new Payment\Entity)->forceFill($response);

        $payment->setExternal(true);

        $repo = App::getFacadeRoot()['repo'];

        $transfer = $repo->transfer->findOrFail($payment->getTransferId());

        $payment->transfer()->associate($transfer);

        return $payment;
    }

    protected function fetchPaymentByIdInternalRequest(string $paymentId): array
    {
        $queryParams = http_build_query(['id' => $paymentId]);

        $endpoint = Constant::PAYMENT_FETCH_ENDPOINT . '?' . $queryParams;

        $response = $this->sendRequest($endpoint, Requests::GET);

        return $response['payment'];
    }

    /**
     * To save the API schema transfer in Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function saveApiTransfer(string $transferId, array $input) : array
    {
        $endpoint = sprintf(Constant::SAVE_API_TRANSFER_ENDPOINT, $transferId);

        return $this->sendRequest($endpoint, Requests::POST, $input);
    }

    /**
     * To save the API schema payment in Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function saveApiPayment(string $paymentId, array $input) : array
    {
        $endpoint = sprintf(Constant::SAVE_API_PAYMENT_ENDPOINT, $paymentId);

        return $this->sendRequest($endpoint, Requests::POST, $input);
    }

    private function forceFillTransferFromResponse($response)
    {
        if (empty($response) === false)
        {
            $transfer = (new Transfer\Entity());

            $transfer->forceFill($response);

            $transfer->setExternal(true);

            $transfer->generate($response);

            return $transfer;
        }
        return null;
    }

    private function loadRelatedEntity($transfer)
    {
        return $transfer;
    }

}
