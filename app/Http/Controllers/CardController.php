<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;

class CardController extends Controller
{
    public function getCard($id)
    {
        $data = $this->service()->fetchById($id);

        return ApiResponse::json($data);
    }

    public function getCards()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function updateSavedCards()
    {
        $data = $this->service()->updateSavedCards();

        return ApiResponse::json($data);
    }
}
