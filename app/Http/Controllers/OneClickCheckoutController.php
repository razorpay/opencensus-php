<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\OneClickCheckout\Shopify;

class OneClickCheckoutController
{

    public function shopifyCreateCheckout()
    {
        $input = Request::all();

        $response = (new Shopify\Service)->shopifyCreateCheckout($input);

        return ApiResponse::json($response, 200);
    }

    public function shopifyGetCheckoutOptions()
    {
        $input = Request::all();

        $response = (new Shopify\Service)->shopifyGetCheckoutOptions($input);

        return ApiResponse::json($response, 200);
    }

    public function shopifyCompleteCheckout()
    {
        $input = Request::all();

        $response = (new Shopify\Service())->completeCheckoutWithLock($input);

        return ApiResponse::json($response, 200);
    }

    public function shopifyOAuthRedirect()
    {
        $input = Request::all();

        $response = (new Shopify\Service)->shopifyOAuthRedirect($input);

        return ApiResponse::json($response, 200);
    }

    public function shopifyUpdateCheckout()
    {
        $input = Request::all();

        $response = (new Shopify\Service)->updateCheckout($input);

        return ApiResponse::json($response, 200);
    }
}
