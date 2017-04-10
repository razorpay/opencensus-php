<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\User;

class UserController extends Controller
{
    public function postUser()
    {
        $input = Request::all();

        $data = (new User\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function putUser($id)
    {
    	$input = Request::all();

        $data = (new User\Service)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function attachUserToMerchant($id)
    {
        $input = Request::all();

        $data = (new User\Service)->attach($id, $input);

        return ApiResponse::json($data);
    }
}
