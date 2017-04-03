<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Common;
use RZP\Mail\Base\Mailable;
use RZP\Models\Merchant\Webhook\Entity as WebhookEntity;
use RZP\Models\Merchant\Webhook\Event;

class Webhook extends Mailable
{
    protected $webhook;

    protected $options;

    public function __construct(WebhookEntity $webhook, array $options)
    {
        $this->webhook = $webhook;

        $this->options = $options;
    }

    protected function addSender()
    {
        $email = Common::MAIL_ADDRESSES[Common::ALERTS];

        $header = Common::FROM_HEADER[Common::ALERTS];

        $this->from($email, $header);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = $this->webhook->merchant->getTransactionReportEmail();

        $this->to($emails);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Common::MAIL_ADDRESSES[Common::SUPPORT];

        $header = Common::FROM_HEADER[Common::SUPPORT];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'url' => $this->webhook->getUrl(),
        ];

        $data['error_message'] = $this->options['errorMessage'];

        if (empty($data['error_message']) === true)
        {
            $data['error_message'] = 'Internal Server Error. Please contact the Razorpay team for more details.';
        }

        $data['date'] = date('d-M-Y H:m:s T');

        $eventData = json_decode($this->options['event'], true);

        $data['event'] = $eventData['event'];

        $this->setEntityData($data, $eventData);

        $data['mode'] = $this->options['mode'];

        $data['subject'] = $this->getSubject();
 $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $webhookId = $this->webhook->getPublicId();

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::WEBHOOK);

            $headers->addTextHeader(MailTags::HEADER, $webhookId);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $view = 'emails.webhook.' . $this->options['type'];

        $this->view($view);

        return $this;
    }

    protected function getSubject()
    {
        $subjectName = $this->webhook->merchant->getBillingLabelElseName();

        $subject = 'Razorpay | ';

        if ($this->options['type'] === 'failure')
        {
            $subject .= 'Webhook failed for ' . $subjectName;
        }
        else if ($this->options['type'] === 'deactivate')
        {
            $subject .= 'Webhook deactivated after 24 hours from last successful delivery for ' . $subjectName;
        }

        return $subject;
    }

    protected function setEntityData(array & $mailData, array $eventData)
    {
        $event = $eventData['event'];

        if (isset(Event::$eventsToEntityMap[$event]) === false)
        {
            return;
        }

        $entityType = Event::$eventsToEntityMap[$event];

        $mailData['entity_id'] = $eventData['payload'][$entityType]['entity']['id'];
        $mailData['field_description'] = (studly_case($entityType) . " " . "Id");
    }
}
