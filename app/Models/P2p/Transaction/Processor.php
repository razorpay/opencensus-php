<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Error\P2p\Error;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Base\Upi;
use RZP\Models\P2p\Beneficiary;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

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

        $this->input->put(Entity::INTERNAL_STATUS, Status::CREATED);

        $transaction = $this->createTransaction($this->action, $this->input, new ArrayBag());

        $this->initiateCallGateway($transaction);

        return $this->callGateway();
    }

    public function initiateCollect(array $input): array
    {
        $this->initialize(Action::INITIATE_COLLECT, $input, true);

        $this->input->put(Entity::INTERNAL_STATUS, Status::CREATED);

        $transaction = $this->createTransaction($this->action, $this->input, new ArrayBag());

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

        $transactionInput = $this->input->bag(Entity::TRANSACTION);
        $upiInput         = $this->input->bag(Entity::UPI);

        $transaction = $this->core->fetch($transactionInput->get(Entity::ID));

        $this->updateTransaction($transaction, $transactionInput, $upiInput);

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

    public function incomingCollect(array $input): array
    {
        $this->initialize(Action::INCOMING_COLLECT, $input, true);

        $transactionInput = $this->input->bag(Entity::TRANSACTION);
        $upiInput         = $this->input->bag(Entity::UPI);

        $transaction = $this->createTransaction($this->action, $transactionInput, $upiInput);

        return $transaction->toArrayPublic();
    }

    public function incomingPay(array $input): array
    {
        $this->initialize(Action::INCOMING_PAY, $input, true);

        $transactionInput = $this->input->bag(Entity::TRANSACTION);
        $upiInput         = $this->input->bag(Entity::UPI);

        $transaction = $this->createTransaction($this->action, $transactionInput, $upiInput);

        return $transaction->toArrayPublic();
    }

    public function raiseConcern(array $input): array
    {
        $this->initialize(Action::RAISE_CONCERN, $input, true);

        $transaction = $this->core->fetch($this->input->pull(Entity::ID));

        if ($transaction->concern instanceof Concern\Entity)
        {
            if ($transaction->concern->isClosed() === false)
            {
                throw $this->badRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_REQUEST);
            }
        }

        $this->initiateCallGateway($transaction);

        $concern = (new Concern\Core)->create($transaction, $this->input->toArray());

        $this->gatewayInput->put(Entity::CONCERN, $concern);

        return $this->callGateway();
    }

    public function raiseConcernSuccess(array $input): array
    {
        $this->initialize(Action::RAISE_CONCERN_SUCCESS, $input, true);

        $concernInput = $this->input->get(Entity::CONCERN);

        $concern = (new Concern\Core)->fetch($concernInput[Entity::ID]);

        $concern->mergeGatewayData($concernInput[Entity::GATEWAY_DATA] ?? []);
        $concern->setInternalStatus($concernInput[Entity::INTERNAL_STATUS]);

        (new Concern\Core)->update($concern, $concernInput);

        return $concern->toArrayPublic();
    }

    public function concernStatus(array $input): array
    {
        $this->initialize(Action::CONCERN_STATUS, $input, true);

        $transaction = $this->core->fetch($this->input->pull(Entity::ID));

        if ($transaction->concern instanceof Concern\Entity)
        {
            if ($transaction->concern->isClosed() === true)
            {
                return $transaction->concern->toArrayPublic();
            }
        }
        else
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND);
        }

        $this->gatewayInput->put(Entity::TRANSACTION, $transaction);
        $this->gatewayInput->put(Entity::CONCERN, $transaction->concern);
        $this->gatewayInput->put(Entity::UPI, $transaction->upi);

        return $this->callGateway();
    }

    public function fetchAllConcerns(array $input): array
    {
        return (new Concern\Core)->fetchAll($input)->toArrayPublic();
    }

    public function concernStatusSuccess(array $input): array
    {
        return $this->raiseConcernSuccess($input);
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

    protected function updateTransactionStatus(Entity $transaction, ArrayBag $input)
    {
        switch ($input->get(Entity::INTERNAL_STATUS))
        {
            case Status::COMPLETED:
                $actions = $this->setTransactionCompleted($transaction, $input);
                break;

            case Status::FAILED:
            case Status::EXPIRED:
            case Status::REJECTED:
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

        $this->core->update($transaction, $input->toArray());

        $this->dispatchEventIfRequired($actions, $transaction);
    }

    protected function setTransactionCompleted(Entity $transaction, ArrayBag $input): Actions
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

        // TODO: Add support for partial payments
        if ($input->get(Entity::AMOUNT) !== $transaction->getAmount())
        {
            $input->put(Entity::INTERNAL_STATUS, Status::FAILED);
            $input->put(Entity::INTERNAL_ERROR_CODE, ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED);

            return $this->setTransactionFailed($transaction, $input);
        }

        $transaction->markCompleted();

        return $actions;
    }

    protected function setTransactionFailed(Entity $transaction, ArrayBag $input): Actions
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

        $error = new Error($input[Entity::INTERNAL_ERROR_CODE]);

        $transaction->setErrorCode($error->getPublicErrorCode());
        $transaction->setErrorDescription($error->getDescription());

        return $actions;
    }

    protected function setTransactionProcessing(Entity $transaction, ArrayBag $input): Actions
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

            $error = new Error(ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING);

            $transaction->setErrorCode($error->getPublicErrorCode());
            $transaction->setErrorDescription($error->getDescription());
        }

        return $actions;
    }

    public function setTransactionCreated(Entity $transaction, ArrayBag $input): Actions
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

    protected function createTransaction(string $action, ArrayBag $input, ArrayBag $upiInput): Entity
    {
        $transactionInput = clone $input;

        $properties = new Properties($this->context(), $action, $transactionInput);

        $transaction = $this->core->build($transactionInput->toArray());

        $properties->attachToTransaction($transaction);

        $upi = $this->core->buildUpi($transaction, $action, $upiInput->toArray());

        $lock = $upi->getAction() . $upi->getNetworkTransactionId();

        return $this->app['api.mutex']->acquireAndRelease($lock,
            function() use ($transaction, $input, $upi)
            {
                return $this->repo()->transaction(function() use ($transaction, $input, $upi)
                {
                    $this->checkForDuplicate($upi);

                    $this->updateTransactionStatus($transaction, $input);

                    $upi->associateTransaction($transaction);

                    $this->core->updateUpi($upi, []);

                    return $transaction;
                });
            });
    }

    protected function checkForDuplicate(UpiTransaction\Entity $upi)
    {
        $existing = $this->core->findAllUpi($upi->toArray());

        if ($existing->count() > 0)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_DUPLICATE_TRANSACTION, [
                Entity::UPI => $upi,
            ]);
        }
    }

    protected function updateTransaction(Entity $transaction, ArrayBag $input, ArrayBag $upiInput)
    {
        $lock = $transaction->upi->getAction() . $transaction->upi->getNetworkTransactionId();

        return $this->app['api.mutex']->acquireAndRelease($lock,
            function() use ($transaction, $input, $upiInput)
            {
                $transaction->reload();

                return $this->repo()->transaction(function() use ($transaction, $input, $upiInput)
                {
                    $this->updateTransactionStatus($transaction, $input);

                    $this->core->updateUpi($transaction->upi, $upiInput->toArray());

                    return $transaction;
                });
            });
    }
}
