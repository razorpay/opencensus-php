<?php

namespace RZP\Http\Controllers;

use RZP\Http\Response\Header;

class RobotsController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        header_remove('X-Powered-By');
    }

    public function nocodeRobots()
    {
        $content = "User-agent: ia_archiver\nDisallow: /";

        return response($content)->header(Header::CONTENT_TYPE, "text/plain");
    }
}
