<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Partner\Metric;
use RZP\Exception\LogicException;
use RZP\Models\Partner\Activation;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    public function createInvoiceEntities(array $input)
    {
        return (new Core)->queueCreateInvoiceEntities($input);
    }

    public function changeStatus($id, array $input)
    {
        $invoice = $this->repo->commission_invoice->findByIdAndMerchant($id, $this->merchant);

        (new Validator)->validateInput('change_status', $input);

        return Tracer::inspan(['name' => HyperTrace::COMMISSION_INVOICE_CHANGE_STATUS_CORE], function () use ($invoice, $input) {

            return (new Core)->changeInvoiceStatus($invoice, $input);
        });
    }

    public function clearOnHoldForInvoiceBulk(array $input)
    {
        (new Validator)->validateInput('bulk_on_hold_clear', $input);

        return Tracer::inspan(['name' => HyperTrace::CLEAR_ON_HOLD_COMMISSION_INVOICE_BULK_CORE], function () use ($input) {

            return (new Core)->clearOnHoldForInvoiceBulk($input);
        });
    }

    public function fetch($id)
    {
        $params = [
            'expand' => ['line_items', 'line_items.taxes'],
        ];

        $invoice = $this->repo->commission_invoice->findByIdAndMerchant($id, $this->merchant, $params);

        return $invoice->toArrayPublic();
    }

    public function fetchBulk(array $input)
    {
        $partnerActivation = (new Activation\Core())->createOrFetchPartnerActivationForMerchant($this->merchant, false);

        if (($partnerActivation->getActivationStatus() !== Activation\Constants::ACTIVATED) and
            ($this->mode === Mode::LIVE))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_IS_NOT_ACTIVATED,
                null,
                [
                    'partner_id' => $this->merchant->getId(),
                    'reason'     => 'only active partners can fetch commission invoices'
                ]
            );
        }

        $invoices = $this->repo->commission_invoice->fetch($input, $this->merchant->getId());

        $this->trace->count(Metric::COMMISSION_INVOICE_BULK_FETCH_SUCCESS_TOTAL, $input);

        return $invoices->toArrayPublic();
    }
}
