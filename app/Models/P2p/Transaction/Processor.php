<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi;
use RZP\Http\Controllers\P2p\Requests;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function initiatePay(array $input): array
    {
        $this->initialize(Action::INITIATE_PAY, $input, true);

        $properties = new Properties($this->context(), $this->action, $this->input);

        $transaction = $this->core->build($this->input->toArray());

        $properties->attachToTransaction($transaction);

        $this->repo()->saveOrFail($transaction);

        $upi = $this->core->createUpi($transaction, $this->action);

        $this->gatewayInput->putMany([
            Entity::TRANSACTION     => $transaction,
            Entity::PAYER           => $transaction->payer,
            Entity::PAYEE           => $transaction->payee,
            Entity::BANK_ACCOUNT    => $transaction->bankAccount,
            Entity::UPI             => $transaction->upi,
        ]);

        return $this->callGateway();
    }

    public function initiatePaySuccess(array $input): array
    {
        $this->initialize(Action::INITIATE_PAY_SUCCESS, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::TRANSACTION)[Entity::ID]);

        if ($transaction->upi->getId() === $this->input->get(Entity::UPI)[UpiTransaction\Entity::TRANSACTION_ID])
        {
            $error = '';// throw exception
        }

        $this->core->updateUpi($transaction, $this->input->get(Entity::UPI));

        $transaction->setInternalStatus(Status::PENDING);

        $this->repo()->saveOrFail($transaction);

        $clientLibrary = new Upi\ClientLibrary();

        $clientLibrary->setDevice($this->context()->getDevice());
        $clientLibrary->setTransaction($transaction);

        return [
            Entity::REQUEST       => $this->getTransactionRequest($transaction),
            Upi\ClientLibrary::CL => $clientLibrary->toArrayPublic(),
        ];
    }

    public function initiateCollect(array $input): array
    {
        $this->initialize(Action::INITIATE_COLLECT, $input, true);

        $properties = new Properties($this->context(), $this->action, $this->input);

        $transaction = $this->core->build($this->input->toArray());

        $properties->attachToTransaction($transaction);

        $this->repo()->saveOrFail($transaction);

        $upi = $this->core->createUpi($transaction, $this->action);

        $this->gatewayInput->putMany([
            Entity::TRANSACTION     => $transaction,
            Entity::PAYER           => $transaction->payer,
            Entity::PAYEE           => $transaction->payee,
            Entity::BANK_ACCOUNT    => $transaction->bankAccount,
            Entity::UPI             => $transaction->upi,
        ]);

        return $this->callGateway();
    }

    public function initiateCollectSuccess(array $input): array
    {
        $this->initialize(Action::INITIATE_COLLECT_SUCCESS, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::TRANSACTION)[Entity::ID]);

        if ($transaction->upi->getId() === $this->input->get(Entity::UPI)[UpiTransaction\Entity::TRANSACTION_ID])
        {
            $error = '';// throw exception
        }

        $this->core->updateUpi($transaction, $this->input->get(Entity::UPI));

        $transaction->setInternalStatus(Status::INITIATED);

        $this->repo()->saveOrFail($transaction);

        return $this->getTransactionRequest($transaction);
    }

    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::ID));

        $clientLibrary = new Upi\ClientLibrary();

        $clientLibrary->setDevice($this->context()->getDevice());
        $clientLibrary->setTransaction($transaction);

        return [
            Entity::REQUEST       => $this->getTransactionRequest($transaction),
            Upi\ClientLibrary::CL => $clientLibrary->toArrayPublic(),
        ];

        return $this->initiatePay($input);
    }

    public function initiateAuthorizeSuccess(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE_SUCCESS, $input);
    }

    public function authorizeTransaction(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_TRANSACTION, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->putMany([
            Entity::TRANSACTION     => $transaction,
            Entity::PAYER           => $transaction->payer,
            Entity::PAYEE           => $transaction->payee,
            Entity::BANK_ACCOUNT    => $transaction->bankAccount,
            Entity::UPI             => $transaction->upi,
            Entity::CL              => $this->input->get(Entity::CL),
        ]);

        return $this->callGateway();
    }

    public function authorizeTransactionSuccess(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_TRANSACTION_SUCCESS, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::TRANSACTION)[Entity::ID]);

        if ($transaction->upi->getId() === $this->input->get(Entity::UPI)[UpiTransaction\Entity::TRANSACTION_ID])
        {
            $error = '';// throw exception
        }

        $this->core->updateUpi($transaction, $this->input->get(Entity::UPI));

        $transaction->setInternalStatus(Status::INITIATED);

        $this->repo()->saveOrFail($transaction);

        return $this->getTransactionRequest($transaction);
    }

    public function reject(array $input): array
    {
        $this->initialize(Action::REJECT, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::ID));

        $transaction->setStatus(Status::FAILED);
        $transaction->setInternalStatus(Status::REJECTED);

        $this->repo()->saveOrFail($transaction);

        $this->gatewayInput->put(Entity::TRANSACTION, $transaction);

        return $this->callGateway();
    }

    public function rejectSuccess(array $input): array
    {
        $this->initialize(Action::REJECT_SUCCESS, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::TRANSACTION)[Entity::ID]);

        $this->core->updateUpi($transaction, [
            Entity::STATUS  => Status::REJECTED,
        ]);

        return $this->getTransactionRequest($transaction);
    }

    private function getTransactionRequest(Entity $transaction): array
    {
        $transactionId = $transaction->getPublicId();

        switch ($this->action)
        {
            case Action::INITIATE_PAY_SUCCESS:
            case Action::INITIATE_AUTHORIZE:

                return [
                    'method'    => 'post',
                    'url'       => route(Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE, [$transactionId]),
                ];

            case Action::INITIATE_COLLECT_SUCCESS:
            case Action::AUTHORIZE_TRANSACTION_SUCCESS:
            case Action::REJECT_SUCCESS:

                return [
                    // TODO: Need to fix this
                    'status'        => 'pending',//$transaction->getInternalStatus(),
                    'status_url'    => route(Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH, [$transactionId]),
                    'expire_at'     => $transaction->getExpireAt(),
                ];
        }
    }
}
