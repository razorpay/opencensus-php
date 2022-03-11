<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;

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

    public function authorizeMandate(array $input): array
    {
        $this->initialize(Action::AUTHORIZE_MANDATE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
    }

    public function initiateReject(array $input): array
    {
        $this->initialize(Action::INITIATE_REJECT, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
    }

    public function rejectMandate(array $input): array
    {
        $this->initialize(Action::REJECT, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
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

    protected function initiateCallGateway(Entity $mandate)
    {
        $this->gatewayInput->putMany([
             Entity::MANDATE      => $mandate ,
             Entity::PAYER        => $mandate->payer ,
             Entity::PAYEE        => $mandate->payee ,
             Entity::BANK_ACCOUNT => $mandate->bank_account ,
             Entity::UPI          => $mandate->upi ,
         ]);

        $this->callbackInput->push($mandate->getPublicId());
    }
}
