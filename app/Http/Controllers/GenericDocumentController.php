<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\GenericDocument\Service;

class GenericDocumentController extends Controller
{
    protected $service = Service::class;

    public function uploadDocument()
    {
        $input = Request::all();

        return $this->service()->uploadDocument($input);
    }

    public function getDocument(string $fileStoreId)
    {
        $input = Request::all();

        return  $this->service()->getDocument($input, $fileStoreId);
    }

    public function getDocumentContent(string $fileStoreId)
    {
        $input = Request::all();

        return  $this->service()->getDocumentContent($input, $fileStoreId);
    }

}