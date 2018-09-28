<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

/**
 * Interface ActivationFlowInterface
 *
 * defines methods , supported by activation flow implementation class
 *
 * @package RZP\Models\Merchant\Detail\ActivationFlow
 */
interface ActivationFlowInterface
{
    public function process();
}
