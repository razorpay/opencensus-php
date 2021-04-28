<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\Freshchat;

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

    public function putChatTimingsConfig()
    {
        $input = Request::all();

        $response = (new Freshchat\Service)->putChatTimingsConfig([Freshchat\Constants::CONFIG => $input]);

        return ApiResponse::json($response);
    }

    public function getChatTimingsConfig()
    {
        $response = (new Freshchat\Service)->getChatTimingsConfig();

        return ApiResponse::json($response);
    }

    public function putChatHolidaysConfig()
    {
        $input = Request::all();

        $response = (new Freshchat\Service)->putChatHolidaysConfig([Freshchat\Constants::CONFIG => $input]);

        return ApiResponse::json($response);
    }

    public function getChatHolidaysConfig()
    {
        $response = (new Freshchat\Service)->getChatHolidaysConfig();

        return ApiResponse::json($response);
    }
}
