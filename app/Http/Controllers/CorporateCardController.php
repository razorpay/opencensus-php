<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\CorporateCard;
use RZP\Exception\BadRequestException;

/**
 * Class CorporateCardController
 *
 * @package RZP\Http\Controllers
 */
class CorporateCardController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = CorporateCard\Service::class;

    public function create($token)
    {
        $input = Request::all();

        $data = $this->service()->create($input, $token);

        $response = $data[Constants\Entity::CORPORATE_CARD];

        $responseCode = $data[CorporateCard\Entity::RESPONSE_CODE];

        return ApiResponse::json($response, $responseCode);
    }

    public function get($id)
    {
        $data = $this->service()->fetchById($id);

        return ApiResponse::json($data);
    }

    public function list()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function renderForm(\Illuminate\Http\Request $request)
    {
        $isValid = $this->service()->isValidToken($request->all());

        if ($isValid === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CORPORATE_CARD_INVALID_TOKEN);
        }

        return View::make('public.corporate_card_form');
    }
}
