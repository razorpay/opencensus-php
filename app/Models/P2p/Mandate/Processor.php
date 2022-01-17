<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Mandate\Status;

/**
 *   * @property Core $core
 */
class Processor extends Base\Processor
{
    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input);

        $mandate = $this->core->fetch($input['id']);

        //TODO: implement validator logic

        //TODO: initiate gateway callback

        //TODO: gateway specific response

        return $mandate->toArrayPublic();
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
}
