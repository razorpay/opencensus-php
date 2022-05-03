<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Exception;
use RZP\Models\EMandate;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;

class EMandateController extends Controller
{
    protected $service = EMandate\Service::class;

    public function postReconcileDebitFile($gateway)
    {
        if (Request::hasFile('file') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input does not contain the excel file to be processed'
            );
        }

        $input = Request::all();

        $data = $this->service()->reconcileDebitFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postProcessNachDebit()
    {
        $batchId = Request::header(RequestHeader::X_Batch_Id);

        $this->service()->processBatchRequestAsync($this->input, $batchId);

        $data = array_merge($this->input, [
            'Status'            => 'Success',
            'Error Code'        => null,
            'Error Description' => null,
        ]);

        $this->trace->info(
            TraceCode::BATCH_PROCESSING_API_RESPONSE,
            [
                'data'     => $data,
                'batch_id' => $batchId,
            ]);

        if(isset($data['data']['Error Code']) === true)
        {
            return ApiResponse::json(['Status' => 'Failure', 'body' => $data], 400);
        }
        return ApiResponse::json($data);
    }

    public function postProcessEmandateDebit()
    {
        $batchId = Request::header(RequestHeader::X_Batch_Id);

        $data = $this->service()->processBatchRequest($this->input);

        $this->trace->info(
            TraceCode::BATCH_PROCESSING_API_RESPONSE,
            [
                'response_body' => $data,
                'batch_id'      => $batchId,
            ]);

        if(isset($data['data']['Error Code']) === true)
        {
            return ApiResponse::json(['Status' => 'Failure', 'body' => $data], 400);
        }
        return ApiResponse::json(['body' => $data], 200);
    }
}
