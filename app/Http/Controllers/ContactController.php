<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants;
use RZP\Models\Contact;

/**
 * Class ContactController
 *
 * @package RZP\Http\Controllers
 */
class ContactController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Contact\Service::class;

    public function get(string $id)
    {
        $input = Request::all();

        $contact = $this->service()->fetch($id, $input);

        return ApiResponse::json($contact);
    }

    public function getTypes()
    {
        $data = $this->service()->getTypes();

        return ApiResponse::json($data);
    }

    public function postType()
    {
        $input = Request::all();

        $data = $this->service()->postType($input);

        return ApiResponse::json($data);
    }

    public function create()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        $response = $data[Constants\Entity::CONTACT];

        $responseCode = $data[Contact\Entity::RESPONSE_CODE];

        return ApiResponse::json($response, $responseCode);
    }
}
