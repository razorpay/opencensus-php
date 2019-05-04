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

        $this->callbackInput->push($transaction->getPublicId());

        return $this->callGateway();
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

        $this->callbackInput->push($transaction->getPublicId());

        return $this->callGateway();
    }

    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->putMany([
            Entity::TRANSACTION     => $transaction,
            Entity::PAYER           => $transaction->payer,
            Entity::PAYEE           => $transaction->payee,
            Entity::BANK_ACCOUNT    => $transaction->bankAccount,
            Entity::UPI             => $transaction->upi,
        ]);

        $this->callbackInput->push($transaction->getPublicId());

        return $this->callGateway();
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
        ]);

        return $this->callGateway();
    }

    public function authorizeTransactionSuccess(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_TRANSACTION_SUCCESS, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::TRANSACTION)[Entity::ID]);

        $this->core->updateUpi($transaction, $this->input->get(Entity::UPI));

        $this->updateTransactionStatus($transaction, $this->input->get(Entity::TRANSACTION));

        return $transaction->toArrayPublic();
    }

    public function initiateReject(array $input): array
    {
        $this->initialize(Action::INITIATE_REJECT, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::ID));

        $this->gatewayInput->putMany([
            Entity::TRANSACTION     => $transaction,
            Entity::PAYER           => $transaction->payer,
            Entity::PAYEE           => $transaction->payee,
            Entity::BANK_ACCOUNT    => $transaction->bankAccount,
            Entity::UPI             => $transaction->upi,
        ]);

        $this->callbackInput->push($transaction->getPublicId());

        return $this->callGateway();
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

        return $transaction->toArrayPublic();
    }

    protected function updateTransactionStatus(Entity $transaction, array $input)
    {
        return $transaction;
    }
}
