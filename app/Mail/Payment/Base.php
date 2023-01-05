<?php

namespace RZP\Mail\Payment;

use Symfony\Component\Mime\Email;

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

        $this->mid = $this->mid ?? $data['merchant']['id'] ?? null;
    }

    protected function addSender()
    {
        $email  = $this->getSenderEmail();

        // hardcoding this for fraud relates issues will change this with some dynamic value.
        //$header = $this->getSenderHeader();
        $header = 'Payments';

        $this->from($email, $header);

        return $this;
    }

    protected function addReplyTo()
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        $email = Constants::getSenderEmailForOrg($orgCode, Constants::NOREPLY);

        $this->replyTo($email);

        return $this;
    }

    protected function addRecipients()
    {
        $email = "";

        if ($this->isMerchantEmail === true)
        {
            $email = $this->data['merchant']['email'];
        }
        else
        {
            $email = $this->data['customer']['email'];
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

        if (isset($this->data['rewards']) === true)
        {
            $subject = "Your payment for $label is successful. Here’s your checkout reward 🎁";

            if(isset($this->data['email_variant']))
            {
                $variant = $this->data['email_variant'];

                $brand = $this->data['rewards'][0]['brand_name'] ?? '';

                if($variant === 'variant_1')
                {
                    $subject = "Exciting reward from $brand inside 🎁. Your payment for $label was successful";
                }
                elseif($variant === 'variant_2')
                {
                    $subject = "Payment successful for $label. Exciting reward inside 🎁";
                }
                elseif($variant === 'variant_3')
                {
                    $subject = "Payment for $label successful. Exciting reward from $brand inside 🎁";
                }
            }

        }

        if ($this->isMerchantEmail === true)
        {
            $orgName = "Razorpay";

            if ((isset($this->data['custom_branding']) === true) and 
                ($this->data['custom_branding'] === true) and
                (isset($this->data['org']['custom_code']) === true) and  
                ($this->data['org']['custom_code'] == 'curlec'))
            {
                $orgName = $this->data['org_name'];
            }

            $subject = $orgName . " | $subject";
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
        $this->withSymfonyMessage(function (Email $message)
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
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderEmailForOrg($orgCode, Constants::NOREPLY);
    }

    protected function getSenderHeader(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderNameForOrg($orgCode, Constants::NOREPLY);
    }

    protected function getCustomerSupportText()
    {
        $merchantId = $this->data['merchant']['id'];

        // Default text
        $supportTextPlain = "We are a payment gateway and only facilitate merchants with on-line payments. We request you to contact the merchant for any service related queries. "
                            . "If you want to dispute a payment, please contact us at https://razorpay.com/contact/";
        $supportTextHtml = "We are a payment gateway and only facilitate merchants with on-line payments."
                            . "<br style=\"font-family: 'Century Gothic', 'Lucida Sans', 'Tahoma', 'Arial' !important;\">"
                            . "We request you to contact the merchant for any service related queries."
                            . "<br style=\"font-family: 'Century Gothic', 'Lucida Sans', 'Tahoma', 'Arial' !important;\">"
                            . "If you want to dispute a payment, please contact us <a href=\"https://razorpay.com/contact/\">here</a>";

        // Zebpay
        if ($merchantId === '8iMbVsEnv1HCo0')
        {
            $supportTextPlain = $supportTextHtml =
                'If this is correct, you don\'t need to take any further action. '
                . 'Please contact the Zebpay team for any service related queries. '
                . 'File a ticket here - ticket.zebpay.com';
        }
        // Koinex
        else if ($merchantId === '8Gx5vN29m83OUY')
        {
            $supportTextPlain = $supportTextHtml =
                'If this is correct, you don\'t need to take any further action. '
                . 'Please contact the Koinex team for any service related queries. Email: team@koinex.in '
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

    public function getSupportEmailInReplyTo(bool $isMerchantEmail = false):string
    {
        $merchantId = $this->data['merchant']['id'];

        // Zebpay
        if ($merchantId === '8iMbVsEnv1HCo0')
        {
            return 'support@zebpay.com';
        }
        // Koinex
        else if ($merchantId === '8Gx5vN29m83OUY')
        {
            return 'team@koinex.in';
        }

        else if ($isMerchantEmail === false and
            isset($this->data['merchant']['support_details']) and
            isset($this->data['merchant']['support_details']['email']))
        {
            return $this->data['merchant']['support_details']['email'];
        }

        $orgCode = $this->data['org']['custom_code'] ?? '';

        $email = Constants::getSenderEmailForOrg($orgCode, Constants::NOREPLY);

        return $email;

    }

}
