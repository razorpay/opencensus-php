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
        try
        {
            $response = Requests::post($baseUrl, [], $postData);
            return ["Screenshots generated successfully"];
        }
        catch(\Exception $e)
        {
            return ["There was an error in generating the screenshots"];
        }
    }
}
