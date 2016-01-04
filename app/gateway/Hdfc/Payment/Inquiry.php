<?php

namespace Gateway\Hdfc\Payment;

use EE\Exception;
use Gateway\Base;
use Gateway\Base\Action;
use Gateway\Base\VerifyResult;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment;
use Trace\Trace;
use Trace\TraceCode;

trait Inquiry
{
    use Base\AuthorizeFailed;

    protected function inquire($input)
    {
        $payment = $input['payment'];

        $this->id = $input['payment']['id'];

        $txn = $this->repo->findByPaymentId($this->id);

        $txn = $txn[0];

        $data = &$this->inquiryRequest['data'];

        $data['action'] = Payment\Action::INQUIRY;
        $data['transid'] = $txn['gateway_transaction_id'];

        $this->runRequestResponseFlow(
            $this->inquiryRequest,
            $this->inquiryResponse);

//        sd($input['payment'], $this->inquiryResponse['xml']);
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->repo->findByPaymentIdToVerify($input['payment']['id']);

        $verify->payment = $payment;

        return $payment;
    }

    protected function verifyPayment($verify)
    {
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $error = $verify->verifyResponse['error'];

        $verify->status = VerifyResult::STATUS_MATCH;

        if ($this->wasEnrollSuccessful($payment) === false)
        {
            return $verify->status;
        }

        $successStatusArray = Status::getSuccessStatusArray();

        if ((isset($content['result'])) and
            (($content['result'] === Result::APPROVED) or
             ($content['result'] === Result::CAPTURED)))
        {
            $verify->gatewaySuccess = true;

            if (in_array($payment['status'], $successStatusArray))
            {
                $verify->apiSuccess = true;
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

            if (in_array($payment['status'], $successStatusArray))
            {
                $verify->apiSuccess = true;
                $verify->status = VerifyResult::STATUS_MISMATCH;
            }
            else
            {
                $verify->apiSuccess = false;
            }
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $received = $payment->getReceived();

        // If result is set then it means we had received back a response.
        if ($payment->getResult() === null)
        {
            if ($received === null)
            {
                $payment->setReceived(false);
            }

            $enrollResult = $payment['enroll_result'];

            $status = null;

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

                $payment->setGatewayTransactionId($content['tranid']);
                $payment->setStatus($status);

                unset($content['tranid']);
                $payment->fill($content);
            }
        }
        else
        {
            if ($received === null)
            {
                $this->setReceived(true);
            }
        }

        $payment->saveOrFail();

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        return $verify->status;
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

            default:
                throw new Exception\LogicException(
                    'Unexpected enroll result code: ' . $enrollResult);
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $payment = $verify->payment;
        $content = $this->getPaymentVerifyRequestContentArray($verify);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [$content]);

        $data = &$this->inquiryRequest['data'];
        $data = $content;

        $this->runRequestResponseFlow(
            $this->inquiryRequest,
            $this->inquiryResponse);

        $inquiryResponse = $this->inquiryResponse;

        $content = $this->inquiryResponse['data'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [
                'payment_id' => $payment->getPaymentId(),
                'xml' => $inquiryResponse['xml']
            ]);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $content);

        $verify->verifyResponse = $inquiryResponse;
        $verify->verifyResponseBody = $inquiryResponse['xml'];
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestContentArray($verify)
    {
        $payment = $verify->payment;

        $content['action'] = Payment\Action::INQUIRY;
        $content['transid'] = $payment['gateway_transaction_id'];
        $content['udf5'] = $payment['gateway_transaction_id'];

        return $content;
    }
}
