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

    public function deleteOffer(string $id)
    {
        $data = (new Offer\Service)->deleteOffer($id);

        return ApiResponse::json($data);
    }

    public function updateMerchants(string $id)
    {
        $input = Request::all();

        $data = (new Offer\Service)->updateMerchants($id, $input);

        return ApiResponse::json($data);
    }
}
