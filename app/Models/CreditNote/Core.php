<?php

namespace RZP\Models\CreditNote;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
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
        (new Validator)->validateInput('pre_create', $input);

        $customerId = array_pull($input, Entity::CUSTOMER_ID, null);

        $this->checkAndFillForSubscription($input);

        $creditnote = (new Entity)->build($input);

        $creditnote->merchant()->associate($merchant);

        if (empty($customerId) === false)
        {
            $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $merchant);

            $creditnote->customer()->associate($customer);
        }

        $this->repo->saveOrFail($creditnote);

        return $creditnote;
    }

    protected function checkAndFillForSubscription(array & $input)
    {
        if (isset($input[Entity::SUBSCRIPTION_ID]) === true)
        {
            $input[Entity::SUBSCRIPTION_ID] = Subscription\Entity::stripDefaultSign($input[Entity::SUBSCRIPTION_ID]);
        }
    }

    public function apply(Entity $creditNote, Merchant\Entity $merchant, array $input): Entity
    {
        $action = $input[Entity::ACTION];

        if ($action === 'refund')
        {
            $this->validateCreditNoteAmountAvailable($creditNote, $input[Entity::INVOICES]);

            $this->validateInvoicesAndPaymentsAndRefund($input[Entity::INVOICES], $merchant, $creditNote);
        }

        return $creditNote;
    }

    protected function validateCreditNoteAmountAvailable(Entity $creditNote, array $invoiceInputs)
    {
        $totalRefundAmount = 0;

        foreach ($invoiceInputs as $invoiceInput)
        {
            $totalRefundAmount += $invoiceInput[Entity::AMOUNT];
        }

        if (($creditNote->getAmountAvailable() < $totalRefundAmount) === true)
        {
            throw new BadRequestValidationFailureException(
                $creditNote->getPublicId() . ' does not have enough amount available to refund');
        }
    }

    protected function validateInvoicesAndPaymentsAndRefund(array $invoiceInputs,
                                                            Merchant\Entity $merchant,
                                                            Entity $creditNote)
    {
        foreach ($invoiceInputs as $invoiceInput)
        {
            $this->validateInvoiceAndRefundAmount($invoiceInput, $merchant, $creditNote);

            $this->doPaymentRefund($invoiceInput, $merchant, $creditNote);
        }
    }

    protected function validateInvoiceAndRefundAmount(array $invoiceInput,
                                                      Merchant\Entity $merchant,
                                                      Entity $creditNote)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceInput[Entity::INVOICE_ID], $merchant);

        $this->validateInvoiceAndEntity($invoice, $creditNote);

        if ($invoice->getCustomerId() !== $creditNote->getCustomerId())
        {
            throw new BadRequestValidationFailureException(
                $invoice->getPublicId() . ' customer does not match credit note customer');
        }

        if ($invoice->getStatus() !== Status::PAID)
        {
            throw new BadRequestValidationFailureException(
                $invoice->getPublicId() . ' is not in paid state');
        }

        $refundAmount = $invoiceInput[Entity::AMOUNT];

        if (($refundAmount > $invoice->getAmountPaid()) === true)
        {
            throw new BadRequestValidationFailureException(
                'Cannot refund the amount since the the refund amount exceeds total payments');
        }

        if ($invoice->getCurrency() !== $creditNote->getCurrency())
        {
            throw new BadRequestValidationFailureException(
                $invoice->getPublicId() . ' and credit note currency does not match');
        }
    }

    protected function validateInvoiceAndEntity(Invoice\Entity $invoice, Entity $creditNote)
    {
        if ($creditNote->getSubscriptionId() !== null)
        {
            if ($invoice->getSubscriptionId() !== $creditNote->getSubscriptionId())
            {
                throw new BadRequestValidationFailureException(
                    Entity::SUBSCRIPTION . ' does not match with the invoice ' . $invoice->getPublicId());
            }
        }
    }

    protected function doPaymentRefund(array $invoiceInput, Merchant\Entity $merchant, Entity $creditNote)
    {
        $refundAmount = $invoiceInput[Entity::AMOUNT];

        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceInput[Entity::INVOICE_ID], $merchant);

        $payments = $invoice->payments()
                            ->where(Payment\Entity::STATUS, '=', Payment\Status::CAPTURED)
                            ->get();

        if ($payments->count() === 0)
        {
            throw new BadRequestValidationFailureException(
                $invoice->getPublicId() . ' does not have any captured payments');
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
            function() use ($payments, $creditNote, $merchant, $invoice, $refundAmount) {
                $paymentProcessor = new Processor($merchant);

                foreach ($payments as $payment)
                {
                    $currentPaymentAmount = $payment->getAmount();

                    if ($refundAmount <= $currentPaymentAmount)
                    {
                        // based on experiment, refund request will be routed to Scrooge
                        $refund = $paymentProcessor->refundCapturedPayment($payment, array(Entity::AMOUNT => $refundAmount));

                        $this->postRefundActions($refund, $creditNote, $merchant, $invoice);

                        return;
                    }
                    else
                    {
                        // based on experiment, refund request will be routed to Scrooge
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

        $creditNoteInvoiceCore = (new creditNoteInvoice\Core());

        $creditNoteInvoiceCore->create($input, $refund, $creditNote, $merchant, $invoice);

        $this->repo->transaction(
            function () use ($creditNote, $refund)
            {
                $this->repo->creditnote->lockForUpdateAndReload($creditNote);

                $creditNote->calculateAndSetAmountRefundedAndAvailable($refund->getAmount());

                $creditNote->setAppropriateStatus();

                $this->repo->saveOrFail($creditNote);
            });
    }
}
