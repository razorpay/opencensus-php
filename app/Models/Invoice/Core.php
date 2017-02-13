<?php

namespace RZP\Models\Invoice;

use Config;
use Illuminate\Foundation\Bus\DispatchesJobs;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\LineItem;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Jobs\InvoiceAction;
use RZP\Models\FileStore;

class Core extends Base\Core
{
    const MAX_ALLOWED_PDF_GEN_ATTEMPTS = 2;

    use DispatchesJobs;

    protected $lineItemCore;
    protected $pdfGenerator;
    protected $slack;
    protected $slackTechLogsChannel;
    protected $mode;

    public function __construct()
    {
        parent::__construct();

        $this->lineItemCore = new LineItem\Core;

        $this->pdfGenerator = null;

        $this->slack = $this->app['slack'];

        $this->slackTechLogsChannel = Config::get('slack.channels.tech_logs');
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;
    }

    public function setPdfGenerator(Entity $invoice)
    {
        $this->pdfGenerator = new PdfGenerator($invoice);
    }

    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::INVOICE_CREATE_REQUEST,
            $input
        );

        $invoice = (new Generator($merchant))->generate($input);

        $this->trace->info(
            TraceCode::INVOICE_CREATED,
            $invoice->toArrayPublic()
        );

        if ($invoice->isIssued())
        {
            $this->dispatch(new InvoiceAction($this->mode, InvoiceAction::ISSUED, $invoice));
        }

        return $invoice;
    }

    public function update(Entity $invoice, array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(TraceCode::INVOICE_UPDATE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'input'          => $input,
            ]);

        $status = $invoice->getStatus();

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        //
        // Once basic fill by edit call on entity is done, Based on invoice status,
        // it calls either updateDraftInvoice|updateIssuedInvoice.
        //
        // This was done to maintain flow clean. Because if not now, there are chances
        // we want to handle different things in different case.
        //
        // This is neat base code for that.
        //

        $operation = 'edit' . studly_case($status);

        try
        {
            $invoice->edit($input, $operation);

            $updateFunction = 'update' . studly_case($status) . 'Invoice';

            $this->$updateFunction($merchant, $invoice, $input);
        }
        catch (\Exception $e)
        {
            ExceptionHandler::handleMySqlUniqueError($e, $invoice, $input);
        }

        $this->dispatch(new InvoiceAction($this->mode, InvoiceAction::UPDATED, $invoice));

        return $invoice;
    }

    public function issue(Entity $invoice, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::INVOICE_ISSUE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
            ]);

        $this->repo->transaction(
            function() use ($invoice, $merchant)
            {
                (new Generator($merchant, $invoice))->issueInvoice();

                $this->repo->saveOrFail($invoice);
            });

        $this->dispatch(new InvoiceAction($this->mode, InvoiceAction::ISSUED, $invoice));

        return $invoice;
    }

    public function delete(Entity $invoice)
    {
        $this->trace->info(
            TraceCode::INVOICE_DELETE_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
            ]);

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        return $this->repo->invoice->deleteOrFail($invoice);
    }

    public function addLineItems(
        Entity $invoice,
        array $input,
        Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::INVOICE_ADD_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'input'          => $input,
            ]);

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->repo->transaction(
            function() use ($invoice, $input, $merchant)
            {
                $this->lineItemCore->createMany($input, $merchant, $invoice);

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            });

        return $invoice;
    }

    public function updateLineItem(
        Entity $invoice,
        LineItem\Entity $lineItem,
        array $input,
        Merchant\Entity $merchant)
    {
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_UPDATE_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'line_item_id'   => $lineItem->getId(),
                'input'          => $input,
            ]);

        $this->repo->transaction(
            function() use ($invoice, $lineItem, $input, $merchant)
            {
                $this->lineItemCore->update(
                    $lineItem,
                    $input,
                    $merchant,
                    $invoice
                );

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            });

        return $invoice;
    }

    public function removeLineItem(Entity $invoice, LineItem\Entity $lineItem)
    {
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_REMOVE_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'line_item_id'   => $lineItem->getId(),
            ]
        );

        $this->repo->transaction(
            function() use ($lineItem, $invoice)
            {
                $this->lineItemCore->delete($lineItem, $invoice);

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            });

        return $invoice;
    }

    public function removeManyLineItems(Entity $invoice, Base\PublicCollection $lineItems)
    {
        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->trace->info(
            TraceCode::INVOICE_REMOVE_LINE_ITEM_REQUEST,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'line_item_ids'  => $lineItems->pluck('id')->toArray(),
            ]);

        $this->repo->transaction(
            function() use ($lineItems, $invoice)
            {
                $this->lineItemCore->deleteMany($lineItems);

                $this->recomputeInvoiceAmount($invoice);
                $this->repo->saveOrFail($invoice);
            });

        return $invoice;
    }

    public function sendNotification(Entity $invoice, $medium)
    {
        $this->trace->info(
            TraceCode::INVOICE_SEND_NOTIFICATION,
            [
                'invoice_id'     => $invoice->getId(),
                'invoice_status' => $invoice->getStatus(),
                'medium'         => $medium,
            ]);

        $invoice->getValidator()->validateSendNotificationRequest($medium);

        $func = studly_case($medium) . 'InvoiceIssuedToCustomer';

        if ($medium === NotifyMedium::EMAIL)
        {
            $pdfPath = $this->getInvoicePdf($invoice);
        }
        else
        {
            $pdfPath = null;
        }

        $response = (new Notifier($invoice, $pdfPath))->$func();

        $this->repo->saveOrFail($invoice);

        return ['success' => $response];
    }

    public function expireInvoice(Entity $invoice)
    {
        $this->trace->info(
            TraceCode::EXPIRE_INVOICE,
            [
                'invoice_id' => $invoice->getId(),
            ]);

        $invoice->getValidator()->validateOperation(__FUNCTION__);

        $this->repo->transaction(
            function () use ($invoice)
            {
                $this->repo->invoice->lockForUpdateAndReload($invoice);

                $this->validateIfInvoiceCanBeExpired($invoice);

                $invoice->setStatus(Status::EXPIRED);

                $this->repo->saveOrFail($invoice);
            });

        $this->dispatch(new InvoiceAction($this->mode, InvoiceAction::EXPIRED, $invoice));

        return $invoice;
    }

    /**
     * Called from cron.
     * Expires all invoices which are issued and past expire_by.
     *
     * @return array
     */
    public function expireInvoices()
    {
        $time = time();

        $invoices = $this->repo->invoice->getIssuedAndPastExpiredByInvoices();

        $summary = [
            'total_invoices_count' => $invoices->count(),
            'invoice_ids'          => $invoices->getIds(),
            'failed_invoice_ids'   => [],
        ];

        foreach ($invoices as $invoice)
        {
            try
            {
                $this->expireInvoice($invoice);
            }
            catch (\Exception $e)
            {
                $summary['failed_invoice_ids'][] = $invoice->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::INVOICE_EXPIRE_VIA_CRON_FAILED,
                    ['id' => $invoice->getId()]
                );
            }
        }

        $time = time() - $time;

        $summary['time_taken'] = $time . ' secs';

        $this->trace->debug(TraceCode::INVOICES_EXPIRE_CRON_SUMMARY, $summary);

        $slackMessage = 'Invoices past expire_by, marked expired via cron.';

        $this->slack->queue($slackMessage, $summary, ['channel' => $this->slackTechLogsChannel]);

        return $summary;
    }

    public function fetchStatus(Entity $invoice)
    {
        $paymentId = $invoice->getPaymentId();

        if ($invoice->hasBeenPaid() === false)
        {
            return [
                Entity::STATUS => $invoice->getStatus()
            ];
        }

        return [
            'razorpay_payment_id' => $paymentId
        ];
    }

    public function getFormattedInvoiceData($invoiceId, Merchant\Entity $merchant)
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($invoiceId, $merchant);

        $orderId = $invoice->getOrderId();

        $customer = $invoice->customer;

        $data['invoice'] = [
            'order_id'  => Order\Entity::getSignedId($orderId),
            'url'       => $invoice->getShortUrl(),
            'amount'    => $invoice->getAmount(),
        ];

        if ($customer)
        {
            $data['customer'] = $customer->toArrayPublic();
        }

        return $data;
    }

    /**
     * Pulls customer details from payment entity if
     * does not exist already or is not created during
     * invoice creation.
     *
     * @param Payment\Entity $payment
     *
     * @return Payment\Entity
     */
    public function setCustomerDetailsFromPaymentIfAbsent(Payment\Entity $payment)
    {
        $invoice = $payment->order->invoice;

        // If invoice is already associated with a customer,
        // don't do anything.
        if ($invoice->customer)
        {
            return;
        }

        // If payment has a customer associated, then associate that to invoice.
        // Otherwise simply copy the email and contact details from payment.

        $paymentCustomer = $payment->customer;

        if ($paymentCustomer !== null)
        {
            $invoice->customer()->associate($paymentCustomer);
            $invoice->setCustomerDetails($paymentCustomer);
        }
        else
        {
            $invoice->setCustomerEmail($payment->getEmail());
            $invoice->setCustomerContact($payment->getContact());
        }

        $this->repo->saveOrFail($invoice);
    }

    public function getInvoicePdf(Entity $invoice, bool $new = false)
    {
        if ($invoice->isTypeInvoice() === false)
        {
            return null;
        }

        $pdf = $invoice->pdf();

        if (($pdf !== null) and ($new === false))
        {
            $pdfFile = (new FileStore\Accessor)
                        ->id($pdf->getId())
                        ->merchantId($invoice->getMerchantId())
                        ->getFile();

            return $pdfFile;
        }

        $this->setPdfGenerator($invoice);

        return $this->generatePdfWithRetry($invoice->getId());
    }

    // -------------------- Protected methods --------------------

    protected function updateDraftInvoice(Merchant\Entity $merchant, Entity $invoice, array $input)
    {
        $this->repo->transaction(
            function() use ($merchant, $invoice, $input)
            {
                $this->generateAttributesOnUpdate($invoice, $input);

                (new Generator($merchant, $invoice))->updateDraftInvoice($input);

                $this->repo->saveOrFail($invoice);
            });
    }

    protected function updateIssuedInvoice(Merchant\Entity $merchant, Entity $invoice, array $input)
    {
        $this->repo->saveOrFail($invoice);
    }

    /**
     * Whenever invoice gets updated via add/update/delete of it's line items,
     * The invoice amount is calculated and set again.
     *
     * We don't need to set order amount here because order is created only in
     * issued state and recomputing invoice amount happens in draft state.
     *
     * @param Entity $invoice
     */
    protected function recomputeInvoiceAmount(Entity $invoice)
    {
        $totalAmount = $this->lineItemCore->getTotalAmountOfLineItems($invoice);

        $invoice->setAmount($totalAmount);
    }

    /**
     * Invoice/Entity has few generators which are dependent on extra request
     * input keys. Those need to be run again in case of put request.
     *
     * @param Entity $invoice
     * @param array  $input
     */
    protected function generateAttributesOnUpdate(Entity $invoice, array $input)
    {
        if (isset($input[Entity::EMAIL_NOTIFY]))
        {
            $invoice->generateEmailStatus($input);
        }

        if (isset($input[Entity::SMS_NOTIFY]))
        {
            $invoice->generateSmsStatus($input);
        }

        if (isset($input[Entity::DRAFT]))
        {
            $invoice->generateStatus($input);
        }
    }

    protected function validateIfInvoiceCanBeExpired(Entity $invoice)
    {
        $count = $this->repo->invoice->getNonFailedPaymentsCount($invoice);

        if ($count !== 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVOICE_EXPIRE_FAILED,
                null,
                [
                    'invoice_id' => $invoice->getId(),
                ]);
        }
    }

    protected function generatePdfWithRetry(string $id, int $attempt = 1)
    {
        if ($attempt > self::MAX_ALLOWED_PDF_GEN_ATTEMPTS)
        {
            return null;
        }

        try
        {
            return $this->pdfGenerator->generate($this->mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::INVOICE_PDF_GEN_FAILED,
                [
                    'id'       => $id,
                    'attempts' => $attempt,
                ]
            );

            $this->generatePdfWithRetry($id, ++$attempt);
        }
    }
}
