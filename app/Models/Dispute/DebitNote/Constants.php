<?php

namespace RZP\Models\Dispute\DebitNote;

class Constants
{
    const BATCH_MERCHANT_ID     = 'merchant_id';
    const BATCH_PAYMENT_IDS     = 'payment_ids';
    const BATCH_SKIP_VALIDATION = 'skip_validation';

    const PAYMENT_IDS     = 'payment_ids';
    const SKIP_VALIDATION = 'skip_validation';
    const TICKET_ID       = 'ticket_id';

    const DEBIT_NOTE_PDF_PATH                  = '/tmp/debit_note_%s.pdf';
    const DEBIT_NOTE_SERIAL_NUMBER_DATE_FORMAT = 'Y/M/d';
    const DEBIT_NOTE_SERIAL_NUMBER_FORMAT      = 'RZP/%s/%05d (%s)';
    const DEBIT_NOTE_SERIAL_NUMBER_KEY         = 'debit_note_serial_number_%s';

}