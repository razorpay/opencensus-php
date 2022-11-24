<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Notifications\Onboarding\Events;

class MerchantOnboardingEmail extends Mailable
{
    protected $data;

    protected $org;

    protected $template;

    protected $event;

    /**
     * @var array
     */
    private $files;

    public function __construct(array $data, array $org, string $event,string $template, string $subject, $files)
    {
        parent::__construct();

        $this->data     = $data;
        $this->org      = $org;
        $this->subject  = $subject;
        $this->template = $template;
        $this->files    = $files;
        $this->event    = $event;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    protected function addRecipients()
    {
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->template);

        return $this;
    }

    protected function addSender()
    {
        if ($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $this->from($this->org['from_email'], $this->org['display_name']);
        }
        else
        {
            $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
            $senderName  = Constants::HEADERS[Constants::NOREPLY];

            $this->from($senderEmail, $senderName);
        }

        return $this;
    }

    protected function addBcc()
    {
        if ($this->org[Org\Entity::ID] === Org\Entity::RAZORPAY_ORG_ID and
            empty($this->event) === false and
            array_key_exists($this->event, Events::EMAIL_CC) === true)
        {
            $this->cc(Events::EMAIL_CC[$this->event]);
        }

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();
        });

        return $this;
    }

    protected function addAttachments()
    {
        if (empty($this->files) === false)
        {
            foreach ($this->files as $file)
            {
                $this->attach($file);
            }
        }

        return $this;
    }
}
