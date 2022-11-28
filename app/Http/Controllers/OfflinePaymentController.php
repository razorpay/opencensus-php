<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Error\PublicErrorCode;
use RZP\Models\OfflinePayment\Entity;
use RZP\Models\OfflinePayment\StatusCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\OfflinePayment;


class OfflinePaymentController extends Controller
{

    public function processOfflinePayment()
    {
        $input = Request::all();

        $transformer = new OfflinePayment\Transformer();

        try
        {
            $request = $transformer->convertInputToOfflineGenericRequest($input);
        }
        catch (\Throwable $e)
        {
            $failureMessage = $e->getMessage();

            return $this->createOfflineResponse($input['challan_number'], $failureMessage, 1);
        }

        $errStatus = $this->service()->checkIfChallanExists($request);

        if ($errStatus !== null)
        {
            return $this->createOfflineResponse($request[Entity::CHALLAN_NUMBER],$errStatus, 1);
        }

        $transformer->validateCustomRequestPayload($request);

        $offlinePayment    = null;
        $errorMessage      = null;

        try {
            $this->service()->processOfflinePayment($request);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::OFFLINE_PAYMENT_PROCESSING_FAILED, $input);

            $errorMessage = $ex->getMessage();

            if (($offlinePayment === null) or ($offlinePayment->getUnexpectedReason() === null))
            {
                $response =  $this->createOfflineResponse($request[Entity::CHALLAN_NUMBER],$errorMessage,1);
            }
            else
            {
                $response =  $this->createOfflineResponse($request[Entity::CHALLAN_NUMBER],$offlinePayment->getUnexpectedReason(),1);
            }

            return ApiResponse::json($response);
        }

        $response = $this->createOfflineResponse($request[Entity::CHALLAN_NUMBER],StatusCode::SUCCESS,0);

        $this->trace->info(TraceCode::OFFLINE_PAYMENT_CREDIT_RESPONSE,
            [
                'Response'     => $response ?? null,
            ]);

        $resp = ApiResponse::json($response);

        $resp->headers->set('content-security-policy', "default-src 'self' https:");

        $resp->headers->set('x-content-type-options', "nosniff");

        return $resp;
    }

    public function createOfflineResponse(string $challan_num, $failureMessage, $statusCode)
    {
        $response['challan_no'] = $challan_num;

        $response[Entity::STATUS] = $statusCode;

        if ($statusCode === 0) {
            $errorBody = null;
        } else {
            $errorBody = [
                'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                'description' => $failureMessage,
                'field' => '',
                'source' => 'business',
                'step' => null,
                'reason' => $failureMessage,
                'metadata' => []
            ];
        }

        $response[Entity::ERROR] = $errorBody;

        $transformer = new OfflinePayment\Transformer();
        return $transformer->createCustomOfflinePaymentResponse($response);
    }
}
