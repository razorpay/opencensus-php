<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
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

    public function getIin($id)
    {
        $data = $this->service(E::IIN)->fetchIin($id);

        return ApiResponse::json($data);
    }

    public function updateSavedCards()
    {
        $data = $this->service()->updateSavedCards();

        return ApiResponse::json($data);
    }

    public function getIins()
    {
        $input = Request::all();

        $data = $this->service(E::IIN)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postIin()
    {
        $input = Request::all();

        if (isset($input['file']))
        {
            $data = $this->service(E::IIN)->importIin($input);
        }
        else
        {
            $data = $this->service(E::IIN)->addIin($input);
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

    public function rangeUploadIin()
    {
        $input = Request::all();

        $data = $this->service(E::IIN)->addIinRange($input);

        return ApiResponse::json($data);
    }

    public function editIin($id)
    {
        $input = Request::all();

        $data = $this->service(E::IIN)->editIin($id, $input);

        return ApiResponse::json($data);
    }

    public function postIinGenerate()
    {
        $input = Request::all();
        $fileName = $this->service(E::IIN)->generateIinFile($input);

        return ApiResponse::json($fileName);
    }

}
