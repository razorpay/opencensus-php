<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use Razorpay\Ufh\Client as UfhClient;

class UfhController extends Controller
{
    /**
     * @var UfhClient
     */
    protected $ufhClient;

    public function __construct()
    {
        parent::__construct();

        $ufhConfig = [
            'base_uri' => $this->config['applications.ufh.url']
        ];

        $this->ufhClient = (new UfhClient)->setConfig($ufhConfig);
    }

    public function getSignedUrl(string $fileId)
    {
        $queryParams = [
            'merchant_id' => $this->ba->getMerchantId()
        ];

        // Todo: Handle exceptions
        $response = $this->ufhClient->getSignedUrl($fileId, $queryParams);

        $data = json_decode($response->getBody(), true);

        return ApiResponse::json($data);
    }
}
