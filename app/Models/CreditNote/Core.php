<?php

namespace RZP\Models\CreditNote;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Invoice;
use RZP\Models\Invoice\Status;
use RZP\Models\Payment\Refund;
use RZP\Models\Plan\Subscription;
use RZP\Models\Payment\Processor\Processor;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\CreditNote\Invoice as creditNoteInvoice;



class Core extends Base\Core
{
    public function create(Merchant\Entity $merchant, array $input): Entity
    {
        $customer_id = $input[Entity::CUSTOMER_ID];

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customer_id, $merchant);

        $this->checkForSubscription($input, $customer);

        $creditnote = (new Entity)->build($input);

        $this->fillNecessaryFields($creditnote, $input);

        $creditnote->merchant()->associate($merchant);

        $creditnote->customer()->associate($customer);

        $this->repo->saveOrFail($creditnote);

        return $creditnote;
    }


    protected function checkForSubscription(array & $input, Customer\Entity $customer)
    {
        if (isset($input[Entity::SUBSCRIPTION_ID]) === true)
        {
            $subscription = $this->repo->subscription->findByPublicIdAndMerchant($input[Entity::SUBSCRIPTION_ID], $this->merchant);

            if ($subscription->getCustomerId() !== $customer->getId())
            {
                throw new BadRequestValidationFailureException(
                     ' Subscription customer id does not match '.$input[Entity::CUSTOMER_ID]);
            }

            $input[Entity::SUBSCRIPTION_ID] = Subscription\Entity::stripDefaultSign($input[Entity::SUBSCRIPTION_ID]);
        }
    }

    protected function fillNecessaryFields(Entity $creditNote, array $input)
    {
        $creditNote->setAmountAvailable($creditNote->getAmount());
    }

    public function apply(Entity $creditNote, Merchant\Entity $merchant, array $input): Entity
    {
        $action = $input[Entity::ACTION];

        if ($action === 'refund')
        {
            $this->validateCreditNoteAmountAvailable($creditNote, $input[Entity::INVOICES]);

            $this->validateInvoicesAndPayments( $input[Entity::INVOICES], $merchant, $creditNote);
        }
        return $creditNote;

    }

    protected function validateCreditNoteAmountAvailable(Entity $creditNote, array $input)
    {
        $totalRefundAmount = 0;

        foreach ($input as $row)
        {
            $totalRefundAmount += $row[Entity::AMOUNT];
        }

        if (($creditNote->getAmountAvailable() < $totalRefundAmount) === true)
        {
            throw new BadRequestValidationFailureException(
                $creditNote->getPublicId() . ' does not have enough amount available to refund');
        }
    }

    protected function validateInvoicesAndPayments(array $input, Merchant\Entity $merchant, Entity $creditNote)
    {
        foreach ($input as $row)
        {
            $this->validateInvoiceAndRefundAmount($row, $merchant, $creditNote);

            $this->validatePaymentsAndRefundAmount($row, $merchant, $creditNote);
        }
    }


    protected function validateInvoiceAndRefundAmount(array $row, Merchant\Entity $merchant,Entity $creditNote)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($row[Entity::INVOICE_ID], $merchant);

        $this->validateInvoiceAndEntity($invoice, $creditNote);

        if ($invoice->getCustomerId() !== $creditNote->getCustomerId())
        {
            throw new BadRequestValidationFailureException(
                $invoice->getPublicId() . ' customer does not match credit note customer');
        }

        $refundAmount = $row[Entity::AMOUNT];

        if (($invoice->getAmount() < $refundAmount) === true)
        {
            throw new BadRequestValidationFailureException(
                $invoice->getPublicId() . ' amount lesser than refund amount');
        }

        if ($invoice->getStatus() !== Status::PAID)
        {
            throw new BadRequestValidationFailureException(
                $invoice->getPublicId() . ' is not in paid state');
        }
    }

    protected function validateInvoiceAndEntity(Invoice\Entity $invoice, Entity $creditNote)
    {
        if (($creditNote->getSubscriptionId() !== null))
        {
            if ($invoice->getSubscriptionId() !== $creditNote->getSubscriptionId())
            {
                throw new BadRequestValidationFailureException(
                    Entity::SUBSCRIPTION . ' does not match with the invoice '.$invoice->getPublicId());
            }
        }
    }

    protected function validatePaymentsAndRefundAmount(array $row, Merchant\Entity $merchant, Entity $creditNote)
    {
        $refundAmount = $row[Entity::AMOUNT];

        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($row[Entity::INVOICE_ID], $merchant);

        $payments = $invoice->payments;

        $totalPayments = 0;

        foreach ($payments as $payment)
        {
            $totalPayments +=  $payment->getAmount();
        }

        if ($refundAmount > $totalPayments)
        {
            throw new BadRequestValidationFailureException(
                'Cannot refund the amount since the the refund amount exceeds total payments');
        }

        $this->selectAndRefundPayments($payments, $refundAmount, $merchant, $creditNote, $invoice);

    }

    protected function selectAndRefundPayments(
        $payments,
        int $refundAmount,
        Merchant\Entity $merchant,
        Entity $creditNote,
        Invoice\Entity $invoice)
    {
        $this->repo->transaction(
            function() use ($payments, $creditNote, $merchant, $invoice, $refundAmount)
            {
                $paymentProcessor = new Processor($merchant);

                foreach ($payments as $payment)
                {
                    $currentPaymentAmount = $payment->getAmount();

                    if (($refundAmount <= $currentPaymentAmount) === true)
                    {
                        $refund = $paymentProcessor->refundCapturedPayment($payment, array(Entity::AMOUNT => $refundAmount));

                        $this->postRefundActions($refund, $creditNote, $merchant, $invoice);

                        return;
                    }

                    else
                    {
                        $refund = $paymentProcessor->refundCapturedPayment($payment, array(Entity::AMOUNT => $currentPaymentAmount));

                        $this->postRefundActions($refund, $creditNote, $merchant, $invoice);

                        $refundAmount = $refundAmount - $currentPaymentAmount;
                    }

                }
            });
    }

    protected function postRefundActions(
        Refund\Entity $refund,
        Entity $creditNote,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice)
    {
        $input = [
            creditNoteInvoice\Entity::STATUS => creditNoteInvoice\Entity::STATUS_REFUNDED,
            creditNoteInvoice\Entity::AMOUNT => $refund->getAmount(),
        ];

        $creditNoteInvoiceCore  = (new creditNoteInvoice\Core());

        $creditNoteInvoiceCore->create($input, $refund, $creditNote, $merchant, $invoice);

        $creditNote->calculateAndSetAmountRefundedAndAvailable($refund->getAmount());

        $this->repo->saveOrFail($creditNote);
    }

}
