<?php

namespace RZP\Http\Controllers;

use RZP\Models\Tax;

class TaxController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Tax\Service;
    }
}
