<?php

namespace RZP\Models\CreditNote\Invoice;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\CreditNote;
use RZP\Models\Payment\Refund;

class Core extends Base\Core
{
    public function create(
        array $input,
        Refund\Entity $refund,
        CreditNote\Entity $creditNote,
        Merchant\Entity $merchant,
        Invoice\Entity $invoice): Entity
    {
        $this->trace->info(TraceCode::CREDITNOTE_CREATE_REQUEST, $input);

        $creditNoteInvoice = (new Entity)->build($input);

        $creditNoteInvoice->merchant()->associate($merchant);

        $creditNoteInvoice->invoice()->associate($invoice);

        $creditNoteInvoice->customer()->associate($creditNote->customer);

        // based on experiment, refunds are created on Scrooge
        // however, all references need to come from virtual refund entity
        $creditNoteInvoice['refund_id'] = $refund['id'];

        $creditNoteInvoice->creditNote()->associate($creditNote);

        $this->repo->saveOrFail($creditNoteInvoice);

        return $creditNoteInvoice;
    }
}
