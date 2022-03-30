<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Constants\Entity;

class TransferController extends Controller
{
    public function getTransfer(string $id)
    {
        $input = Request::all();

        $transfer = $this->service()->fetch($id, $input);

        return ApiResponse::json($transfer);
    }

    public function getTransfers()
    {
        $input = Request::all();

        $transfers = $this->service()->fetchMultiple($input);

        return ApiResponse::json($transfers);
    }

    public function getLinkedAccountTransfers()
    {
        $input = Request::all();

        $transfers = $this->service()->fetchLinkedAccountTransfers($input);

        return ApiResponse::json($transfers);
    }

    public function getPaymentIdForLinkedAccountTransfer(string $id)
    {
        $input = Request::all();

        $transfer = $this->service()->fetchLinkedAccountTransferByPaymentId($id, $input);

        return ApiResponse::json($transfer);
    }

    public function getLinkedAccountTransfer(string $id)
    {
        $transfer = $this->service()->fetchLinkedAccountTransfer($id);

        return ApiResponse::json($transfer);
    }

    public function getTransferReversals(string $id)
    {
        $reversals = $this->service()->fetchReversalsOfTransfer($id);

        return ApiResponse::json($reversals);
    }

    public function getLinkedAccountTransferReversals(string $id)
    {
        $reversals = $this->service()->fetchLinkedAccountReversalsOfTransfer($id);

        return ApiResponse::json($reversals);
    }

    public function postTransfer()
    {
        $input = Request::all();

        $transfer = $this->service()->create($input);

        return ApiResponse::json($transfer);
    }

    public function postTransferReversal(string $id)
    {
        $input = Request::all();

        $reversal = $this->service()->reverse($id, $input);

        return ApiResponse::json($reversal);
    }

    public function postLinkedAccountTransferReversal(string $id)
    {
        $input = Request::all();

        $reversal = $this->service()->linkedAccountReversal($id, $input);

        return ApiResponse::json($reversal);
    }

    public function patchTransfer(string $id)
    {
        $input = Request::all();

        $transfer = $this->service()->edit($id, $input);

        return ApiResponse::json($transfer);
    }

    public function transferUpdateSettelements(string $id)
    {
        $transferscount = $this->service()->processTransfersSettelements($id);

        $data = [
            'settlementId'       => $id ,
            'transfersCount'     => $transferscount,

        ];
        return ApiResponse::json($data);
    }

    public function processPendingOrderTransfers()
    {
        $input = Request::all();

        $orderIds = $this->service()->processPendingOrderTransfers($input);

        return ApiResponse::json($orderIds);
    }

    public function processPendingPaymentTransfers()
    {
        $input = Request::all();

        $orderIds = $this->service()->processPendingPaymentTransfers($input);

        return ApiResponse::json($orderIds);
    }


    public function processFailedOrderTransfers()
    {
        $input = Request::all();

        $orderIds = $this->service()->processFailedOrderTransfers($input);

        return ApiResponse::json($orderIds);
    }

    public function debugRoute()
    {
        $input = Request::all();

        $response = $this->service(Entity::TRANSACTION)->dispatchIdealLedgerJob($input);

        return ApiResponse::json($response);

//        return ApiResponse::json(
//            [
//                'msg'   => 'Transfer/Route debug route. Use this route for debugging/data corrections via dark',
//                'input' => $input
//            ]
//        );
    }

    public function createTransferReversalFromBatch($id)
    {
        $input = Request::all();

        $reversal = $this->service()->createReversalFromBatch($id, $input);

        return ApiResponse::json($reversal);
    }
}
