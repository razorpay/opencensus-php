<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\FundAccount;

/**
 * Class FundAccountController
 *
 * @package RZP\Http\Controllers
 */
class FundAccountController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = FundAccount\Service::class;

    public function get(string $id)
    {
        $entity = $this->service()->fetch($id, $this->input);

        return ApiResponse::json($entity);
    }

    public function createPublic()
    {
        $input = Request::all();

        $entity = $this->service()->createPublic($input);

        return ApiResponse::json($entity);
    }
}
