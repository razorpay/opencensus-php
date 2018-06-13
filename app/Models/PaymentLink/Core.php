<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    /**
     * Creates a payment link
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {

    }

    /**
     * Updates a payment link
     *
     * @param Entity $paymentLink
     * @param array  $input
     *
     * @return Entity
     */
    public function update(Entity $paymentLink, array $input): Entity
    {

    }
}
