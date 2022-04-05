<?php

namespace RZP\Mail\Payment;

class Captured extends Base
{
    protected function addHtmlView()
    {
        $this->view('emails.payment.merchant');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.merchant_text');

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function getParamsForStork(): array
    {
        $data = $this->data;

        $storkParams = [
            'template_namespace' => 'payments_core',
            'params'             => [
                'payment_public_id'         => $data['payment']['public_id'],
                'amount_symbol'             => $data['payment']['amount_spread'][0],
                'amount_units'              => $data['payment']['amount_spread'][1],
                'amount_subunits'           => $data['payment']['amount_spread'][2],
                'customer_email'            => $data['customer']['email'],
                'customer_contact_mobile'   => $data['customer']['phone'],
            ],
        ];

        if (empty($data['payment']['orderId']) === false)
        {
            $storkParams['template_name'] = 'merchant.payments.captured_with_order_id';
            $storkParams['params']['payment_order_id'] = $data['payment']['orderId'];
        }
        else
        {
            $storkParams['template_name'] = 'merchant.payments.captured_without_order_id';
        }

        return $storkParams;
    }
}
