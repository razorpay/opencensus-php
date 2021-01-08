<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Entity as Entity;

class DocumentControllerV2 extends Controller
{
    public function uploadDocuments()
    {
        $input = Request::all();

        return $this->service(Entity::MERCHANT_DOCUMENT)->uploadDocument($input);

    }

}