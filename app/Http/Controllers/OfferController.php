<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Offer;
use Request;

class OfferController extends Controller
{
    public function createOffer()
    {
        $input = Request::all();

        $data = (new Offer\Service)->createOffer($input);

        return ApiResponse::json($data);
    }

    public function updateOffer(string $id)
    {
        $input = Request::all();

        $data = (new Offer\Service)->updateOffer($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchOffers()
    {
        $input = Request::all();

        $data = (new Offer\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchOfferById(string $id)
    {
        $data = (new Offer\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function addIins(string $id)
    {
        $input = Request::all();

        $data = (new Offer\Service)->addIins($id, $input);

        return ApiResponse::json($data);
    }

    public function deactivateOffer(string $id)
    {
        $data = (new Offer\Service)->deactivate($id);

        return ApiResponse::json($data);
    }
}
