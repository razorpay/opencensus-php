<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

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
}
