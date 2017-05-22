<?php

namespace RZP\Http\Controllers;

use Request;
use Trace;
use ApiResponse;

use Razorpay\OAuth\Client;

class OAuthClientController extends Controller
{
    /**
     * @var \RZP\Models\Merchant\Entity
     */
    protected $merchant;

    /**
     * @var Client\Service
     */
    protected $clientService;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->clientService = new Client\Service;
    }

    public function getClient(string $id)
    {
        $client = $this->clientService->fetch($id, $this->merchant->getId());

        return ApiResponse::json($client);
    }

    public function createClient()
    {
        $input = Request::all();

        $merchantId = $this->merchant->getId();

        $client = $this->clientService->create($input, $merchantId);

        return ApiResponse::json($client);
    }

    /**
     * Update the given client.
     *
     * @param  string  $clientId
     * @return ApiResponse
     */
    public function editClient($clientId)
    {
        $input = \Request::all();

        // $client = (new Client\Service)->update($clientId, $input);
        $client = null;

        return ApiResponse::json($client);
    }

    public function deleteClient(string $id)
    {
        $data = (new Client\Service)->delete($id, $this->merchant->getId());

        return ApiResponse::json($data);
    }
}
