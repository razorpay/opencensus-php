<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class PromotionController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = \RZP\Models\Promotion\Service::class;
}
