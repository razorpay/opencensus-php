<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Transaction\Mode;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\UpiTransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionRequestTransformer;


class TransactionGateway extends Gateway implements Contracts\TransactionGateway
{
    protected $actionMap = TransactionAction::MAP;

    public function initiatePay(Response $response)
    {
        $request = $this->initiateSdkRequest(TransactionAction::SEND_MONEY);

        $transformer = new TransactionRequestTransformer($this->input->toArray());

        $transformer->put(Fields::ACTION, TransactionAction::SEND_MONEY);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::UPI_REQUEST_ID, $this->getUpiRequestId());

        $request->merge($transformer->transform());

        $response->setRequest($request);
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
        $action = $transformer->transformAction();

        $transformer->put(Fields::ACTION, $action);
        $transformer->put(Fields::MERCHANT_CUSTOMER_ID, $this->getMerchantCustomerId());
        $transformer->put(Fields::TIMESTAMP, $this->getTimeStamp());
        $transformer->put(Fields::UPI_REQUEST_ID, $this->getUpiRequestId());

        $request = $this->initiateSdkRequest($action);

        $request->merge($transformer->transform());

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

    protected function getTransactionRequestId()
    {
        // the request id needs to be of 35 length
        return 'TXN' . bin2hex(random_bytes(16));
    }

    protected function getMerchantRequestId($transaction)
    {
        return 'RZP' . str_pad($transaction->get(Entity::ID), 32, '0', STR_PAD_LEFT);
    }
}
