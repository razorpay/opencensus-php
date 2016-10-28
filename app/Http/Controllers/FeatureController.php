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

	public function deleteFeature($id)
	{
		$data = (new Feature\Service)->deleteFeature($id);

		return ApiResponse::json($data);
	}

	public function getFeatures($toggleableType, $toggleableId)
	{
		$data = (new Feature\Service)->getFeatures($toggleableType, $toggleableId);

		return ApiResponse::json($data);
	}
}