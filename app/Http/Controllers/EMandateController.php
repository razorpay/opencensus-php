<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\HyperTrace;
use RZP\Exception;
use RZP\Models\EMandate;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Trace\Tracer;

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

        $data = $this->service()->processNachBatchRequest($this->input, $batchId);

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

        $data = Tracer::inSpan(['name' => HyperTrace::EMANDATE_DEBIT_PAYMENT_PROCESSING_BATCH_REQUEST], function () use ($batchId){
            return $this->service()->processBatchRequest($this->input, $batchId);
        });

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
    
    public function getBulkEmandateConfigs()
    {
        $input = Request::all();
    
        $data = $this->service()->getBulkEmandateConfigs($input);
    
        return ApiResponse::json($data);
    }
    
    public function postBulkEmandateConfigs()
    {
        $input = Request::all();
        
        $data = $this->service()->postBulkEmandateConfigs($input);
        
        return ApiResponse::json($data);
    }
    
    public function editBulkEmandateConfigs()
    {
        $input = Request::all();
    
        $data = $this->service()->editBulkEmandateConfigs($input);
    
        return ApiResponse::json($data);
    }
}
