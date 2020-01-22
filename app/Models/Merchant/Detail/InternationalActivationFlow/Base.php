<?php

namespace RZP\Models\Merchant\Detail\InternationalActivationFlow;

use RZP\Models\Merchant\Detail;

abstract class Base implements ActivationFlowInterface
{
    protected $merchant;

    protected $merchantDetail;

    public function __construct($merchant)
    {
        $this->merchant = $merchant;

        $this->merchantDetail = (new Detail\Core)->getMerchantDetails($merchant);
    }
}
