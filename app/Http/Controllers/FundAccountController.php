<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants;
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

    public function create()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        $entity = $data[Constants\Entity::FUND_ACCOUNT];

        $entity = $entity->ToArrayPublic();

        if (isset($data[FundAccount\Entity::RESPONSE_CODE]) === true)
        {
            $responseCode = $data[FundAccount\Entity::RESPONSE_CODE];

            return ApiResponse::json($entity,$responseCode);
        }

        return ApiResponse::json($entity);
    }

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
