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

        $this->clientService = new Client\Service;
    }

    public function getClient(string $id)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $client = $this->clientService->fetch($id, $merchant->getId());

        return ApiResponse::json($client);
    }

    public function createClient()
    {
        $input = Request::all();

        $merchant = $this->app['basicauth']->getMerchant();

        $merchantId = $merchant->getId();

        $client = $this->clientService->create($input, $merchantId);

        return ApiResponse::json($client);
    }

    public function editClient(string $id)
    {
        $input = Request::all();

        $merchant = $this->app['basicauth']->getMerchant();

        $merchantId = $merchant->getId();

        $client = $this->clientService->update($id, $merchantId, $input);

        return ApiResponse::json($client);
    }

    public function deleteClient(string $id)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $data = $this->clientService->delete($id, $merchant->getId());

        return ApiResponse::json($data);
    }
}
