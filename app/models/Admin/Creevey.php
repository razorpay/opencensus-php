<?php
namespace Models\Admin;

use Config;
use Requests;

class Creevey
{
    public static function takeScreenshot($merchantId, array $urls)
    {
        $baseUrl = Config::get('creevey.root');

        // Now we make the post request
        $postData = [
            'url'   =>  $urls,
            'token' =>  Config::get('creevey.token'),
            'id'    =>  $merchantId
        ];

        $response = Requests::post($baseUrl, [], $postData);

        sd($response->body);
    }
}
