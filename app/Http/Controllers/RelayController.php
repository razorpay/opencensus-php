<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class RelayController extends Controller
{
    public function createProps($appID)
    {
        $response = $this->app['relay']->createProps($appID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function updateProps($appID, $propID)
    {
        $response = $this->app['relay']->updateProps($appID, $propID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function deleteProps($appID, $propID)
    {
        $response = $this->app['relay']->deleteProps($appID, $propID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function getPendingProps($appID)
    {
        $response = $this->app['relay']->getPendingProps($appID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function propsAction()
    {
        $response = $this->app['relay']->propsAction($this->input);

        return ApiResponse::json($response['body']);
    }

    public function getApps()
    {
        $response = $this->app['relay']->getApps($this->input);

        return ApiResponse::json($response['body']);
    }

    public function getAppByID($appID)
    {
        $response = $this->app['relay']->getAppByID($appID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function updateApps($appID)
    {
        $response = $this->app['relay']->updateApps($appID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function deleteApps($appID)
    {
        $response = $this->app['relay']->deleteApps($appID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function createApp()
    {
        $response = $this->app['relay']->createApp($this->input);

        return ApiResponse::json($response['body']);
    }

    public function getProps($appID)
    {
        $response = $this->app['relay']->getProps($appID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function createBulkProps($appID)
    {
        $response = $this->app['relay']->createBulkProps($appID, $this->input);

        return ApiResponse::json($response['body']);
    }

    public function getPropsHistory($appID, $propID)
    {
        $response = $this->app['relay']->getPropsHistory($appID, $propID, $this->input);

        return ApiResponse::json($response['body']);
    }
}
