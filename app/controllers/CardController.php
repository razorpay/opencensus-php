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
        $data = (new Card\IIN\Service)->fetchIin($id);

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

        if (isset($input['file']))
        {
            $data = (new Card\IIN\Service)->importIin($input); 
        }
        else
        {
            $data = (new Card\IIN\Service)->addIin($input);
        }

        return ApiResponse::json($data);
    }

    public function editIin($id)
    {
        $input = Input::all();

        $data = (new Card\IIN\Service)->editIin($id, $input);

        return ApiResponse::json($data);
    }

    public function postIinGenerate()
    {
        $input = Input::all();
        $fileName = (new Card\IIN\Service)->generateIinFile($input);

        return ApiResponse::json($fileName);
    }

}