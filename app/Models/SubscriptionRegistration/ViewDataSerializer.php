<?php

namespace RZP\Models\SubscriptionRegistration;

use Config;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\UpiMandate;

class ViewDataSerializer extends Base\Core
{

    protected $invoice;

    public function __construct(Invoice\Entity $invoice)
    {
        parent::__construct();

        $this->invoice = $invoice;
    }

    public function serializeForApi()
    {
        $subscriptionRegistration = $this->invoice->entity;

        if($subscriptionRegistration === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $order = $this->invoice->order;

        $invoiceData = $this->invoice->toArrayPublic();

        $invoiceData[Constants\Entity::SUBSCRIPTION_REGISTRATION] = $subscriptionRegistration->toArrayPublic();

        if($subscriptionRegistration->getMethod() === Method::EMANDATE)
        {
            $bankAccount = $subscriptionRegistration->entity;

            if ($bankAccount !== null)
            {
                $invoiceData
                [Constants\Entity::SUBSCRIPTION_REGISTRATION]
                [Constants\Entity::BANK_ACCOUNT] = $bankAccount->toArrayHosted();
            }

            $invoiceData
            [Constants\Entity::SUBSCRIPTION_REGISTRATION]
            [Constants\Entity::BANK_ACCOUNT]
            [BankAccount\Entity::BANK_NAME] = $order->getBank();
        }

        if($subscriptionRegistration->getMethod() === Method::UPI)
        {
            $upimandate = $order->upiMandate;

            $invoiceData
            [Constants\Entity::SUBSCRIPTION_REGISTRATION]
            [UpiMandate\Entity::FREQUENCY] = $upimandate->getFrequency();
        }

        return $invoiceData;

    }

    public function serializeForApiInternal()
    {
        $invoiceData = $this->serializeForApi();

        $order = $this->invoice->order;

        if ($this->invoice->getEntityType() === Constants\Entity::SUBSCRIPTION_REGISTRATION)
        {
            $subscriptionRegistration = $this->invoice->entity;

            if ($order->getMethod() === Method::NACH)
            {
                $paperMandate = $subscriptionRegistration->paperMandate;

                // for dashboard indicates whether form has been uploaded or not
                // if form is already uploaded uploaded id will be populated or else it will be null
                $invoiceData['is_nach_form_uploaded'] = empty($paperMandate->getUploadedFileID()) === false;
            }

            $invoiceData[Constants\Entity::SUBSCRIPTION_REGISTRATION][Entity::STATUS] = $subscriptionRegistration->getStatus();
        }

        return $invoiceData;
    }
}
