<?php

namespace RZP\Mail\Invoice;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Invoice\Type;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Preferences;
use RZP\Constants as C;

class Base extends Mailable
{
    /**
     * Overridden in child classes(specific mail types), holds templates
     * per type.
     *
     * @var array
     */
    const SUBJECT_TEMPLATES = [
        Type::LINK    => '',
        Type::ECOD    => '',
        Type::INVOICE => '',
    ];

    const MAIL_TAG_MAP = [
        Type::LINK    => MailTags::LINK,
        Type::ECOD    => MailTags::ECOD,
        Type::INVOICE => MailTags::INVOICE,
    ];

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = $this->getSenderEmail();

        $fromHeader = $this->data['merchant']['name'];

        $this->from($fromEmail, $fromHeader);

        return $this;
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

    protected function addRecipients()
    {
        if (isset($this->data['invoice']['customer_details']) === true)
        {
            $customerEmail = $this->data['invoice']['customer_details']['email'];

            $this->to($customerEmail);
        }

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubjectByInvoiceType();

        $this->subject($subject);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $header = Constants::HEADERS[Constants::NOREPLY];

        $this->replyTo($email, $header);

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
            $invoiceType = $this->data['invoice']['type'];

            $label = self::MAIL_TAG_MAP[$invoiceType] ?? MailTags::INVOICE;

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['invoice']['id']);

            $headers->addTextHeader(MailTags::HEADER, $label);
        });

        return $this;
    }

    /**
     * Returns subject to use for mails based on invoice's type.
     * The subject templates for mails per type has different placeholders.
     *
     * @return string
     */
    protected function getSubjectByInvoiceType(): string
    {
        $merchantId = $this->data['merchant']['id'];

        $type = $this->data['invoice']['type'];

        $template = static::SUBJECT_TEMPLATES[$type];

        if ($type === Type::INVOICE)
        {
            $args = [
                $this->data['merchant']['name'],
            ];
        }
        else
        {
            $args = [
                $this->data['invoice']['currency'],
                $this->data['invoice']['amount_formatted'],
            ];
        }

        switch ($merchantId)
        {
            case Preferences::MID_RBL_RETAIL_ASSETS:
            case Preferences::MID_RBL_RETAIL_CUSTOMER:
            case Preferences::MID_RBL_RETAIL_PRODUCT:

                if (empty(static::SUBJECT_TEMPLATES[$merchantId]) === false)
                {
                    $template = static::SUBJECT_TEMPLATES[$merchantId];

                    $args = [];
                }

                break;

            case Preferences::MID_RBL_INTERIM_PROCESS2:
            case Preferences::MID_BOB_FIN:

                if ($this->data['invoice']['entity_type'] === C\Entity::SUBSCRIPTION_REGISTRATION)
                {
                    $template = static::SUBJECT_TEMPLATES[$merchantId];

                    $args = [];
                }

                break;

            case Preferences::MID_ADITYA_BIRLA_HEALTH:

                if ($this->data['invoice']['entity_type'] === C\Entity::SUBSCRIPTION_REGISTRATION)
                {
                    $template = static::SUBJECT_TEMPLATES[$merchantId];

                    $args = [ $this->data['invoice']['receipt'] ];
                }

                break;

            default:

                break;
        }

        if(empty($this->data['pp_invoice']) === false)
        {
            $template = $this->getPpMailSubject();

            $template = str_replace('%', '%%', $template);

            $args = [];

            app('trace')->info(TraceCode::PAYMENT_PAGE_SUBJECT_CREATION_FOR_INVOICE, [
                "template"      => $template,
                "args"          => $args,
            ]);

        }

        return sprintf($template, ...$args);
    }

    protected function getPpMailSubject()
    {
        $enable80g = $this->data['invoice'][C\Entity::PAYMENT_PAGE]['enable_80g'];

        $startText = "Payment ";

        if ($enable80g == "1")
        {
            $startText = "Donation ";
        }

        $appendText = $startText. "receipt for your successful transaction ";

        $viewType = $this->data['invoice'][C\Entity::PAYMENT_PAGE]['view_type'];

        if ($viewType === 'button')
        {
            $template = "with ".$this->data['merchant']['name'];
        }
        else
        {
            $template = "on ".$this->data['invoice'][C\Entity::PAYMENT_PAGE]['title'];
        }

        $template = $appendText. $template;

        return $template;
    }
}
