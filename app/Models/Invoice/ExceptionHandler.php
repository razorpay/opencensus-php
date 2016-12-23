<?php

namespace RZP\Models\Invoice;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class ExceptionHandler
{
    /**
     *
     * Check if is Mysql duplicate on unique index error. If it is returns
     * Bad request response to client.
     * If is't not mysql duplicate error, it'll just let it bubble up.
     *
     * This needs to be re-factored and should use something like
     * https://github.com/felixkiss/uniquewith-validator to validate the
     * uniqueness beforehand(by querying of course) and not wait for mysql error.
     *
     */
    public static function handle(\Exception $e, Entity $invoice, array $input)
    {
        if (($e instanceof \Illuminate\Database\QueryException) and
            ($e->errorInfo[1] === 1062))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_DUPLICATE_INVOICE_RECEIPT,
                null,
                [
                    'invoice_id'    => $invoice->getId(),
                    'input'         => $input,
                ]);
        }

        throw $e;
    }
}
