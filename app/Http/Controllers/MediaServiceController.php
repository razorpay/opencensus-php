<?php

namespace RZP\Http\Controllers;
use ApiResponse;
use Request;
use RZP\Trace\TraceCode;

class MediaServiceController extends Controller
{
    protected $config;

    protected $mediaService;

    public function __construct()
    {
        parent::__construct();

        $this->config      = $this->app['config']->get('applications.media_service');

        $this->mediaService = $this->app['media_service'];
    }

    public function postUploadFile()
    {
        $input = Request::all();

        $response = $this->mediaService->postUploadFile($input);

        return ApiResponse::json($response);
    }

    public function getBuckets()
    {
        $response = $this->mediaService->getBuckets();
        return ApiResponse::json($response);
    }

    public function postUploadProcess()
    {
        $input = Request::all();
        $response = $this->mediaService->postUploadProcess($input);
        return ApiResponse::json($response);
    }
}
