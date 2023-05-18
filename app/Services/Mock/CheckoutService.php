<?php

namespace RZP\Services\Mock;

use RZP\Services\CheckoutService as BaseCheckoutService;

class CheckoutService extends BaseCheckoutService
{
    public function getCheckoutPreferencesFromCheckoutService(array $input): array
    {
        return [];
    }
}
