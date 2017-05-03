<?php

namespace RZP\Http\Controllers;

class TaxGroupController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = \RZP\Models\Tax\Group\Service::class;
}
