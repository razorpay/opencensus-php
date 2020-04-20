<?php

namespace RZP\Gateway\P2p\Upi\Npci;

use RZP\Models\P2p\Device\Entity;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

/**
 * Trait ClTrait
 * @package RZP\Gateway\P2p\Upi\Npci
 */
trait ClTrait
{
    protected $npciCommonLibrary;
    /**
     * Generate a CL instance with device token gateway data
     * @return Cl
     */
    protected function cl(): Cl
    {
        if (($this->npciCommonLibrary instanceof Cl) === false)
        {
            $input  = $this->getContextDeviceToken()->get(Entity::GATEWAY_DATA);
            $handle = $this->getContextHandle();

            // Apart from Gateway Data these fields are added to input
            $input[ClInput::TXN_ID]         = $this->getHandlePrefix() . $this->getRequestId();
            $input[ClInput::DEVICE_ID]      = $this->getContextDevice()->get(Entity::UUID);
            $input[ClInput::APP_ID]         = $this->getContextDevice()->get(Entity::APP_NAME);
            $input[ClInput::MOBILE_NUMBER]  = substr($this->getContextDevice()->get(Entity::CONTACT), -10);

            // Here we have access to gateway data, we can switch version from here

            $this->npciCommonLibrary = $this->newCl($handle, $input);
        }

        return $this->npciCommonLibrary;
    }

    /**
     * Generate a CL instance with any input passed
     * Handle and input can also tell which version to pick
     *
     */
    protected function newCl(ArrayBag $handle, array $input, string $version = ClVersion::V_1_5): CL
    {
        if ($version !== ClVersion::V_1_5)
        {
            throw new InvalidArgumentException('Cl version not supported', [ClInput::CL_VERSION => $version]);
        }
        // Here based on version we will create cl
        return new Cl($handle, $input);
    }
}
