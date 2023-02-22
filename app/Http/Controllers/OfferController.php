<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class OfferController extends Controller
{
    public function createOffer()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function createOfferBulk()
    {
        $input = Request::all();

        $data = $this->service()->createBulk($input);

        return ApiResponse::json($data);
    }

    public function updateOffer(string $id)
    {
        $input = Request::all();

        $data = $this->service()->update($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchOffers()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchOffersSubscription()
    {
        $input = Request::all();

        $data = $this->service()->fetchOffersSubscription($input);

        return ApiResponse::json($data);
    }

    public function fetchOffersDiscountForSubscription()
    {
        $input = Request::all();

        $data = $this->service()->fetchOffersDiscountForSubscription($input);

        return ApiResponse::json($data);
    }

    public function fetchOffersPreferenceForSubscription()
    {
        $input = Request::all();

        $data = $this->service()->fetchOffersPreferenceForSubscription($input);

        return ApiResponse::json($data);
    }

    public function fetchOfferById(string $id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function bulkDeactivateOffers()
    {
        $data = $this->service()->bulkDeactivateOffers();

        return ApiResponse::json($data);
    }

    public function deactivateOffers()
    {
        $data = $this->service()->deactivate();

        return ApiResponse::json($data);
    }

    public function validateCheckoutOffers()
    {
        $input = Request::all();

        $data = $this->service()->validateCheckoutOffers($input);

        return ApiResponse::json($data);
    }
}
