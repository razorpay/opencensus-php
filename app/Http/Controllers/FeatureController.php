<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class FeatureController extends Controller
{
    public function addFeatures()
    {
        $input = Request::all();

        $data = $this->service()->addFeatures($input);

        return ApiResponse::json($data);
    }

    public function multiAssignFeature()
    {
        $input = Request::all();

        $data = $this->service()->multiAssignFeature($input);

        return ApiResponse::json($data);
    }

    public function multiRemoveFeature()
    {
        $input = Request::all();

        $data = $this->service()->multiRemoveFeature($input);

        return ApiResponse::json($data);
    }

    public function deleteFeature(string $entityId, string $featureName)
    {
        $input = Request::all();

        $data = $this->service()->deleteFeature($entityId, $featureName, $input);

        return ApiResponse::json($data);
    }

    public function getFeatures(string $entityId)
    {
        $data = $this->service()->getFeatures($entityId);

        return ApiResponse::json($data);
    }

}
