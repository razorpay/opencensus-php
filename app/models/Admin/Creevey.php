<?php
namespace Models\Admin;

use Config;
use Requests;

class Creevey
{
    const HEADERS = [
        'Content-Type' => 'application/json'
    ];

    const OPTIONS = [
        'timeout'   => 120,
        'useragent' => 'Razorpay/Dashboard'
    ];

    public function fire($job, array $data)
    {
        $id = $data[0];
        $urls = $data[1];

        $baseUrl = Config::get('creevey.root');

        // Now we make the post request
        $postData = json_encode([
            'url'   =>  $urls,
            'token' =>  Config::get('creevey.token'),
            'id'    =>  $merchantId
        ]);

        $response = Requests::post($baseUrl,
            self::HEADERS,
            $postData,
            self::OPTIONS
        );

        if ($response->success)
        {
            $job->delete();
        }
        else
        {
            // This will automatically release the job back to the queue
            throw new \Exception("Invalid response from creevey: {$response->status_code}");
        }
    }
}
