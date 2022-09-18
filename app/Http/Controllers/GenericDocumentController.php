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

    public function uploadDocumentV1()
    {
        return $this->uploadDocument();
    }

    public function getDocument(string $fileStoreId)
    {
        $input = Request::all();

        return  $this->service()->getDocument($input, $fileStoreId);
    }

    public function getDocumentV1(string $fileStoreId)
    {
        return $this->getDocument($fileStoreId);
    }

    public function getDocumentContent(string $fileStoreId)
    {
        $input = Request::all();

        return  $this->service()->getDocumentContent($input, $fileStoreId);
    }

    public function getDocumentContentV1(string $fileStoreId)
    {
        return $this->getDocumentContent($fileStoreId);
    }

}
