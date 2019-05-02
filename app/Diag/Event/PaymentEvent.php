<?php

namespace RZP\Diag\Event;

use App;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment;

class PaymentEvent extends Event
{
    const EVENT_TYPE = 'payment-events';
    const EVENT_VERSION = 'v1';

    protected $payment = null;

    protected $customProperties = null;

    protected $exception = null;

    public function __construct(Payment\Entity $payment = null, \Throwable $ex = null, array $customProperties = [])
    {
        $this->app = App::getFacadeRoot();

        $this->payment = $payment;

        $this->customProperties = $customProperties;

        $this->exception = $ex;
    }

    public function getProperties()
    {
        $properties = [];

        if ($this->payment !== null)
        {
            $properties = $this->getPaymentProperties();
        }

        $this->addErrorDetails($properties);

        $this->removeSenstiveFields();

        $properties['properties'] = $this->customProperties;

        return $properties;
    }

    protected function getPaymentProperties()
    {
        $properties = [];

        $this->addPaymentDetails($properties);

        $this->addMerchantDetails($properties);

        return $properties;
    }

    protected function addMerchantDetails(array &$properties)
    {
        $merchant = $this->payment->merchant;

        $properties['merchant'] = [
                'id'        => $merchant->getId(),
                'name'      => $merchant->getBillingLabel(),
                'mcc'       => $merchant->getCategory(),
                'category'  => $merchant->getCategory2(),
        ];
    }

    protected function addPaymentDetails(array &$properties)
    {
        $payment = $this->payment;

        $properties['payment'] = [
                'id'       => $payment->getPublicId(),
                'amount'   => $payment->getAmount(),
                'currency' => $payment->getCurrency(),
                'method'   => $payment->getMethod(),
                'issuer'   => $payment->getIssuer(),
        ];

        if ($payment->hasCard() === true)
        {
            $properties['payment'] += [
                'card_iin'      => $payment->card->getIin(),
                'card_network'  => $payment->card->getNetwork(),
                'card_type'     => $payment->card->getType(),
                'card_country'  => $payment->card->getCountry(),
                'international' => $payment->isInternational(),
            ];
        }
    }

    protected function addErrorDetails(array &$properties)
    {
        $properties['error_code'] = ErrorCode::SUCCESS;

        if ($this->exception !== null)
        {
            if ($this->exception instanceof BaseException)
            {
                $properties['error_code'] = $this->exception->getCode();
            }
            else 
            {
                $properties['error_code'] = ErrorCode::SERVER_ERROR;
            }
        }
    }

    protected function removeSenstiveFields()
    {
        // currently just doing based on the input keys, can add strict validations like luhn check etc
        unset($this->customProperties['card']);
        unset($this->customProperties['card_number']);
        unset($this->customProperties['number']);
        unset($this->customProperties['notes']);
    }
}
