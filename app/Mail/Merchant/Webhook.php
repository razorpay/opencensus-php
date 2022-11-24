<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Merchant\Webhook\Event;

class Webhook extends Mailable
{
    protected $webhook;

    protected $merchant;

    protected $options;

    protected $recipientEmail;

    public function __construct(array $webhook, array $merchant, array $options)
    {
        parent::__construct();

        $this->webhook = $webhook;

        $this->merchant = $merchant;

        $this->options = $options;

        $this->recipientEmail = $options['recipient_email'] ?? null;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::ALERTS];

        $header = Constants::HEADERS[Constants::ALERTS];

        $this->from($email, $header);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = $this->recipientEmail ?? $this->merchant['transaction_report_email'];

        $this->to($emails);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $header = Constants::HEADERS[Constants::SUPPORT];

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
            'url' => $this->webhook['url'],
        ];

        $data['date'] = date('d-M-Y H:m:s T');

        if ($this->options['type'] !== 'deactivate')
        {

            $data['error_message'] = $this->options['errorMessage'];

            if (empty($data['error_message']) === true)
            {
                $data['error_message'] = 'Internal Server Error. Please contact the Razorpay team for more details.';
            }

            $eventData = json_decode($this->options['event'], true);

            $data['event'] = $eventData['event'];

            $this->setEntityData($data, $eventData);
        }

        $data['mode'] = $this->options['mode'];

        $data['subject'] = $this->getSubject();

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $webhookId = $this->webhook['id'];

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
        $subjectName = $this->merchant['billing_label'];

        if (empty($subjectName) === true)
        {
            $subjectName = $this->merchant['name'];
        };

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

    protected function getParamsForStork(): array
    {
        return [
            'params' => [
                'url' => $this->webhook['url'],
                'date'=> date('d-M-Y H:m:s T'),
                'mode'=> $this->options['mode'],
                'subject' => $this->getSubject(),
                ]
        ];
    }
}
