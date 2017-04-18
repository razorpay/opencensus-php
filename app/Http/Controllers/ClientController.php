<?php

namespace RZP\Http\Controllers;

use Trace;
use ApiResponse;
use Illuminate\Http\Request;
// use client service from composer;  TODOuse ApiResponse;

class ClientController extends Controller
{
    use HandlesOAuthErrors;

    /**
     * Get all of the clients for given merchant.
     *
     * @return ApiResponse
     */
    public function getClients()
    {
        $input = \Request::all();

        // $clients = (new ComposerClient\Service)->fetchMultiple($input['merchant_id']); TODO

        // foreach ($clients as $client) {
        //     $client->makeVisible('secret')->toArray(); TODO
        // }

        $clients = null;

        return ApiResponse::json($clients);
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
