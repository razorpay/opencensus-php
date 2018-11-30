<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Beneficiary;

/**
 * Class BeneficiaryAccountController
 *
 * @package RZP\Http\Controllers
 */
class BeneficiaryAccountController extends Controller
{
    protected $service = Beneficiary\Account\Service::class;

    public function get(string $beneId, string $accId)
    {
        $input = Request::all();

        $entity = $this->service()->fetch($beneId, $accId, $input);

        return ApiResponse::json($entity);
    }

    public function list(string $beneId)
    {
        $input = Request::all();

        $entities = $this->service()->fetchMultiple($beneId, $input);

        return ApiResponse::json($entities);
    }

    public function create(string $beneId)
    {
        $input = Request::all();

        $entity = $this->service()->create($beneId, $input);

        return ApiResponse::json($entity);
    }

    public function update(string $beneId, string $accId)
    {
        $input = Request::all();

        $entity = $this->service()->update($beneId, $accId, $input);

        return ApiResponse::json($entity);
    }

    public function delete(string $beneId, string $accId)
    {
        $response = $this->service()->delete($beneId, $accId);

        return ApiResponse::json($response);
    }
}
