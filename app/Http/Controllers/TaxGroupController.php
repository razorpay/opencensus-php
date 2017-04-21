<?php

namespace RZP\Http\Controllers;

use RZP\Models\Tax\Group as TaxGroup;

class TaxGroupController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new TaxGroup\Service;
    }
}
