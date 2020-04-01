<?php

namespace RZP\Models\Merchant\Detail\InternationalActivationFlow;

/**
 * Interface International ActivationFlowInterface
 *
 * defines methods , supported by International ActivationFlowInterface implementation class
 *
 * @package RZP\Models\Merchant\Detail\InternationalActivationFlow
 */
interface ActivationFlowInterface
{
    public function shouldActivateInternational() : bool;

    public function shouldActivateTypeformInternational() : bool;
}
