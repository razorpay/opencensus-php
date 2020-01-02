<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * BalanceConfigController to interact with Apis to Fetch/Edit/Create Balance Configs for Balance
 *
 * @package RZP\Http\Controllers
 */
class BalanceConfigController extends Controller
{
    /**
     * Get all BalanceConfigs for this authorized merchant
     *
     * @return mixed
     */
    public function getMerchantBalanceConfigs()
    {
        $response = $this->service()->getMerchantBalanceConfigs();

        return ApiResponse::json($response);
    }

    /**
     * Get BalanceConfig identified by $balanceConfigId.
     *
     * @param string $balanceConfigId
     * @return mixed
     */
    public function getBalanceConfigById(string $balanceConfigId)
    {
        $response = $this->service()->getBalanceConfig($balanceConfigId);

        return ApiResponse::json($response);
    }

    /**
     * Create a BalanceConfig for given $merchantId based on $input parameters
     *
     * @param string $merchantId
     * @return mixed
     */
    public function addBalanceConfig(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service()->createBalanceConfig($merchantId, $input);

        return ApiResponse::json($response, Response::HTTP_CREATED);
    }

    /**
     * Edit BalanceConfig identified by $balanceConfigId, based on $input parameters
     *
     * @param string $balanceConfigId
     * @return mixed
     */
    public function editBalanceConfig(string $balanceConfigId)
    {
        $input = Request::all();

        $response = $this->service()->editBalanceConfig($input, $balanceConfigId);

        return ApiResponse::json($response);
    }
}
