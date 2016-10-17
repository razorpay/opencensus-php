<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Exception\RecoverableException;
use RZP\Models\Payment;
use RZP\Models\Card;
use Request;

class CardController extends Controller
{
    public function getCard($id)
    {
        $data = (new Card\Service)->fetchById($id);

        return ApiResponse::json($data);
    }

    public function getCards()
    {
        $input = Request::all();

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
        $input = Request::all();

        $data = (new Card\IIN\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postIin()
    {
        $input = Request::all();

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
    public function uploadIin()
    {
        $input = Request::all();

        if (isset($input['data']))
        {
            $app = \App::getFacadeRoot();
            $file = Request::file('data');
            $filePath = $file->getRealPath();

            $cardIin = "RZP\\Models\\Card\\IIN\\Service";

            $app->queue->push($cardIin.'@importCsvIin', $filePath);
            $data = "Iin Data added in queue for Processing";
        }
        else
        {
            $data = "Please give Iin Csv File";
        }
        return ApiResponse::json($data);
    }
    public function editIin($id)
    {
        $input = Request::all();

        $data = (new Card\IIN\Service)->editIin($id, $input);

        return ApiResponse::json($data);
    }

    public function postIinGenerate()
    {
        $input = Request::all();
        $fileName = (new Card\IIN\Service)->generateIinFile($input);

        return ApiResponse::json($fileName);
    }

}
