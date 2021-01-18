<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class FreshchatController extends Controller
{
    public function postExtractReport()
    {
        $this->app['freshchat_client']->extractReport();

        return ['success' => true];
    }

    public function postRetrieveReport()
    {
        $this->app['freshchat_client']->retrieveReport();

        return ['success' => true];
    }
}
