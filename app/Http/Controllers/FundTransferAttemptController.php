<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Entity;
use RZP\Models\FundTransfer\Attempt\Constants;
use RZP\Services\FTS\Constants as FTSConstants;
use RZP\Models\FundAccount\Validation as FundAccountValidation;

class FundTransferAttemptController extends Controller
{
    public function bulkUpdate()
    {
        $input = Request::all();

        $service = $this->service(Entity::FUND_TRANSFER_ATTEMPT);

        $response = $service->bulkUpdate($input);

        return ApiResponse::json($response);
    }

    public function reconcileFundTransfers(string $channel)
    {
        $input = Request::all();

        $service = $this->service(Entity::FUND_TRANSFER_ATTEMPT);

        $response = $service->reconcileFundTransfers($input, $channel);

        return ApiResponse::json($response);
    }

    public function initiateFundTransfers(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->initiateFundTransfers($input, $channel);

        return ApiResponse::json($data);
    }

    public function sendFTAReconReport()
    {
        $data = $this->service()->sendFTAReconReport();

        return ApiResponse::json($data);
    }

    public function nodalFileUploadThroughBeam()
    {
        $input = Request::all();

        $data = $this->service()->nodalFileUploadThroughBeam($input);

        return ApiResponse::json($data);
    }

    public function updateSource()
    {
        $input = Request::all();

        $response = $this->service()->updateFundTransferAttempt($input);

        return ApiResponse::json($response);
    }

    public function healthCheck(string $channel)
    {
        $input = Request::all();

        $response =  $this->service()->healthCheck($channel, $input);

        return ApiResponse::json($response);
    }

    public function setChannelState(string $channel,string $action)
    {
        $response = $this->service()->setChannelState($channel, $action);

        return ApiResponse::json($response);
    }

    public function getChannelState()
    {
        $response = $this->service()->getChannelState();

        return ApiResponse::json($response);
    }

    public function processFundTransfersUsingFts(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->processFundTransfersUsingFts($input, $channel);

        return ApiResponse::json($response);
    }

    public function getSupportedModes()
    {
        $input = Request::all();

        $response = $this->service()->getSupportedModes($input);

        return ApiResponse::json($response);
    }
}
