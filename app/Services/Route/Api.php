<?php

namespace RZP\Services\Route;

use App;
use Request;
use Razorpay\Edge\Passport\Passport;
use RZP\Exception;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Models\Base\PublicCollection;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Reversal;
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
        if ( (new Config())->shouldCreateNewPassportToken() ){

            $this->addNewPassportToken();

        }
        else {

            $this->addPassportToken();

        }

        $this->setDirectTransferIdempotencyHeaderIfPresent();

        return $this->sendRequest(Constant::DIRECT_TRANSFER_ENDPOINT, Requests::POST, $input);
    }

    /**
     * To patch a transfer by ID in Route microservice
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function patchTransfer(string $transferId, array $input) : array
    {
        $response = $this->patchTransferByInternalRequest($transferId,  $input);

        return $response;
    }

    protected function patchTransferByInternalRequest(string $transferId, array $input) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() ){
            $this->addNewPassportToken();
        }
        else {
            $this->addPassportToken();

        }
        $endpoint = sprintf(Constant::PATCH_TRANSFER_ROUTE_ENDPOINT, $transferId);
        $response = $this->sendRequest($endpoint, Requests::PATCH,$input);

        return $response;
    }

    public function createPaymentTransfer(string $paymentId, array $input) : array
    {
        $routeName = $this->app['api.route']->getCurrentRouteName() ?? '';

        $isBatchApi = false;

        if (in_array($routeName, ['payment_transfer_batch', 'payment_transfer_retry_batch']))
        {
            $isBatchApi = true;
        }

        if ((new Config())->shouldCreateNewPassportToken() || $isBatchApi)
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $endpoint = sprintf(Constant::PAYMENT_TRANSFER_ENDPOINT, $paymentId);

        return $this->sendRequest($endpoint, Requests::POST, $input);
    }

    public function createTransferReversal(string $transferId, array $input) : array
    {
        if ((new Config())->shouldCreateNewPassportToken())
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $endpoint = sprintf(Constant::TRANSFER_REVERSAL_ENDPOINT, $transferId);

        return $this->sendRequest($endpoint, Requests::POST, $input);
    }

    /**
     * To fetch a transfer by ID from Route microservice
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchTransferById(string $transferId, string $merchantId = null) : Transfer\Entity
    {
        $response = $this->fetchTransferByIdInternalRequest($transferId, $merchantId);

        $transfer = $this->forceFillTransferFromResponse($response);

        return $this->loadRelatedEntity($transfer);
    }

    protected function fetchTransferByIdInternalRequest(string $transferId, string $merchantId = null) : array
    {
        $endpoint = sprintf(Constant::TRANSFER_FETCH_BY_ID_ENDPOINT, $transferId);

        if (empty($merchantId) === false)
        {
            $queryParams = http_build_query(['merchant_id' => $merchantId]);

            $endpoint = $endpoint . '?' . $queryParams;
        }

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
     * To fetch a reversal by ID from Route microservice
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchReversalById(string $reversalId, string $merchantId = null) : Transfer\Entity
    {
        $response = $this->fetchReversalByIdInternalRequest($reversalId, $merchantId);

        $transfer = $this->forceFillReversalFromResponse($response);

        return $this->loadRelatedEntity($transfer);
    }

    protected function fetchReversalByIdInternalRequest(string $reversalId, string $merchantId = null) : array
    {
        $endpoint = sprintf(Constant::REVERSAL_FETCH_BY_ID_ENDPOINT, $reversalId);

        if (empty($merchantId) === false)
        {
            $queryParams = http_build_query(['merchant_id' => $merchantId]);

            $endpoint = $endpoint . '?' . $queryParams;
        }

        $response = $this->sendRequest($endpoint, Requests::GET);

        return $response;
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

    /**
     * To save the DCS Features
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function updateFeatures(array $input) : array
    {
        return $this->sendRequest(Constant::UPDATE_FEATURE_ENDPOINT, Requests::POST, $input);
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

    private function forceFillReversalFromResponse($response)
    {
        if (empty($response) === false)
        {
            $reversal = (new Reversal\Entity());

            $reversal->forceFill($response);

            $reversal->setExternal(true);

            $reversal->generate($response);

            return $reversal;
        }
        return null;
    }


    private function loadRelatedEntity($transfer)
    {
        return $transfer;
    }

    private function setDirectTransferIdempotencyHeaderIfPresent()
    {
        $idemptencyHeader = Request::header(RequestHeader::X_TRANSFER_IDEMPOTENCY) ?? null;

        if (empty($idemptencyHeader) === false)
        {
            $this->setCustomHeaders([RequestHeader::X_TRANSFER_IDEMPOTENCY => $idemptencyHeader]);
        }
    }

    /**
     * To fetch a transfer payment entity by ID from Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchTransferPaymentByPaymentId(string $paymentId): ?Transfer\Payment\Entity
    {
        $response = $this->fetchTransferPaymentByPaymentIdInternalRequest($paymentId);

        $transferPayment = (new Transfer\Payment\Entity)->forceFill($response);

        $transferPayment->setExternal(true);

        return $transferPayment;
    }

    protected function fetchTransferPaymentByPaymentIdInternalRequest(string $paymentId): array
    {
        $endpoint = sprintf(Constant::TRANSFER_PAYMENT_FETCH_ENDPOINT, $paymentId);

        $response = $this->sendRequest($endpoint, Requests::GET);

        return $response['source_payment'];
    }

    /**
     * To save the API schema transfer_payment in Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function saveApiTransferPayment(string $paymentId, array $input) : array
    {
        $endpoint = sprintf(Constant::SAVE_API_TRANSFER_PAYMENT_ENDPOINT, $paymentId);

        return $this->sendRequest($endpoint, Requests::POST, $input);
    }

    /**
     * To decrement amount_transferred of API schema transfer_payment in Route microservice
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function decrementTransferPayment(string $paymentId, array $input) : array
    {
        $endpoint = sprintf(Constant::DECREMENT_TRANSFER_PAYMENT_ENDPOINT, $paymentId);

        return $this->sendRequest($endpoint, Requests::POST, $input);
    }

    /**
     * To fetch a transfer by ID from Route microservice
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function fetchTransfersBySourceId(string $merchantId, string $sourceId, int $count=0, int $skip=0) : PublicCollection
    {
        $response = $this->fetchTransfersBySourceIdInternalRequest($merchantId, $sourceId, $count, $skip);

        $transfersCollection = new PublicCollection();

        foreach ($response['items'] as $item)
        {
            $transfer = $this->forceFillTransferFromResponse($item);

            $transfer = $this->loadRelatedEntity($transfer);

            $transfersCollection->add($transfer);
        }

        return $transfersCollection;
    }

    protected function fetchTransfersBySourceIdInternalRequest(string $merchantId, string $sourceId, int $count, int $skip) : array
    {
        $queryParams = http_build_query
        ([
            'merchant_id' => $merchantId,
            'source_id' => $sourceId,
            'count' => $count,
            'skip' => $skip
        ]);

        $endpoint = Constant::TRANSFER_FETCH_MULTIPLE_INTERNAL_ENDPOINT . '?' . $queryParams;

        $response = $this->sendRequest($endpoint, Requests::GET);

        return $response;
    }

    public function fetchMultipleTransfers(array $input) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() )
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $queryParams = [
            'count' => $input['count'] ?? 0,
            'skip' => $input['skip'] ?? 0,
            'from' => $input['from'] ?? null,
            'to' => $input['to'] ?? null,
            'recipient_settlement_id' => $input['recipient_settlement_id'] ?? null,
            'expand' => $input['expand'] ?? []
        ];

        $queryParams = array_filter($queryParams, function ($value) {
            return $value !== null;
        });

        // Using http_build_query with PHP_QUERY_RFC3986 to preserve array brackets (e.g., expand[]=value)
        $queryString = http_build_query($queryParams, '', '&&', PHP_QUERY_RFC3986);

        $endpoint = Constant::TRANSFER_FETCH_MULTIPLE_ENDPOINT . '?' . $queryString;

        $this->trace->info(TraceCode::ROUTE_FETCH_TRANSFER_QUERY, [
            'query_string' => $queryString,
            'query_params' => $queryParams,
            'endpoint' => $endpoint
        ]);

        return $this->sendRequest($endpoint, Requests::GET);
    }

    public function fetchTransferByIDExternal($transferID,array $input) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() )
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $queryParams = [
            'expand' => $input['expand'] ?? []
        ];

        $queryParams = array_filter($queryParams, function ($value) {
            return $value !== null;
        });

        $queryString = http_build_query($queryParams, '', '&&', PHP_QUERY_RFC3986);

        $endpoint = sprintf(Constant::TRANSFER_FETCH_ENDPOINT, $transferID);
        $endpoint = $endpoint. '?' . $queryString;

        $this->trace->info(TraceCode::ROUTE_FETCH_TRANSFER_QUERY, [
            'query_string' => $queryString,
            'query_params' => $queryParams,
            'endpoint' => $endpoint
        ]);

        return $this->sendRequest($endpoint, Requests::GET);
    }

    public function fetchByPaymentID($paymentID) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() )
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $endpoint = sprintf(Constant::TRANSFER_FETCH_BY_PAYMENT_ENDPOINT, 'pay_'.$paymentID);

        return $this->sendRequest($endpoint, Requests::GET);
    }

    public function fetchByOrderID($orderID) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() )
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $endpoint = sprintf(Constant::TRANSFER_FETCH_BY_ORDER_ENDPOINT, 'order_'.$orderID);


        return $this->sendRequest($endpoint, Requests::GET);
    }

    public function fetchLinkedAccountTransfersByID($id) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() )
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $endpoint = sprintf(Constant::LA_TRANSFER_FETCH_BY_ID_ENDPOINT, $id);

        return $this->sendRequest($endpoint, Requests::GET);
    }

    public function fetchLinkedAccountTransfersByPaymentID($id,$input) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() )
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }

        $endpoint = sprintf(Constant::LA_TRANSFER_FETCH_BY_PAYMENT_ID_ENDPOINT, $id);

        $queryParams = [
            'transfer_id' => $input['id']
        ];

        $queryParams = array_filter($queryParams, function ($value) {
            return $value !== null;
        });

        // Using http_build_query with PHP_QUERY_RFC3986 to preserve array brackets (e.g., expand[]=value)
        $queryString = http_build_query($queryParams, '', '&&', PHP_QUERY_RFC3986);

        $endpoint = $endpoint. '?' . $queryString;

        return $this->sendRequest($endpoint, Requests::GET);
    }

    public function fetchLinkedAccountTransfers($input) : array
    {
        if ( (new Config())->shouldCreateNewPassportToken() )
        {
            $this->addNewPassportToken();
        }
        else
        {
            $this->addPassportToken();
        }


        $queryParams = [
            'count' => $input['count'] ?? 0,
            'skip' => $input['skip'] ?? 0,
            'from' => $input['from'] ?? null,
            'to' => $input['to'] ?? null,
            'recipient_settlement_id' => $input['recipient_settlement_id'] ?? null,
            'expand' => $input['expand'] ?? []
        ];

        $queryParams = array_filter($queryParams, function ($value) {
            return $value !== null;
        });

        // Using http_build_query with PHP_QUERY_RFC3986 to preserve array brackets (e.g., expand[]=value)
        $queryString = http_build_query($queryParams, '', '&&', PHP_QUERY_RFC3986);

        $endpoint = Constant::LA_TRANSFER_FETCH_MULTIPLE_ENDPOINT . '?' . $queryString;

        $this->trace->info(TraceCode::ROUTE_FETCH_TRANSFER_QUERY, [
            'query_string' => $queryString,
            'query_params' => $queryParams,
            'endpoint' => $endpoint
        ]);

        return $this->sendRequest($endpoint, Requests::GET);

    }
}
