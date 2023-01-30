<?php

namespace RZP\Http\Controllers;

use Request;
use Response;
use ApiResponse;
use RZP\Constants\HyperTrace;
use RZP\Models\QrPayment\Service;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class QrPaymentController extends Controller
{
    const BARRICADE_ACTION = 'merchant_integration_fetch_verify';
    const BARRICADE_MERCHANT_INTEGRATION_QRPAYMENT_FLOW = 'barricade_merchant_integration_qr_payment_flow';
    public function fetchMultiplePayments()
    {
        $input = Request::all();

        $entities = (new Service())->fetchMultiplePayments($input);

        return ApiResponse::json($entities);
    }

    public function fetchQrCodePayments($id)
    {
        $input = Request::all();

        $entities = Tracer::inspan(['name' => HyperTrace::QR_PAYMENT_FETCH_PAYMENT_BY_QR_CODE_ID], function () use ($input, $id) {
            return (new Service())->fetchPaymentsForQrCode($input, $id);
        });

        return ApiResponse::json($entities);
    }

    public function fetchCheckoutPaymentStatusByQrCodeId($qrCodeId)
    {
        $response = Tracer::inspan(['name' => HyperTrace::QR_PAYMENT_FETCH_PAYMENT_STATUS_BY_QR_CODE_ID], function () use ($qrCodeId) {
            return (new Service())->fetchPaymentStatusByQrCodeId($qrCodeId);
        });
        $this->pushForBarricade($response, $qrCodeId);

        return ApiResponse::json($response);


    }

    protected function pushForBarricade($payment, $qrCodeId): void
    {
        $sqsPush = $this->app->razorx->getTreatment($qrCodeId, self::BARRICADE_MERCHANT_INTEGRATION_QRPAYMENT_FLOW, $this->app['rzp.mode']);

        if ($sqsPush === 'on') {

            $data = $payment;
            $data['payment_details'] = [
                'id' => $qrCodeId,
            ];
            $data['action'] = [
                'action' => self::BARRICADE_ACTION
            ];

            try {
                $waitTime = 600;
                $queueName = $this->app['config']->get('queue.barricade_verify.' . $this->app['rzp.mode']);
                $this->app['queue']->connection('sqs')->later($waitTime, "Barricade Queue Push", json_encode($data), $queueName);


                $this->trace->info(TraceCode::BARRICADE_SQS_PUSH_SUCCESS,
                    [
                        'queueName' => $queueName,
                        'data' => $data
                    ]);

            } catch (\Throwable $ex) {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::BARRICADE_SQS_PUSH_FAILURE,
                    [
                        'payment_id' => $payment->getId(),
                    ]);
            }
        }
    }

}
