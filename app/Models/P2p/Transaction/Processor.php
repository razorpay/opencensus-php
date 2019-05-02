<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
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

        $transaction = $this->core->create($properties, $this->input->toArray());

        $this->core->createUpi($transaction, $this->action);

        $this->initiateCallGateway($transaction);

        return $this->callGateway();
    }

    public function initiateCollect(array $input): array
    {
        $this->initialize(Action::INITIATE_COLLECT, $input, true);

        $properties = new Properties($this->context(), $this->action, $this->input);

        $transaction = $this->core->create($properties, $this->input->toArray());

        $this->core->createUpi($transaction, $this->action);

        $this->initiateCallGateway($transaction);

        return $this->callGateway();
    }

    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($transaction);

        return $this->callGateway();
    }

    public function authorizeTransaction(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_TRANSACTION, $input, true);

        $transaction = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($transaction);

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

    public function incomingCollect(array $input): array
    {
        $this->initialize(Action::INCOMING_COLLECT, $input, true);

        $transactionInput = $this->arrayBag($this->input->get(Entity::TRANSACTION));

        $properties = new Properties($this->context(), $this->action, $transactionInput);

        $transaction = $this->core->create($properties, $transactionInput->toArray());

        $this->core->createUpi($transaction, $this->action, $this->input->get(Entity::UPI));

        $this->updateTransactionStatus($transaction, $this->input->get(Entity::TRANSACTION));

        return $transaction->toArrayPublic();
    }

    public function incomingPay(array $input): array
    {
        $this->initialize(Action::INCOMING_PAY, $input, true);

        $transactionInput = $this->arrayBag($this->input->get(Entity::TRANSACTION));

        $properties = new Properties($this->context(), $this->action, $transactionInput);

        $transaction = $this->core->create($properties, $transactionInput->toArray());

        $this->core->createUpi($transaction, $this->action, $this->input->get(Entity::UPI));

        $this->updateTransactionStatus($transaction, $this->input->get(Entity::TRANSACTION));

        return $transaction->toArrayPublic();
    }

    protected function initiateCallGateway(Entity $transaction)
    {
        $this->gatewayInput->putMany([
            Entity::TRANSACTION     => $transaction,
            Entity::PAYER           => $transaction->payer,
            Entity::PAYEE           => $transaction->payee,
            Entity::BANK_ACCOUNT    => $transaction->bankAccount,
            Entity::UPI             => $transaction->upi,
        ]);

        $this->callbackInput->push($transaction->getPublicId());
    }

    protected function updateTransactionStatus(Entity $transaction, array $input)
    {
        switch ($input[Entity::INTERNAL_STATUS])
        {
            case Status::COMPLETED:
                $actions = $this->setTransactionCompleted($transaction, $input);
                break;

            case Status::FAILED:
                $actions = $this->setTransactionFailed($transaction, $input);
                break;

            case Status::PENDING:
            case Status::INITIATED:
                $actions = $this->setTransactionProcessing($transaction, $input);
                break;

            case Status::CREATED:
                $actions = $this->setTransactionCreated($transaction, $input);
                break;

            default:
                throw $this->logicException('Invalid internal status for transaction', [
                    Entity::TRANSACTION     => $input,
                    Entity::ID              => $transaction->getId(),
                ]);
        }

        $this->core->update($transaction, $input);

        $this->dispatchEventIfRequired($actions, $transaction);
    }

    protected function setTransactionCompleted(Entity $transaction, array $input): Actions
    {
        $actions = new Actions();

        if ($transaction->isFailed() === true)
        {
            throw $this->logicException('Transaction can not be marked completed', [
                Entity::TRANSACTION     => $input,
                Entity::ID              => $transaction->getId(),
            ]);
        }
        else if ($transaction->isCompleted() === true)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_TRANSACTION_INVALID_STATE, [
                Entity::TRANSACTION     => $input,
                Entity::ID              => $transaction->getId(),
            ]);
        }

        $transaction->markCompleted();

        return $actions;
    }

    protected function setTransactionFailed(Entity $transaction, array $input): Actions
    {
        $actions = new Actions();

        if ($transaction->isCompleted() === true)
        {
            throw $this->logicException('Transaction can not be marked failed', [
                Entity::TRANSACTION     => $input,
                Entity::ID              => $transaction->getId(),
            ]);
        }
        else if ($transaction->isFailed() === true)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_TRANSACTION_INVALID_STATE, [
                Entity::TRANSACTION     => $input,
                Entity::ID              => $transaction->getId(),
            ]);
        }

        $transaction->setInternalStatus($input[Entity::INTERNAL_STATUS]);

        return $actions;
    }

    protected function setTransactionProcessing(Entity $transaction, array $input): Actions
    {
        $actions = new Actions();

        if (($transaction->isCompleted() === true) or ($transaction->isFailed() === true))
        {
            throw $this->logicException('Transaction can not be marked processing', [
                Entity::TRANSACTION     => $input,
                Entity::ID              => $transaction->getId(),
            ]);
        }

        if ($input[Entity::INTERNAL_STATUS] === Status::INITIATED)
        {
            $transaction->markInitiated();
        }
        else if ($input[Entity::INTERNAL_STATUS] === Status::PENDING)
        {
            $transaction->setInternalStatus(Status::PENDING);
        }

        return $actions;
    }

    public function setTransactionCreated(Entity $transaction, array $input): Actions
    {
        $actions = new Actions();

        if (($transaction->isProcessing() === true) or
            ($transaction->isCompleted() === true) or
            ($transaction->isFailed() === true))
        {
            throw $this->logicException('Transaction can not be marked created', [
                Entity::TRANSACTION     => $input,
                Entity::ID              => $transaction->getId(),
            ]);
        }

        $transaction->setInternalStatus(Status::CREATED);

        return $actions;
    }

    protected function dispatchEventIfRequired(Actions $actions, Entity $transaction)
    {
        if ($actions->hasEvent() === true)
        {
            event($actions->getEvent());
        }
    }
}
