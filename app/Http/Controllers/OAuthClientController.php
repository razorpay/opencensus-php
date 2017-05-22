<?php

namespace RZP\Http\Controllers;

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

    /**
     * Store a new client.
     *
     * @return ApiResponse
     */
    public function postCreateClient()
    {
        $input = \Request::all();

        // $client = (new ComposerClient\Service)->create($input);    TODO
        $client = null;

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

    /**
     * Delete the given client.
     *
     * @param  string  $clientId
     * @return ApiResponse
     */
    public function deleteClient($clientId)
    {
        // $data = (new Client\Service)->delete($clientId);
        $data = null;

        return ApiResponse::json($data);
    }
}
