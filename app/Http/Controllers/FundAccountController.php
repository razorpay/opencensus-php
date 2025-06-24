<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Symfony\Component\HttpFoundation\Response;
use RZP\Services\PayoutService\PayoutShadowService;

use RZP\Constants;
use RZP\Models\FundAccount;
use Throwable;

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

        try
        {
            // call shadow router to mirror the request
            PayoutShadowService::mirrorRequest(
                'POST',
                Request::path(),
                $input,
                Request::header()
            );
        }
        catch (Throwable $e)
        {
            // ignore error
        }


        $data = $this->service()->create($input);

        $fundAccount = $data[Constants\Entity::FUND_ACCOUNT];

        $fundAccountData = $fundAccount->toArrayPublic();

        //
        // fund account can be created for different sources like
        // contact and customer. The API response for fund account
        // creation of contact will be different. The response code
        // will be passed by the service and controller will just
        // forward that. Since for other sources the behaviour
        // remain the same, so we are keeping an isset check
        //
        $responseCode = isset($data[FundAccount\Entity::RESPONSE_CODE]) ?
                        $data[FundAccount\Entity::RESPONSE_CODE] :
                        Response::HTTP_OK;

        return ApiResponse::json($fundAccountData, $responseCode);
    }

    public function get(string $id)
    {
        $entity = $this->service()->fetch($id, $this->input);

        return ApiResponse::json($entity);
    }

    /**
     *  Route to create bulk fund accounts
     *  Currently it is used by batch Service
     */
    public function createFundAccountBulk()
    {
        $input = Request::all();

        $response = $this->service()->createBulkFundAccount($input);

        return ApiResponse::json($response);
    }

    public function createForVendorPortalV2()
    {
        $input = Request::all();

        $response = $this->service()->createForVendorPortalV2($input);

        return ApiResponse::json($response);
    }

    public function fetchBySourceTypeAndId(string $sourceType, string $sourceId)
    {
        $response = $this->service()->fetchBySourceTypeAndId($sourceType, $sourceId);

        return ApiResponse::json($response);
    }
}
