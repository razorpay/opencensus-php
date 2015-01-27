<?php

namespace Gateway\Hdfc\Payment;

use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment;
use Trace\Trace;
use Trace\TraceCode;

trait Inquiry
{
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
}
