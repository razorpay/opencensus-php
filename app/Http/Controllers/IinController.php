<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;

class IinController extends Controller
{
    public function postIin()
    {
        $input = Request::all();

        if (isset($input['file']))
        {
            $data = $this->service()->importIin($input);
        }
        else
        {
            $data = $this->service()->addIin($input);
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

        $data = $this->service()->addIinRange($input);

        return ApiResponse::json($data);
    }

    public function editIin($id)
    {
        $input = Request::all();

        $data = $this->service()->editIin($id, $input);

        return ApiResponse::json($data);
    }

    public function editIinBulk()
    {
        $input = Request::all();

        $data = $this->service()->editIinBulk($input);

        return ApiResponse::json($data);
    }

    public function postIinGenerate()
    {
        $input = Request::all();

        $fileName = $this->service()->generateIinFile($input);

        return ApiResponse::json($fileName);
    }

    public function validateIinIssuer()
    {
        $input = Request::all();

        $response = $this->service()->validateIinIssuer($input);

        return ApiResponse::json($response);
    }

    public function getIinsList()
    {
        $input = Request::all();

        $response = $this->service()->getIinsList($input);

        return ApiResponse::json($response);
    }

    public function processRecords(string $type)
    {
        $input = Request::all();

        $response = $this->service()->processRecords($type, $input);

        return ApiResponse::json($response);
    }

    public function getIinDetails()
    {
        $input = Request::all();

        $data = $this->service()->getIinDetails($input);

        return ApiResponse::json($data);
    }

    public function getIssuerDetails($id)
    {
        $data = $this->service()->getIssuerDetails($id);

        return ApiResponse::json($data);
    }

    public function getIin($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);

    }

    public function disableMultipleIINFlows()
    {
        $input = Request::all();

        $response = $this->service()->disableMultipleIINFlows($input);

        return ApiResponse::json($response);
    }
}
