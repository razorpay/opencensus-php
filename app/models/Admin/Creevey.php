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
            $response = Requests::post($baseUrl, [], $postData,[
                'timeout'   => 120,
                'useragent' => 'Razorpay/Dashboard'
            ]);

            if($response->status_code === 200)
            {
                return [];
            }
            else
            {
                throw new \Exception("Invalid response from creevey: {$response->status_code}");
            }
        }
        catch(\Exception $e)
        {
            return [$e->getMessage()];
        }
    }
}
