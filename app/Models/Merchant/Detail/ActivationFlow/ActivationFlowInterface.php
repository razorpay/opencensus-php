<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Models\Merchant\Entity;

/**
 * Interface ActivationFlowInterface
 *
 * defines methods , supported by activation flow implementation class
 *
 * @package RZP\Models\Merchant\Detail\ActivationFlow
 */
interface ActivationFlowInterface
{
    public function process(Entity $merchant);

    /**
     * Contains validation specific to the activation flow (L2 activation form)
     *
     * @param Entity $merchant
     */
    public function validateFullActivationForm(Entity $merchant);
}
