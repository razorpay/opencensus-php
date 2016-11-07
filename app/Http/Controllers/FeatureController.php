<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Feature;
use Request;

class FeatureController extends Controller
{
    public function addFeatures()
    {
        $input = Request::all();

        $data = (new Feature\Service)->addFeatures($input);

        return ApiResponse::json($data);
    }

    public function multiAssignFeature()
    {
        $input = Request::all();

        $data = (new Feature\Service)->multiAssignFeature($input);

        return ApiResponse::json($data);
    }

    public function multiRemoveFeature()
    {
        $input = Request::all();

        $data = (new Feature\Service)->multiRemoveFeature($input);

        return ApiResponse::json($data);
    }

    public function deleteFeature(string $entityId, string $featureName)
    {
        $data = (new Feature\Service)->deleteFeature($entityId, $featureName);

        return ApiResponse::json($data);
    }

    public function getFeatures(string $entityId)
    {
        $data = (new Feature\Service)->getFeatures($entityId);

        return ApiResponse::json($data);
    }

}
