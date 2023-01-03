<?php

namespace RZP\Models\P2p\Turbo;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Models\P2p\Turbo\Entity;
use RZP\Models\P2p\Vpa\Handle\Entity as HandleEntity;

/**
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function initiateTurboCallback(array $input): array
    {
        $this->initialize(Action::INITIATE_TURBO_CALLBACK, $input , true);

        $handle = (new Handle\Core)->findByAcquirer($input[Entity::GATEWAY], true);

        $this->context()->setHandleAndMode($handle[HandleEntity::CODE]);

        $this->gatewayInput = $this->input;

        return $this->callGateway();
    }

    protected function initiateTurboCallbackSuccess(array $input): array
    {
        $this->initialize(Action::INITIATE_TURBO_CALLBACK_SUCCESS, $input);

        return $input;
    }

    public function turboCallback(array $input): array
    {
        $this->initialize(Action::TURBO_CALLBACK, $input);

        $this->gatewayInput = $this->input;

        return $this->callGateway();
    }

    public function turboCallbackSuccess(array $input): array
    {
        $this->initialize(Action::TURBO_CALLBACK_SUCCESS, $input);

        return $this->input->toArray();
    }
}
