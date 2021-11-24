<?php

namespace RZP\Mail\Dispute;

use App;
use Razorpay\Trace\Logger;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Currency\Currency;
use RZP\Services\SalesForceClient;
use Illuminate\Contracts\Foundation\Application;
use RZP\Trace\TraceCode;

class Base extends Mailable
{
    protected $data;
    protected $trace;

    /**
     * The Illuminate application instance.
     *
     * @var Application
     */
    protected $app;

    const EXCLUDE_EMAIL_FROM_CC_ON_CHARGEBACK_EMAILS = "businessops@razorpay.com";
    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $fromName = Constants::HEADERS[Constants::DISPUTES];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addRecipients()
    {
        $merchantEmail = $this->data['merchant']['email'];

        $this->to($merchantEmail);

        return $this;
    }

    protected function addCc()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $this->cc($email);

        try
        {
            $salesPOCEmailId = $this->app['salesforce']->getSalesPOCForMerchantID($this->data['merchant']['id']);

            if ($salesPOCEmailId !== self::EXCLUDE_EMAIL_FROM_CC_ON_CHARGEBACK_EMAILS)
            {
                $this->cc($salesPOCEmailId);
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e,
                Logger::ERROR,
                TraceCode:: ERROR_IN_FETCHING_SALES_POC,
                [
                    'merchantId' => $this->data['merchant']['id'],
                ]);
        }

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $replyToName = Constants::HEADERS[Constants::DISPUTES];

        $this->replyTo($email, $replyToName);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function getFormattedAmount($amount, $currency)
    {
        return Currency::getSymbol($currency) . ' ' . ((float) ($amount / Currency::getDenomination($currency)));
    }

}
