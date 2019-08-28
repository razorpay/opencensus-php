<?php

namespace RZP\Http\Controllers;

use RZP\Exception\LogicException;

class ExcelStoreController extends Controller
{
    /**
     * Dummy controller method
     */
    public function dummy()
    {
        throw new LogicException('The request should not have reached here');
    }
}
