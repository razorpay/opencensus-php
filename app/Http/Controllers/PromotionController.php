<?php

namespace RZP\Http\Controllers;

class PromotionController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = \RZP\Models\Promotion\Service::class;
}
