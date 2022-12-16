<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\BankAccount;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Transaction\Mode;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Concern;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\UpiTransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionConcernTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionRequestTransformer;


class TransactionGateway extends Gateway implements Contracts\TransactionGateway
{
    protected $actionMap = TransactionAction::MAP;

    public function initiatePay(Response $response)
    {
        $this->initiateAuthorize($response);
    }

    public function initiateCollect(Response $response)
    {
        $request = $this->initiateSdkRequest(TransactionAction::REQUEST_MONEY);

        $transformer = new TransactionRequestTransformer($this->input->toArray());

        $transformer->put(Fields::ACTION, TransactionAction::REQUEST_MONEY);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::UPI_REQUEST_ID, $this->getUpiRequestId());

        $request->merge($transformer->transform());

        $response->setRequest($request);
    }

    public function fetchAll(Response $response)
    {

    }

    public function fetch(Response $response)
    {

    }

    public function initiateAuthorize(Response $response)
    {
        $transformer = new TransactionRequestTransformer($this->input->toArray());

        $transformer->put('context', [
            'handle_code'   => $this->getContextHandleCode()
        ]);

        $action = $transformer->transformAction();

        $transformer->put(Fields::ACTION, $action);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::UPI_REQUEST_ID, $this->getUpiRequestId());

        $request = $this->initiateSdkRequest($action);

        $request->merge($transformer->transform());

        $request->mergeUdf($transformer->transformUdf());

        $response->setRequest($request);
    }

    public function authorizeTransaction(Response $response)
    {
        $sdk = $this->handleInputSdk();
        $callback = $this->handleSdkCallback();

        $transaction = $this->input->get(Entity::TRANSACTION);

        $transformer = new UpiTransactionTransformer($sdk->toArray(), $callback->get(Fields::ACTION));
        $transformer->put(Fields::MERCHANT_REQUEST_ID, $this->getMerchantRequestId($transaction));

        $upi = $transformer->transformSdk();

        $transformer = new TransactionTransformer($upi, $callback->get(Fields::ACTION));

        $transaction = $transformer->transformSdk();

        $response->setData([
            Entity::TRANSACTION => $transaction,
            Entity::UPI         => $upi,
        ]);
    }

    public function initiateReject(Response $response)
    {
        $request = $this->initiateSdkRequest(TransactionAction::DECLINE_COLLECT);

        $transformer = new TransactionRequestTransformer($this->input->toArray());

        $transformer->put(Fields::ACTION, TransactionAction::DECLINE_COLLECT);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::UPI_REQUEST_ID, $this->getUpiRequestId());

        $request->merge($transformer->transform());

        $response->setRequest($request);
    }

    public function reject(Response $response)
    {

    }

    public function raiseConcern(Response $response)
    {
        $request = $this->initiateS2sRequest(TransactionAction::RAISE_QUERY);

        $upi = $this->input->get(Entity::UPI);
        $concern = $this->input->get(Entity::CONCERN);

        $transformer = new TransactionConcernTransformer($this->input->toArray(), $this->action);

        $concernResponse = $transformer->transformInternal();

        if (is_null($concernResponse) === true)
        {
            $request->merge([
                Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                Fields::UPI_REQUEST_ID          => $upi[UpiTransaction\Entity::NETWORK_TRANSACTION_ID],
                Fields::UPI_RESPONSE_ID         => $upi[UpiTransaction\Entity::RRN],
                Fields::QUERY_COMMENT           => $concern[Concern\Entity::COMMENT],
            ]);

            // Will be used in callback
            $request->mergeUdf([
                Entity::ID      => $concern[Concern\Entity::ID],
                Entity::HANDLE  => $concern[Concern\Entity::HANDLE],
            ]);

            $s2s = $this->sendS2sRequest($request);

            $this->handleGatewayResponseCode($s2s[Fields::PAYLOAD]);

            $transformer = new TransactionConcernTransformer($s2s[Fields::PAYLOAD], $this->action);

            $transformer->put(Entity::ID, $concern[Entity::ID]);
            $transformer->put(Concern\Entity::TRANSACTION_ID, $concern[Concern\Entity::TRANSACTION_ID]);

            $concernResponse = $transformer->transform();
        }

        $response->setData([
            Entity::CONCERN => $concernResponse,
        ]);
    }

    public function concernStatus(Response $response)
    {
        $request = $this->initiateS2sRequest(TransactionAction::QUERY_STATUS);

        $upi = $this->input->get(Entity::UPI);
        $concern = $this->input->get(Entity::CONCERN);

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
            Fields::UPI_REQUEST_ID          => $upi[UpiTransaction\Entity::NETWORK_TRANSACTION_ID],
            Fields::UPI_RESPONSE_ID         => $upi[UpiTransaction\Entity::RRN],
        ]);

        $s2s = $this->sendS2sRequest($request);

        $transformer = new TransactionConcernTransformer($s2s[Fields::PAYLOAD], $this->action);

        $transformer->put(Entity::ID, $concern[Entity::ID]);
        $transformer->put(Concern\Entity::TRANSACTION_ID, $concern[Concern\Entity::TRANSACTION_ID]);

        $response->setData([
            Entity::CONCERN => $transformer->transform(),
        ]);
    }

    protected function getTransactionRequestId()
    {
        // the request id needs to be of 35 length
        return 'TXN' . bin2hex(random_bytes(16));
    }

    protected function getMerchantRequestId($transaction)
    {
        return 'RAZORPAY' . str_pad($transaction->get(Entity::ID), 27, '0', STR_PAD_LEFT);
    }
}
