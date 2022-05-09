<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;
use RZP\Error\P2p\Error;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Mandate\Status;
use RZP\Models\P2p\Mandate\Actions;
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

    public function initiatePause(array $input): array
    {
        $this->initialize(Action::INITIATE_PAUSE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
    }

    public function pauseMandate(array $input): array
    {
        $this->initialize(Action::PAUSE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
    }

    public function initiateUnpause(array $input): array
    {
        $this->initialize(Action::INITIATE_UNPAUSE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
    }

    public function unpauseMandate(array $input): array
    {
        $this->initialize(Action::UNPAUSE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
    }

    public function initiateRevoke(array $input): array
    {
        $this->initialize(Action::INITIATE_REVOKE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
    }

    public function revokeMandate(array $input): array
    {
        $this->initialize(Action::REVOKE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

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
}
