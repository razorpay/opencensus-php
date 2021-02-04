<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Exception;
use RZP\Models\EMandate;
use RZP\Trace\TraceCode;

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
        $data = $this->service()->processNachBatch($this->input);

        $this->trace->info(
            TraceCode::BATCH_PROCESSING_API_RESPONSE,
            [
                'data' => $data
            ]);

        if(isset($data['data']['Error Code']) === true)
        {
            return ApiResponse::json(['Status' => 'Failure', 'body' => $data], 400);
        }
        return ApiResponse::json($data);
    }
}
