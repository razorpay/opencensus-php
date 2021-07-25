<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Events\P2p;
use RZP\Models\P2p\Vpa;
use RZP\Error\P2p\Error;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Base\Upi;
use RZP\Models\P2p\Beneficiary;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;

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

        $transaction = $this->createTransaction($this->action, $this->input, $this->input->bag(Entity::UPI));

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

        (new Rules($transaction))->validate();

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

        $upi = $this->core->findAllUpi($upiInput->toArray());

        if ($upi->count() === 1)
        {
            $transaction = $this->core->fetch($upi->first()->getTransactionId());

            $this->updateTransaction($transaction, $transactionInput, $upiInput);
        }
        else
        {
            $transaction = $this->createTransaction($this->action, $transactionInput, $upiInput);
        }

        return $transaction->toArrayPublic();
    }

    public function raiseConcern(array $input): array
    {
        $this->initialize(Action::RAISE_CONCERN, $input, true);

        $transaction = $this->core->fetch($this->input->pull(Entity::ID));

        if ($transaction->isConcernEligible() === false)
        {
            throw $this->badRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }
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

        (new Concern\Core)->update($concern, array_only($concernInput, [
            Concern\Entity::GATEWAY_REFERENCE_ID,
            Concern\Entity::RESPONSE_CODE,
            Concern\Entity::RESPONSE_DESCRIPTION,
        ]));

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
            case Status::REQUESTED:
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

        if ($actions->shouldUpdate() === true)
        {
            $this->core->update($transaction, $input->toArray());
        }

        return $actions;
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
            return $actions->setShouldUpdate(false);
        }

        // TODO: Add support for partial payments
        if ($input->get(Entity::AMOUNT) !== $transaction->getAmount())
        {
            $input->put(Entity::INTERNAL_STATUS, Status::FAILED);
            $input->put(Entity::INTERNAL_ERROR_CODE, ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED);

            return $this->setTransactionFailed($transaction, $input);
        }

        $transaction->markCompleted();

        $actions->setEvent(new P2p\TransactionCompleted($this->context(), $transaction));

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
            return $actions->setShouldUpdate(false);
        }

        $transaction->setInternalStatus($input[Entity::INTERNAL_STATUS]);

        $error = new Error($input[Entity::INTERNAL_ERROR_CODE]);

        $transaction->setErrorCode($error->getPublicErrorCode());
        $transaction->setErrorDescription($error->getDescription());

        $actions->setEvent(new P2p\TransactionFailed($this->context(), $transaction));

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
        else if ($input[Entity::INTERNAL_STATUS] === Status::REQUESTED)
        {
            $transaction->setInternalStatus(Status::REQUESTED);
        }

        $actions->setEvent(new P2p\TransactionCreated($this->context(), $transaction));

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

        $actions->setEvent(new P2p\TransactionCreated($this->context(), $transaction));

        return $actions;
    }

    protected function createTransaction(string $action, ArrayBag $input, ArrayBag $upiInput): Entity
    {
        $transactionInput = clone $input;

        $properties = new Properties($this->context(), $action, $transactionInput);

        $transaction = $this->core->build($transactionInput->toArray());

        $properties->attachToTransaction($transaction);

        (new Rules($transaction))->validate();

        $upi = $this->core->buildUpi($transaction, $action, $upiInput->toArray());

        $lock = $upi->getAction() . $upi->getNetworkTransactionId();

        return $this->app['api.mutex']->acquireAndRelease($lock,
            function() use ($transaction, $input, $upi)
            {
                return $this->repo()->transaction(function() use ($transaction, $input, $upi)
                {
                    $this->checkForDuplicate($upi);

                    $actions = $this->updateTransactionStatus($transaction, $input);

                    $upi->associateTransaction($transaction);

                    $this->core->updateUpi($upi, []);

                    $this->performTransactionActions($actions, $transaction);

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
                    $actions = $this->updateTransactionStatus($transaction, $input);

                    $this->core->updateUpi($transaction->upi, $upiInput->toArray());

                    $this->performTransactionActions($actions, $transaction);

                    return $transaction;
                });
            },
            60,
            ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
            3);
    }

    protected function performTransactionActions(Actions $actions, Entity $transaction)
    {
        if ($actions->hasEvent() === true)
        {
            $this->app['events']->dispatch($actions->getEvent());
        }
    }
}
