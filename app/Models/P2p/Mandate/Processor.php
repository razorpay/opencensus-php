<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;
use RZP\Error\P2p\Error;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Mandate\Status;
use RZP\Models\P2p\Mandate\Actions;
use RZP\Exception\BadRequestException;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 *   * @property Core $core
 */
class Processor extends Base\Processor
{
    /**
     * @param array $input
     *
     * @return array
     */
    public function incomingCollect(array $input): array
    {
        $this->initialize(Action::INCOMING_COLLECT, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);
        $upiInput     = $this->input->bag(Entity::UPI);

        new Properties($this->context(), $this->action, $mandateInput);

        $mandate = $this->core->create($mandateInput->toArray(), $upiInput->toArray());

        return $mandate->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate authorize mandate flow
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input);

        $mandate = $this->core->fetch($input[Entity::ID]);

        //TODO: implement validator logic

        $this->initiateCallGateway($mandate);

        return $this->callGateway();

    }

    /**
     * @param array $input
     * This is the function to get mandate data for authorization and call gateway
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function authorizeMandate(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_MANDATE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for various actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function authorizeMandateSuccess(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_MANDATE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $mandate = $this->core->fetch($this->input->get(Entity::MANDATE)[Entity::ID]);

        $this->updateMandateStatus($mandate, $mandateInput);

        return $mandate->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate reject mandate , takes mandate id as input and initiate reject for it
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiateReject(array $input): array
    {
        $this->initialize(Action::INITIATE_REJECT, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the method to initiate reject mandate , takes mandate id as input and initiate pause for it
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiatePause(array $input): array
    {
        // both the pause start and pause end should be set
        if (isset($input[Entity::PAUSE_START]) === false  or
            isset($input[Entity::PAUSE_END]) === false)
        {
            throw new BadRequestException("Pause start and pause end are required");
        }

        $this->initialize(Action::INITIATE_PAUSE, $input);

        $mandate                      = $this->core->fetch($this->input->get(Entity::ID));
        $mandate[Entity::PAUSE_START] = $input[Entity::PAUSE_START];
        $mandate[Entity::PAUSE_END]   = $input[Entity::PAUSE_END];

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to get mandate data for pausing the data and call gateway
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function pauseMandate(array $input): array
    {
        $this->initialize(Action::PAUSE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for various actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function pauseSuccess(array $input): array
    {
        $this->initialize(Action::PAUSE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $mandate = $this->core->fetch($this->input->bag(Entity::MANDATE)->get(Entity::ID));

        $this->updateMandateStatus($mandate, $mandateInput);

        return $mandate->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate un pause action
     *
     * @return array
     * @throws \Throwable
     */
    public function initiateUnpause(array $input): array
    {
        $this->initialize(Action::INITIATE_UNPAUSE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        if ($mandate[Entity::INTERNAL_STATUS] !== Status::PAUSED)
        {
            throw new BadRequestException('Mandate is not paused, cannot unpause. Current mandate status is ' .$mandate[Entity::INTERNAL_STATUS]);
        }

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }


    /**
     * @param array $input
     * This is the function to get mandate data for pausing the data and call gateway
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function unpauseMandate(array $input): array
    {
        $this->initialize(Action::UNPAUSE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for unpause actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function unpauseSuccess(array $input): array
    {
        $this->initialize(Action::UNPAUSE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $mandate = $this->core->fetch($this->input->bag(Entity::MANDATE)->get(Entity::ID));

        $this->updateMandateStatus($mandate, $mandateInput);

        return $mandate->toArrayPublic();
    }

    /**
     * @param array $input
     * This is the method to initiate revoke mandate flow
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function initiateRevoke(array $input): array
    {
        $this->initialize(Action::INITIATE_REVOKE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to get mandate data for pausing the data and call gateway
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function revokeMandate(array $input): array
    {
        $this->initialize(Action::REVOKE, $input);

        $mandate = $this->core->fetch($this->input->get(Entity::ID));

        $this->initiateCallGateway($mandate);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is the function to process gateway response that come back for revoke actions
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    public function revokeSuccess(array $input): array
    {
        $this->initialize(Action::REVOKE_SUCCESS, $input);

        $mandateInput = $this->input->bag(Entity::MANDATE);

        $mandate = $this->core->fetch($this->input->bag(Entity::MANDATE)->get(Entity::ID));

        $this->updateMandateStatus($mandate, $mandateInput);

        return $mandate->toArrayPublic();
    }

    /**
     * This is the method to intitiate gateway callback for the given mandate
     * @param Entity $mandate
     */
    protected function initiateCallGateway(Entity $mandate)
    {
        $this->gatewayInput->putMany([
             Entity::MANDATE      => $mandate ,
             Entity::PAYER        => $mandate->payer ,
             Entity::PAYEE        => $mandate->payee ,
             Entity::BANK_ACCOUNT => $mandate->bankAccount ,
             Entity::UPI          => $mandate->upi ,
         ]);

        $this->callbackInput->push($mandate->getPublicId());
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the function to update mandate statuses
     * @return mixed
     * @throws \RZP\Exception\LogicException
     * @throws \RZP\Exception\RuntimeException
     */
    protected function updateMandateStatus(Entity $mandate, ArrayBag $input):Entity
    {
        switch ($input->get(Entity::INTERNAL_STATUS))
        {
            case Status::COMPLETED:
                $this->setMandateCompleted($mandate, $input);
                break;

            case Status::APPROVED:
                $this->setMandateApproved($mandate, $input);
                break;

            case Status::REJECTED:
                $this->setMandateRejected($mandate, $input);
                break;

            case Status::PAUSED:
                $this->setMandatePaused($mandate, $input);
                break;

            case Status::REVOKED:
                $this->setMandateRevoked($mandate, $input);
                break;

            default:
                throw $this->logicException('Invalid internal status for mandate', [
                    Entity::MANDATE         => $input,
                    Entity::ID              => $mandate->getId(),
                ]);
        }

        $this->core->update($mandate, $input->toArray());

        return $mandate;
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be authorized
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateApproved(Entity $mandate, ArrayBag $input)
    {
        if ($mandate->isFailed() === true || $mandate->isRevoked() === true )
        {
            throw $this->logicException('mandate can not be marked completed', [
                Entity::MANDATE         => $input,
                Entity::ID              => $mandate->getId(),
            ]);
        }

        $mandate->markApproved();
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be completed
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateCompleted(Entity $mandate, ArrayBag $input)
    {
        if ($mandate->isFailed() === true)
        {
            throw $this->logicException('mandate can not be marked completed', [
                Entity::MANDATE         => $input,
                Entity::ID              => $mandate->getId(),
            ]);
        }

        $mandate->markCompleted();
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be completed
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateRejected(Entity $mandate, ArrayBag $input)
    {
        if (($mandate->isFailed() === true ) or
            ($mandate->isRevoked() === true) or
            ($mandate->isApproved() === true))
        {
            throw $this->logicException('mandate can not be marked rejected', [
                Entity::MANDATE         => $input,
                Entity::ID              => $mandate->getId(),
            ]);
        }

        $mandate->markRejected();
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be authorized
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandatePaused(Entity $mandate, ArrayBag $input)
    {
        // if the mandate creation has already failed throw the error
        if ($mandate->isFailed() === true)
        {
            throw $this->logicException('mandate cannot be marked paused as the mandate has already failed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is in revoked state throw the error
        if ($mandate->isRevoked() === true)
        {
            throw $this->logicException('mandate cannot be marked paused as the mandate has already been revoked', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is not in an completed state throw the error
        if ($mandate->isCompleted() === true)
        {
            throw $this->logicException('mandate is not in progress and already completed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        $mandate->markPaused();
    }

    /**
     * @param Entity   $mandate
     * @param ArrayBag $input
     * This is the method to set mandate status to be revoked
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function setMandateRevoked(Entity $mandate, ArrayBag $input)
    {
        // if the mandate creation has already failed throw the error
        if ($mandate->isFailed() === true)
        {
            throw $this->logicException('mandate can not be marked completed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        // if the mandate is not in an completed state throw the error
        if ($mandate->isCompleted() === true)
        {
            throw $this->logicException('mandate cannot be marked as revoked as its already completed', [
                Entity::MANDATE => $input,
                Entity::ID      => $mandate->getId(),
            ]);
        }

        $mandate->markRevoked();
    }
}
