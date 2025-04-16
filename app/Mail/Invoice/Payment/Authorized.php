<?php

namespace RZP\Mail\Invoice\Payment;

use RZP\Mail\Payment\Base;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\Invoice\Type;
use RZP\Trace\TraceCode;

/**
 * We are extending Mail\Payment\Base class here instead of Invoice|base
 * as this mailable requires some payment related data
 */
class Authorized extends Base
{
    protected $invoiceData;

    public function setInvoiceDetails(array $invoiceData)
    {
        $this->invoiceData = $invoiceData;
    }

    protected function getSenderHeader(): string
    {
        return $this->invoiceData['merchant']['name'];
    }

    /**
     * Overridden: For non-invoice types we have new format. Later we will
     * fix the subject lines for invoice type as well, for now calling parent
     * for invoice type.
     */
    protected function addSubject()
    {
        $type = $this->invoiceData['invoice']['type'];

        if ($type === Type::INVOICE)
        {
            return parent::addSubject();
        }

        $amount = $this->data['payment']['amount'];

        $subject = "Payment of Rs. {$amount} is successful (via Razorpay)";

        $this->subject($subject);

        return $this;
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
        $this->view('emails.invoice.customer.notification');

        return $this;
    }

    protected function addMailData()
    {
        $this->data['invoice'] = $this->invoiceData['invoice'];

        $this->data['merchant'] += $this->invoiceData['merchant'];

        $this->with($this->data);

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        $app = \App::getFacadeRoot();

        $isStorkEmailVIAEnabled = $this->isSendingPaymentLinkMailsSupported($this->data['merchant']['id'],$this->view);

        $traceData = [
            'merchant_id' => $this->data['merchant']['id'],
            'view' => $this->view,
            'data' => $this->data,
            'isStorkEmailVIAEnabled' => $isStorkEmailVIAEnabled
        ];

        $app['trace']->info(TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_VIA_SPLITZ_PAYMENT_AUTHORIZED , $traceData);

        return $isStorkEmailVIAEnabled;
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

    public function isSendingPaymentLinkMailsSupported($merchantId,$view) : bool {
        $traceCode = TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_STORK_PAYMENT_AUTHORIZED;

        $experimentId = 'app.send_payment_link_emails_via_stork_payment_authorized';

        try {
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

        } catch (\Exception $e) {
            $app['trace']->traceException($e, null, $traceCode);
        }
        return false;
    }
}
