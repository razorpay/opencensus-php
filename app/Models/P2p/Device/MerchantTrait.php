<?php

namespace RZP\Models\P2p\Device;

use RZP\Models\P2p\Vpa;
use RZP\Trace\TraceCode;

trait MerchantTrait
{
    protected $deviceToUpdate;

    public function updateWithAction(array $input): array
    {
        $this->initialize(Action::UPDATE_WITH_ACTION, $input, true);

        $device = (new Core)->fetch($this->input->get(Entity::ID));

        $this->context()->setDevice($device);

        $data = null;

        $this->trace()->info(TraceCode::P2P_MANAGE_REQUEST, $input);

        switch ($this->input->get(Entity::ACTION))
        {
            case Action::RESTORE_DEVICE;

                $data = $this->restoreDevice($this->input->get(Entity::DATA));
                break;
        }

        $this->trace()->info(TraceCode::P2P_MANAGE_RESPONSE, [
            Entity::ACTION  => $this->action,
            Entity::DATA    => $data,
        ]);

        return [
            Entity::ID          => $this->context()->getDevice()->getPublicId(),
            Entity::ACTION      => $this->action,
            Entity::SUCCESS     => true,
            Entity::DATA        => $data,
        ];
    }

    protected function restoreDevice(array $input): array
    {
        $this->initialize(Action::RESTORE_DEVICE, $input, true);

        $vpas = (new Vpa\Core)->restoreVpas($input);

        return [
            Entity::VPAS        => $vpas->toArrayPublic(),
        ];
    }
}
