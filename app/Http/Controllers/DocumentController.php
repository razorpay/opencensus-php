<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant\Document;

class DocumentController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Document\Service::class;
}
