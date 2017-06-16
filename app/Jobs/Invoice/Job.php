<?php

namespace RZP\Jobs\Invoice;

use RZP\Jobs;

/**
 * Handles asynchronous action against particular invoice, Eg:
 * - Generate PDFs,
 * - Communications - send SMSes, emails etc.
 *
 */
class Job extends Jobs\InvoiceAction
{
}
