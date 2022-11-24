<?php

namespace RZP\Models\Payment\Fraud\Notifications;

use RZP\Models\Payment\Fraud\Constants\Notification as Constants;

class Config
{
    protected $fraudType = '';

    protected $smsEnabled = false;

    protected $emailEnabled = false;

    protected $whatsappEnabled = false;

    protected $freshdeskTicketEnabled = false;

    protected $smsTemplate = '';

    protected $whatsappTemplate = '';

    protected $whatsappTemplateName = '';

    protected $emailHandler = '';

    protected $emailProvider = '';

    protected $notifyEmailInterval = Constants::INSTANT_NOTIFY;

    protected $notifySmsInterval = Constants::INSTANT_NOTIFY;

    protected $notifyWhatsappInterval = Constants::INSTANT_NOTIFY;

    protected $notifyFreshdeskTicketInterval = Constants::INSTANT_NOTIFY;

    public function __construct(string $fraudType, array $settings)
    {
        $this->fraudType = $fraudType;

        $this->processSettings($settings);
    }

    private function processSettings($settings)
    {
        $this->processEmailSettings($settings);

        $this->processFreshdeskTicketSettings($settings);

        $this->processSmsSettings($settings);

        $this->processWhatsappSettings($settings);
    }

    private function processEmailSettings($settings)
    {
        if (isset($settings[Constants::EMAIL]) === false)
        {
            return;
        }

        // handler cannot be empty for mailgun
        if (empty($settings[Constants::EMAIL][Constants::HANDLER]) === true
            and (empty($settings[Constants::EMAIL][Constants::PROVIDER]) === true
            or $settings[Constants::EMAIL][Constants::PROVIDER] === Constants::MAILGUN)
        )
        {
            return;
        }

        $this->emailEnabled = true;

        $this->emailHandler = $settings[Constants::EMAIL][Constants::HANDLER];

        $this->emailProvider = $settings[Constants::EMAIL][Constants::PROVIDER];

        if (empty($settings[Constants::EMAIL][Constants::NOTIFY_INTERVAL]) === true)
        {
            return;
        }

        $notifyInterval = $settings[Constants::EMAIL][Constants::NOTIFY_INTERVAL];

        $this->notifyEmailInterval = $notifyInterval;

    }

    private function processFreshdeskTicketSettings($settings)
    {
        if (isset($settings[Constants::FRESHDESK_TICKET]) === false)
        {
            return;
        }

        $this->freshdeskTicketEnabled = true;

        if (empty($settings[Constants::FRESHDESK_TICKET][Constants::NOTIFY_INTERVAL]) === true)
        {
            return;
        }

        $notifyInterval = $settings[Constants::FRESHDESK_TICKET][Constants::NOTIFY_INTERVAL];

        $this->notifyFreshdeskTicketInterval = $notifyInterval;
    }

    private function processSmsSettings($settings)
    {
        if (isset($settings[Constants::SMS]) === false)
        {
            return;
        }

        if (empty($settings[Constants::SMS][Constants::TEMPLATE]) === true)
        {
            return;
        }

        $this->smsEnabled = true;

        $this->smsTemplate = $settings[Constants::SMS][Constants::TEMPLATE];

        $notifyInterval = $settings[Constants::SMS][Constants::NOTIFY_INTERVAL];

        $this->notifySmsInterval = $notifyInterval;
    }

    private function processWhatsappSettings($settings)
    {
        if (isset($settings[Constants::WHATSAPP]) === false)
        {
            return;
        }

        if (empty($settings[Constants::WHATSAPP][Constants::TEMPLATE]) === true
            or empty($settings[Constants::WHATSAPP][Constants::TEMPLATE_NAME]) === true)
        {
            return;
        }

        $this->whatsappEnabled = true;

        $this->whatsappTemplate = $settings[Constants::WHATSAPP][Constants::TEMPLATE];

        $this->whatsappTemplateName = $settings[Constants::WHATSAPP][Constants::TEMPLATE_NAME];

        $notifyInterval = $settings[Constants::WHATSAPP][Constants::NOTIFY_INTERVAL];

        $this->notifyWhatsappInterval = $notifyInterval;

    }

    public function isSmsEnabled(): bool
    {
        return $this->smsEnabled;
    }

    public function isEmailEnabled(): bool
    {
        return $this->emailEnabled;
    }

    public function isWhatsappEnabled(): bool
    {
        return $this->whatsappEnabled;
    }

    public function isFreshdeskTicketEnabled(): bool
    {
        return $this->freshdeskTicketEnabled;
    }

    public function getSmsTemplate(): string
    {
        return $this->smsTemplate;
    }

    public function getWhatsappTemplate(): string
    {
        return $this->whatsappTemplate;
    }

    public function getWhatsappTemplateName(): string
    {
        return $this->whatsappTemplateName;
    }

    public function getEmailHandler(): string
    {
        return $this->emailHandler;
    }

    public function getEmailProvider(): string
    {
        return $this->emailProvider;
    }

    public function emailInstantly(): bool
    {
        return $this->notifyEmailInterval === Constants::INSTANT_NOTIFY;
    }

    public function smsInstantly(): bool
    {
        return $this->notifySmsInterval === Constants::INSTANT_NOTIFY;
    }

    public function whatsappInstantly(): bool
    {
        return $this->notifyWhatsappInterval === Constants::INSTANT_NOTIFY;
    }

    public function freshdeskTicketInstantly(): bool
    {
        return $this->notifyFreshdeskTicketInterval === Constants::INSTANT_NOTIFY;
    }

    public function getEmailInterval(): int
    {
        return $this->notifyEmailInterval;
    }

    public function getSmsInterval(): int
    {
        return $this->notifySmsInterval;
    }

    public function getWhatsappInterval(): int
    {
        return $this->notifyWhatsappInterval;
    }

    public function getFreshdeskTicketInterval(): int
    {
        return $this->notifyFreshdeskTicketInterval;
    }

    public function getFraudType(): string
    {
        return $this->fraudType;
    }
}
