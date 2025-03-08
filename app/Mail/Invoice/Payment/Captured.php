<?php

namespace RZP\Mail\Invoice\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\EmailHelper;
use RZP\Mail\Payment\Base;
use RZP\Models\Invoice;
use RZP\Models\Invoice\Type;
use RZP\Trace\TraceCode;

/**
 * We are extending Mail\Payment\Base class here instead of Invoice|base
 * as this mailable requires some payment related data
 */
class Captured extends Base
{
    protected $invoiceData;

    public function setInvoiceDetails(array $invoiceData)
    {
        $this->invoiceData = $invoiceData;
    }

    protected function getAction()
    {
        $typeLabel = $this->invoiceData['invoice']['type_label'];

        $action = ucwords($typeLabel) .'\'s Payment';

        return $action;
    }

    protected function getMailTag()
    {
        return MailTags::INVOICE;
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.merchant.captured');

        return $this;
    }

    protected function addMailData()
    {
        $this->data['invoice'] = $this->invoiceData['invoice'];

        $this->data['merchant'] += $this->invoiceData['merchant'];

        $this->with($this->data);

        return $this;
    }

    public function shouldSendEmailViaStork():bool
    {
        $app = \App::getFacadeRoot();

        $merchantID = $this->data['merchant']['id'];

        $isStorkEmailVIAEnabled = ($this->isSendingPaymentServiceMailsSupportedCaptured($merchantID,$this->view));

        return ($isStorkEmailVIAEnabled);
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_name' => $this->view,
            'template_namespace' => 'payments_payment_links',
            'org_id' => $this->data['org']['id'],
            'params' => $this->data
        ];
    }

    public function isSendingPaymentServiceMailsSupportedCaptured($merchantId,$view) : bool {

        $traceCode = TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_VIA_SPLITZ_CAPTURED;

        $experimentId = 'app.send_payment_link_emails_via_stork_captured';

        try
        {
            $app = \App::getFacadeRoot();

            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get($experimentId),
                'request_data'  => json_encode(['merchant_id' => $merchantId , 'template_name' => $view])
            ];

            $response = $app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $app['trace']->info($traceCode, [
                'splitzUserResult' => $response,
            ]);

            return  $variant == "enable";
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException($e, null, TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_STORK_EXCEPTION);
        }

        return false;
    }
}
