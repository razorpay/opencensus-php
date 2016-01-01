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
        $status = VerifyResult::STATUS_MATCH;

        $verify->match = true;

        return $status;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
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
            $content);

        $verify->verifyResponse = $inquiryResponse['response'];
        $verify->verifyResponseBody = $inquiryResponse['xml'];
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getPaymentVerifyRequestContentArray($verify)
    {
        $payment = $verify->payment;

        $data['action'] = Payment\Action::INQUIRY;
        $data['transid'] = $payment['gateway_transaction_id'];
        $data['udf5'] = $payment['gateway_transaction_id'];

        return $data;
    }
}
