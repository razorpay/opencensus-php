<?php

namespace RZP\Models\CreditNote;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\CreditNote\Invoice as creditNoteInvoice;

class ViewDataSerializer extends Base\Core
{
    /**
     * @var Entity
     */
    protected $creditNote;

    public function __construct(Entity $creditNote)
    {
        parent::__construct();

        $this->creditNote = $creditNote;
    }

    public function serializeForPublic(): array
    {
        $creditNoteInvoices = $this->creditNote->creditNoteInvoices;

        $serializedForPublic = $this->creditNote->toArrayPublic();

        $serializedForPublic[Entity::INVOICES] = [];

        $invoiceSerialized = [];

        foreach ($creditNoteInvoices as $creditNoteInvoice)
        {
            $invoiceId = $creditNoteInvoice->getPublicInvoiceId();

            if (isset($invoiceSerialized[$invoiceId]) === false)
            {
                $invoiceSerialized[$invoiceId] = [];
            }

            if ($creditNoteInvoice->getStatus() === creditNoteInvoice\Entity::STATUS_REFUNDED)
            {
                $invoiceSerialized[$invoiceId][] = $creditNoteInvoice->toArrayPublic();
            }
        }

        foreach ($invoiceSerialized as $key => $row)
        {
            $serializedForPublic[Entity::INVOICES][] = [Entity::INVOICE_ID => $key, 'refunds' => $row];
        }

        return $serializedForPublic;
    }
}
