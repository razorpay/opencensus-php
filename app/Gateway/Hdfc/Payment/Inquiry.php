<?php

namespace RZP\Gateway\Hdfc\Payment;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Payment;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

trait Inquiry
{
    use Base\AuthorizeFailed;

    protected function getPaymentToVerify($verify)
    {
        $input = $verify->input;
        
        $payment = $this->repo->findByPaymentIdToVerify($input['payment']['id']);

        $verify->payment = $payment;

        return $payment;
    }

    protected function verifyPayment($verify)
    {
        // gateway entity in db
        // NOTE: This is an entity and not an array.
        $gatewayPayment = $verify->payment;

        // Gateway response from verify_payment
        $content = $verify->verifyResponseContent;

        // payment entity in db
        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($this->wasEnrollSuccessful($gatewayPayment) === false)
        {
            $verify->match = true;

            return $verify->status;
        }

        $successStatusArray = Status::getSuccessStatusArray();

        if (empty($content['trackid']) === false)
        {
            assert ($content['trackid'] === $input['payment']['id']);
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

            if ((in_array($gatewayPayment['status'], $successStatusArray) === true) and
                ($input['payment']['status'] !== 'failed') and
                ($input['payment']['status'] !== 'created'))
            {
                $verify->apiSuccess = true;
            }
            // api's payment entity could be in either authorized or captured state
            // and gateway's payment entity status is in failed state. This is an issue
            // and should ideally never happen.
            else if ((in_array($gatewayPayment['status'], $successStatusArray) === false) and
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
            if ((($input['payment']['status'] !== 'failed') and
                 ($input['payment']['status'] !== 'created')) or
                (in_array($gatewayPayment['status'], $successStatusArray) === true))
            {
                // Ideally both api payment entity status and gateway payment entity status should be true,
                // to reach this block. In case even if one of them is not true, we log it.
                if ((in_array($gatewayPayment['status'], $successStatusArray) === false) or
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
                    'Not expecting this result code: ' . $content['result']);
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
                    'Unexpected enroll result code: ' . $enrollResult);
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        // Gets the payment entity that has to be verified
        $payment = $verify->payment;

        // Gets the request array for verify from gateway
        $requestContent = $this->getPaymentVerifyRequestContentArray($verify);

        // Sets the gateway URL for the inquiry (verifying payment status)
        $this->inquiryRequest['url'] = Hdfc\Urls::SUPPORT_PAYMENT_URL;

        // Sets the request body for the inquiry (verifying payment status)
        $this->inquiryRequest['data'] = $requestContent;

        // TO NOTE: This is just initializing RESPONSE from the inquiry.
        $this->inquiryResponse['data'] = [];

        $traceVerifyData = $this->inquiryRequest;

        unset($traceVerifyData['content']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $traceVerifyData);

        // This sets the response received from the inquiry
        $this->runRequestResponseFlow(
            $this->inquiryRequest,
            $this->inquiryResponse);

        $this->checkAndSetResponseResult($payment);

        $inquiryResponse = $this->inquiryResponse;
        $responseContent = $this->inquiryResponse['data'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'payment_id' => $payment->getPaymentId(),
                'xml' => $inquiryResponse['xml'],
                'response_content' => $responseContent
            ]);

        $verify->verifyResponse = $inquiryResponse;
        $verify->verifyResponseBody = $inquiryResponse['xml'];
        $verify->verifyResponseContent = $responseContent;

        return $responseContent;
    }

    protected function getPaymentVerifyRequestContentArray($verify)
    {
        $payment = $verify->payment;

        $content['action'] = Action::INQUIRY;
        $content['transid'] = $payment['gateway_transaction_id'];
        $content['udf5'] = 'PaymentID';

        $content['amt'] = $verify->input['payment']['amount']/100;
        $content['member'] = $verify->input['card']['name'];
        $content['trackid'] = $verify->input['payment']['id'];

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
                    'Unexpected action: ' . $paymentAction);
            }

            $responseData['result'] = $result;
        }
    }
}
