<?php

namespace RZP\Http\Controllers;
Use ApiResponse;


class EdgeController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * To support proxy auth and admin auth request authentication at Edge until user auth is supported
     * Edge will call this endpoint to authenticate proxy auth and admin auth requests.
     * Passport generation and authorization (for most of the cases) will still happen at Edge.
     * Refer this doc: https://docs.google.com/document/d/18-Z9BvzlTAyYuoVqJdxRg60hO2qQpBi5DXkno_xhJH0/edit#heading=h.nmbffcswpsi5
     */
    public function authenticate()
    {
        $response['message'] = "Work in Progress";

        return ApiResponse::json($response);
    }
}
