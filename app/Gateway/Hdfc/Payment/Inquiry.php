<?php

namespace RZP\Gateway\Hdfc\Payment;

use Illuminate\Support\Facades\Auth;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Hdfc\Payment;
use RZP\Models\Payment\AuthType;
use RZP\Trace\TraceCode;
use RZP\Models\Payment as PaymentModel;
use RZP\Models\Payment\Verify\Action as VerifyAction;

trait Inquiry
{
    use Base\AuthorizeFailed;

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new Base\ScroogeResponse();

        $unprocessedRefunds = $this->getUnprocessedRefunds();

        $processedRefunds = $this->getProcessedRefunds();

        if (in_array($input['refund']['id'], $unprocessedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::REFUND_MANUALLY_CONFIRMED_UNPROCESSED)
                                   ->toArray();
        }

        if (in_array($input['refund']['id'], $processedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        $response = $this->sendRefundVerifyRequest($input);

        $data = $response['data'];

        $scroogeResponse->setGatewayVerifyResponse(json_encode($response['xml']))
                        ->setGatewayKeys($this->getGatewayData($data));

        if (isset($response['error']['result']) === true)
        {
            if ($response['error']['code'] === 'GW00201')
            {
                return $scroogeResponse->setSuccess(false)
                                       ->setStatusCode(ErrorCode::GATEWAY_ERROR_SUPPORT_AUTH_NOT_FOUND)
                                       ->toArray();
            }

            $data = $response['error'];
        }
        else if (($response['data']['result'] === 'FAILURE(SUSPECT)') and
                 ($response['data']['trackid'] === $input['refund']['id']) and
                 ((int) ($response['data']['amt'] * 100) === $input['refund']['amount']) and
                 (empty($response['data']['authRespCode']) === true) and
                 (empty($response['data']['auth']) === false))
        {
            // Changing the result to `CAPTURED` as FSS returns `FAILURE(SUSPECT)`
            $response['data']['result'] = 'CAPTURED';

            $refund = $this->repo->findByRefundId($input['refund']['id']);

            $attributes = $this->getSuccessfulVerifyRefundAttributes($input, $response['data']);
            $refund->fill($attributes);

            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        else if (($data['result'] === 'FAILURE(SUSPECT)') and
                 ($data['trackid'] === $input['refund']['id']) and
                 ((int) ($data['amt'] * 100) === $input['refund']['amount']) and
                 (empty($data['authRespCode']) === false) and
                 (Hdfc\ErrorCodes\ErrorCodes::shouldRetryRefund($data['authRespCode']) === true))
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->toArray();
        }

        $this->trace->critical(
            TraceCode::GATEWAY_REFUND_VERIFY_UNEXPECTED,
            $data);

        throw new Exception\LogicException(
            'Unexpected refund verify result received',
            ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS,
            [
                PaymentModel\Gateway::GATEWAY_VERIFY_RESPONSE  => json_encode($response['xml']),
                PaymentModel\Gateway::GATEWAY_KEYS             =>
                    [
                        'payment_id' => $input['refund']['payment_id'],
                        'refund_id'  => $input['refund']['id'],
                    ],
            ]);
    }

    protected function getSuccessfulVerifyRefundAttributes($input, $responseData)
    {
        $attributes = [
            'received'                  => '1',
            'payment_id'                => $input['payment']['id'],
            'refund_id'                 => $input['refund']['id'],
            'gateway_transaction_id'    => $responseData['tranid'],
            'amount'                    => $responseData['amt'],
            'action'                    => Action::REFUND,
            'status'                    => Payment\Status::REFUNDED,
            'result'                    => $responseData['result'],
            'ref'                       => $responseData['ref'],
            'auth'                      => $responseData['auth'],
            'avr'                       => $responseData['avr'],
            'postdate'                  => $responseData['postdate']
        ];

        return $attributes;
    }

    protected function getPaymentToVerify(Verify $verify)
    {
        $input = $verify->input;

        //
        // We do this because current payment verify logic relies on the
        // error code of the gateway entity.
        // In case of a timeout, two entities get created in the hdfc entity
        // and this causes issue with the reconciliation
        // That's why we are filtering out all the payments where
        // enroll_result is null
        $payments = $this->repo->findPaymentsByPaymentIdToVerify($input['payment']['id']);

        if (($input['payment']['auth_type'] === AuthType::PIN) or
            ($this->isSecondRecurringPaymentRequest($input) === true))
        {
            $payment = $payments->firstOrFail();

            $verify->payment = $payment;

            return $payment;
        }

        $payment = $payments->filter(function ($payment)
        {
            return ($payment->getEnrollResult() !== null);
        })->first();

        $verify->payment = $payment;

        return $payment;
    }

    protected function checkResponseAndThrowExceptionIfRequired($verify)
    {
        if ((empty($verify->verifyResponse['error']['code']) === false) and
            ($verify->verifyResponse['error']['code'] === 'GW00201'))
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                VerifyAction::FINISH);
        }
    }

    protected function verifyPayment($verify)
    {
        $this->checkResponseAndThrowExceptionIfRequired($verify);

        // gateway entity in db
        // NOTE: This is an entity and not an array.
        $gatewayPayment = $verify->payment;

        // Gateway response from verify_payment
        $content = $verify->verifyResponseContent;

        // payment entity in db
        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        if (($this->wasEnrollSuccessful($gatewayPayment) === false) and
            ($input['payment']['auth_type'] !==AuthType::PIN ))
        {
            $verify->match = true;

            return $verify->status;
        }

        $successStatusArray = Status::getSuccessStatusArray();

        if (empty($content['trackid']) === false)
        {
            assertTrue ($content['trackid'] === $input['payment']['id']);
        }

        if ((isset($content['result'])) and
            (($content['result'] === Result::APPROVED) or
             ($content['result'] === Result::CAPTURED)))
        {
            $verify->gatewaySuccess = true;

            //
            // Following situations have been accounted for:
            // * Api payment status is success. Api hdfc payment status
            //   is also success. Leading to brand it as success and moving on.
            //
            // * Second case is an interesting one. Here, api payment status is
            //   successful. But, for some reason hdfc payment status stored with us
            //   indicates failure. This could happen mostly due to race conditions
            //   like for example callback route being hit twice very quickly.
            //   This will cause auth request being sent twice very quickly. In
            //   this situation, the second request will fail and hdfc payment will
            //   be marked as unsuccessful. However, api payment will still be successful
            //   because of first case. So, we mark hdfc payment as successful and move on.
            // * Otherwise it's an error probably on our side. We will deal with more
            //   cases as we discover them.
            //

            if ((in_array($gatewayPayment['status'], $successStatusArray, true) === true) and
                ($input['payment']['status'] !== 'failed') and
                ($input['payment']['status'] !== 'created'))
            {
                $verify->apiSuccess = true;
            }
            // api's payment entity could be in either authorized or captured state
            // and gateway's payment entity status is in failed state. This is an issue
            // and should ideally never happen.
            else if ((in_array($gatewayPayment['status'], $successStatusArray, true) === false) and
                     ($input['payment']['status'] !== 'failed') and
                     ($input['payment']['status'] !== 'created'))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'api_payment_status'      => $input['payment']['status'],
                        'gateway_verify_response' => $content['result'],
                        'payment_id'              => $input['payment']['id'],
                        'gateway_payment_status'  => $gatewayPayment['status'],
                    ]);

                $verify->apiSuccess = true;
                $this->fillPaymentStatusAndContent($verify);
            }
            else
            {
                $verify->status = VerifyResult::STATUS_MISMATCH;
                $verify->apiSuccess = false;
            }
        }
        else
        {
            $verify->gatewaySuccess = false;

            // If payment is marked as success in api or in gateway entity, but gateway's verify response
            // returned false. This is an issue and should ideally never happen.
            if (($input['payment']['status'] !== 'failed') and
                ($input['payment']['status'] !== 'created'))
            {
                // Ideally both api payment entity status and gateway payment entity status should be true,
                // to reach this block. In case even if one of them is not true, we log it.
                if ((in_array($gatewayPayment['status'], $successStatusArray, true) === false) or
                    (($input['payment']['status'] === 'failed') or ($input['payment']['status'] === 'created')))
                {
                    $this->trace->info(
                        TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                        [
                            'api_payment_status'      => $input['payment']['status'],
                            'gateway_verify_response' => $content,
                            'payment_id'              => $input['payment']['id'],
                            'gateway_payment_status'  => $gatewayPayment['status'],
                        ]);
                }

                $verify->apiSuccess = true;
                $verify->status = VerifyResult::STATUS_MISMATCH;
            }
            else
            {
                $verify->apiSuccess = false;
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $received = $gatewayPayment->getReceived();

        // If result is set then it means we had received back a response.
        if ($gatewayPayment->getResult() === null)
        {
            if ($received === null)
            {
                $gatewayPayment->setReceived(false);
            }

            $this->fillPaymentStatusAndContent($verify);
        }

        if (($gatewayPayment->getResult() !== null) and
            ($gatewayPayment->getAction() === Action::PURCHASE))
        {
            if ((isset($content['result']) === true) and
                ($content['result'] === Result::CAPTURED))
            {
                $gatewayPayment->setStatus(Status::CAPTURED);
            }
        }

        if (empty($content['tranid']) === false)
        {
            $gatewayPayment->setGatewayTransactionId($content['tranid']);
        }

        $gatewayPayment->saveOrFail();

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        return $verify->status;
    }

    protected function fillPaymentStatusAndContent($verify)
    {
        $status = null;

        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;

        if ($verify->gatewaySuccess === true)
        {
            if ($content['result'] === Result::APPROVED)
            {
                $status = Status::AUTHORIZED;
            }
            else if ($content['result'] === Result::CAPTURED)
            {
                $status = Status::CAPTURED;
            }
            else
            {
                throw new Exception\LogicException(
                    'Not expecting this result code',
                    null,
                    [
                        'result'     => $content['result'],
                        'payment_id' => $content['trackid'],
                    ]);
            }

            $payment->setStatus($status);

            $payment->fill($content);
        }
    }

    protected function wasEnrollSuccessful($payment)
    {
        $enrollResult = $payment->getEnrollResult();

        switch ($enrollResult)
        {
            case Result::INITIALIZED:
            case Result::ENROLLED:
            case Result::NOT_ENROLLED:
                return true;
                break;

            case Result::FSS0001_ENROLLED:
            case Result::UNKNOWN_ERROR_ENROLLED:
                return false;
                break;

            case null:
                return false;
                break;

            default:
                throw new Exception\LogicException(
                    'Unexpected enroll result code',
                    null,
                    [
                        'payment_id' => $payment->getPaymentId(),
                        'result'     => $enrollResult,
                    ]);
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        // Gets the payment entity that has to be verified
        $payment = $verify->payment;

        // Gets the request array for verify from gateway
        $requestContent = $this->getPaymentVerifyRequestContentArray($verify);

        $this->inquiryRequest['url'] = Hdfc\Urls::SUPPORT_PAYMENT_URL;

        // Sets the request body for the inquiry (verifying payment status)
        $this->inquiryRequest['data'] = $requestContent;

        // TO NOTE: This is just initializing RESPONSE from the inquiry.
        $this->inquiryResponse['data'] = [];

        $traceVerifyData = $this->inquiryRequest;

        unset($traceVerifyData['content']);

        unset($traceVerifyData['data']['member']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $traceVerifyData);

        // This sets the response received from the inquiry
        $this->runRequestResponseFlow(
            $this->inquiryRequest,
            $this->inquiryResponse);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'xml'               => $this->inquiryResponse['xml'],
                'response_content'  => $this->inquiryResponse['data'],
                'gateway'           => $this->gateway
            ]);

        $this->checkAndSetResponseResult($payment);

        $inquiryResponse = $this->inquiryResponse;

        $verify->verifyResponse = $inquiryResponse;
        $verify->verifyResponseBody = $inquiryResponse['xml'];
        $verify->verifyResponseContent = $inquiryResponse['data'];

        return $inquiryResponse['data'];
    }

    protected function getPaymentVerifyRequestContentArray($verify)
    {
        $payment = $verify->payment;

        $content['action'] = Action::INQUIRY;

        $content['transid'] = $payment['payment_id'];

        $content['udf5'] = 'TrackID';

        $content['amt'] = $verify->input['payment']['amount'] / 100;
        $content['member'] = $verify->input['card']['name'];
        $content['trackid'] = $verify->input['payment']['id'];

        $traceContent = $content;

        unset($traceContent['member']);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY, $traceContent);

        return $content;
    }

    protected function sendRefundVerifyRequest(array $input)
    {
        // Gets the request array for verify from gateway
        $requestContent = $this->getRefundVerifyRequestContentArray($input);

        // Sets the gateway URL for the inquiry (verifying payment status)
        $this->inquiryRequest['url'] = Hdfc\Urls::SUPPORT_PAYMENT_URL;

        // Sets the request body for the inquiry (verifying payment status)
        $this->inquiryRequest['data'] = $requestContent;

        // TO NOTE: This is just initializing RESPONSE from the inquiry.
        $this->inquiryResponse['data'] = [];

        $traceVerifyData = $this->inquiryRequest;

        unset($traceVerifyData['content']);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            $traceVerifyData);

        // This sets the response received from the inquiry
        $this->runRequestResponseFlow(
            $this->inquiryRequest,
            $this->inquiryResponse);

        $inquiryResponse = $this->inquiryResponse;

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'xml' => $inquiryResponse['xml'],
                'response_content' => $inquiryResponse['data']
            ]);

        return $inquiryResponse;
    }

    protected function getRefundVerifyRequestContentArray(array $input)
    {
        $content['action'] = Action::INQUIRY;
        $content['transid'] = $input['refund']['id'];
        $content['udf5'] = 'TrackID';

        $content['amt'] = $input['refund']['amount'] / 100;
        $content['member'] = 'test';

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY, $content);

        return $content;
    }

    protected function checkAndSetResponseResult($payment)
    {
        $responseData = & $this->inquiryResponse['data'];

        if ((isset($responseData['result'])) and
            ($responseData['result'] === Result::SUCCESS))
        {
            $paymentAction = $payment['action'];

            if ($paymentAction === Action::AUTHORIZE)
            {
                $result = Result::APPROVED;
            }
            else if ($paymentAction === Action::PURCHASE)
            {
                $result = Result::CAPTURED;
            }
            else
            {
                throw new Exception\LogicException(
                    'Unexpected action',
                    null,
                    [
                        'payment_id' => $this->input['payment']['id'],
                        'action'     => $paymentAction,
                    ]);
            }

            $responseData['result'] = $result;
        }
    }
}
