<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Offer;

class OfferController extends Controller
{
    public function createOffer()
    {
        $input = Request::all();

        $data = (new Offer\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function updateOffer(string $id)
    {
        $input = Request::all();

        $data = (new Offer\Service)->update($id, $input);

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

    public function deactivateOffers()
    {
        $data = (new Offer\Service)->deactivate();

        return ApiResponse::json($data);
    }
}
