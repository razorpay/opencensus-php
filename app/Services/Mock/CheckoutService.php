<?php

namespace RZP\Services\Mock;

use Illuminate\Http\Response;
use RZP\Services\CheckoutService as BaseCheckoutService;

class CheckoutService extends BaseCheckoutService
{
    public function getCheckoutPreferencesFromCheckoutService(array $input): Response
    {
        return new Response();
    }
}
