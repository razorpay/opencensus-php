<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Models\Merchant\Detail\Entity;

/**
 * Interface ActivationFlowInterface
 *
 * defines methods , supported by activation flow implementation class
 *
 * @package RZP\Models\Merchant\Detail\ActivationFlow
 */
interface ActivationFlowInterface
{
    public function process(Entity $merchantDetails);
}
