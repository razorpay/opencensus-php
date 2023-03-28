<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\Document;
use RZP\Constants\Entity as Entity;

class DocumentController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Document\Service::class;

    public function uploadMerchantDocuments()
    {
        $input = Request::all();

        $response = $this->service(Entity::MERCHANT_DOCUMENT)->uploadActivationFileMerchant($input);

        return ApiResponse::json($response);
    }

    public function uploadMerchantDocumentsInternal($id)
    {
        $input = Request::all();

        $merchant = (new Core())->get($id);

        $response = $this->service(Entity::MERCHANT_DOCUMENT)->uploadActivationFileForMerchant($input, $merchant);

        return ApiResponse::json($response);
    }


    /**
     * @Todo add comments
     * @return mixed
     */
    public function getMerchantDocuments()
    {
        $response = $this->service(Entity::MERCHANT_DOCUMENT)->fetchActivationFilesFromDocument();

        return ApiResponse::json($response);
    }

    /**
     * @Todo add comments
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getMerchantDocumentsByAdmin(string $merchantId)
    {
        $response = $this->service(Entity::MERCHANT_DOCUMENT)->fetchActivationFilesFromDocument($merchantId);

        return ApiResponse::json($response);
    }

    public function merchantDocumentDelete(string $merchantId, string $id)
    {
        $response = $this->service(Entity::MERCHANT_DOCUMENT)->merchantDocumentDelete($merchantId, $id);

        return ApiResponse::json($response);
    }

    public function delete(string $id)
    {
        $response = $this->service(Entity::MERCHANT_DOCUMENT)->delete($id);

        return ApiResponse::json($response);
    }

    public function getDocumentTypes()
    {
        $response = $this->service(Entity::MERCHANT_DOCUMENT)->getDocumentTypes();

        return ApiResponse::json($response);
    }
    public function fetchFIRSDocuments()
    {
        $input = Request::all();

        $response = $this->service(Entity::MERCHANT_DOCUMENT)->fetchFIRSDocuments($input);

        return ApiResponse::json($response);
    }

    public function uploadFilesByAgent()
    {
        $input = Request::all();

        $response = $this->service(Entity::MERCHANT_DOCUMENT)->uploadFilesByAgent($input);

        return ApiResponse::json($response);
    }

    public function downloadFIRSDocuments()
    {
        $input = Request::all();

        $response = $this->service(Entity::MERCHANT_DOCUMENT)->DownloadFIRSDocuments($input);

        return ApiResponse::json($response);
    }

    public function collectAndZipFIRSDocuments()
    {
        $input = Request::all();

        $response = $this->service(Entity::MERCHANT_DOCUMENT)->collectAndZipFIRSDocuments($input);

        return ApiResponse::json($response);
    }

    public function getSignedUrl(string $documentId)
    {
        $response = $this->service(Entity::MERCHANT_DOCUMENT)->getSignedUrl($documentId);

        return ApiResponse::json($response);
    }

}
