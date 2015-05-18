<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Payment;
use Models\Card;

class CardController extends BaseController
{
    public function getCard($id)
    {
        $data = (new Card\Service)->fetchById($id);

        return ApiResponse::json($data);
    }

    public function getCards()
    {
        $input = Input::all();

        $data = (new Card\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getIin($id)
    {
        $data = (new Card\IIN\Service)->fetchById($id);

        return ApiResponse::json($data);
    }

    public function getIins()
    {
        $input = Input::all();

        $data = (new Card\IIN\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postIin()
    {
        $input = Input::all();

        $data = (new Card\IIN\Service)->addIin($input);

        return ApiResponse::json($data);
    }
}