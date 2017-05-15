<?php

namespace RZP\Http\Controllers;

class TaxController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = \RZP\Models\Tax\Service::class;
}
