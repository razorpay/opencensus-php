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

	public function deleteFeature(int $id)
	{
		$data = (new Feature\Service)->deleteFeature($id);

		return ApiResponse::json($data);
	}

	public function getFeatures(string $entityType, string $entityId)
	{
		$data = (new Feature\Service)->getFeatures($entityType, $entityId);

		return ApiResponse::json($data);
	}

	public function migrateMerchantFeatures()
	{
		$data = (new Feature\Service)->migrateMerchantFeatures();

		return ApiResponse::json($data);
	}
}