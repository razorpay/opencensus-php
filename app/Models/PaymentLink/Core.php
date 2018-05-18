<?php

namespace RZP\Models\PaymentLink;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Base\PublicCollection;

class Core extends Base\Core
{
    /**
     * Creates a payment link
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {

    }

    /**
     * Fetches multiple payment links
     *
     * @return PublicCollection
     */
    public function fetchMultiple(array $input, Merchant\Entity $merchant): PublicCollection
    {

    }

    /**
     * Updates a payment link
     *
     * @return Entity
     */
    public function update(Entity $paymentLink, array $input): Entity
    {

    }

    /**
     * Fetches all payments of a payment link
     *
     * @return PublicCollection
     */
    public function fetchPayments(Entity $paymentLink): PublicCollection
    {

    }
}
