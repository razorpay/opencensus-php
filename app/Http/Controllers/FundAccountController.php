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

    /**
     *  Route to create bulk contacts.
     *  Currently it is used by batch Service
     */
    public function createFundAccountBulk()
    {
        $input = Request::all();

        $response = $this->service()->createBulkFundAccount($input);

        return ApiResponse::json($response);
    }
}
