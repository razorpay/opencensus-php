<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Base extends Mailable
{
    protected $data;

    protected $isMerchantEmail;

    public function __construct(array $data, bool $isMerchantEmail = false)
    {
        parent::__construct();

        $this->data = $data;

        $this->isMerchantEmail = $isMerchantEmail;
    }

    protected function addSender()
    {
        $email  = $this->getSenderEmail();
        $header = $this->getSenderHeader();

        $this->from($email, $header);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $this->replyTo($email);

        return $this;
    }

    protected function addRecipients()
    {
        $email = $this->data['customer']['email'];

        if ($this->isMerchantEmail === true)
        {
            $email = $this->data['merchant']['email'];
        }

        $this->to($email);

        return $this;
    }

    protected function addSubject()
    {
        $action = $this->getAction();

        /**
         * The reason we have a fallback to the amount here is because
         * not every merchant necessarily has a proper billing label (most do)
         * Since the dba field was moved from the dashboard to the API after a
         * while. All new merchants have this field for sure, though. We
         * can do a survey later and remove this check from here and other
         * places
         */
        $label = $this->data['merchant']['billing_label'] ?? $this->data['payment']['amount'];

        $subject = "$action successful for $label";

        if ($this->isMerchantEmail === true)
        {
            $subject = "Razorpay | $subject";
        }

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $paymentId = $this->data['payment']['id'];

            $mailTag = $this->getMailTag();

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $paymentId);

            $headers->addTextHeader(MailTags::HEADER, $mailTag);
        });

        return $this;
    }

    protected function getAction()
    {
        return 'Payment';
    }

    protected function getMailTag()
    {
        return MailTags::PAYMENT_SUCCESSFUL;
    }

    public function isCustomerReceiptEmail()
    {
        return false;
    }

    public function isMerchantEmail()
    {
        return $this->isMerchantEmail;
    }

    protected function getSenderEmail(): string
    {
        return ($this->isMerchantEmail() === true) ?
                    Constants::MAIL_ADDRESSES[Constants::CARE] :
                    Constants::MAIL_ADDRESSES[Constants::REPORTS];
    }

    protected function getSenderHeader(): string
    {
        return ($this->isMerchantEmail() === true) ?
                    Constants::HEADERS[Constants::CARE] :
                    Constants::HEADERS[Constants::REPORTS];
    }

    protected function getCustomerSupportText()
    {
        $merchantId = $this->data['merchant']['id'];

        // Default text
        $supportTextPlain = 'You can contact us at contact@razorpay.com in case of any discrepancy.';
        $supportTextHtml  = 'Please <a title="Click to send us a mail" href="mailto:contact@razorpay.com" ' . 'style="font-family: \'Century Gothic\', \'Lucida Sans\', \'Tahoma\', \'Arial\' !important; color: #2ba6cb; text-decoration: none;">contact us</a> in case of any discrepancy.';

        // Zebpay
        if ($merchantId === '8iMbVsEnv1HCo0')
        {
            $supportTextPlain = $supportTextHtml =
                'Please contact the Zebpay team for any service related queries. '
                . 'File a ticket here - ticket.zebpay.com';
        }
        // Koinex
        else if ($merchantId === '8Gx5vN29m83OUY')
        {
            $supportTextPlain = $supportTextHtml =
                'Please contact the Koinex team for any service related queries. Email: team@koinex.in '
                . 'Contact link: https://koinex.in/contact_us';
        }

        return [
            'support_text_plain' => $supportTextPlain,
            'support_text_html'  => $supportTextHtml
        ];
    }

    protected function getCustomCustomerReplyToEmail(): string
    {
        $merchantId = $this->data['merchant']['id'];

        $email = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        // Zebpay
        if ($merchantId === '8iMbVsEnv1HCo0')
        {
            $email = 'support@zebpay.com';
        }
        // Koinex
        else if ($merchantId === '8Gx5vN29m83OUY')
        {
            $email = 'team@koinex.in';
        }

        return $email;
    }
}
