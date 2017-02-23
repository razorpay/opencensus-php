<?php

namespace RZP\Tests\Functional\Invoice;

trait InvoiceTestTrait
{
    protected function createDraftInvoice(array $with = [])
    {
        $this->fixtures->create(
            'invoice',
            array_merge(
                [
                    'type'         => 'invoice',
                    'status'       => 'draft',
                    'order_id'     => null,
                    'short_url'    => null,
                    'amount'       => null,
                    'sms_status'   => 'pending',
                    'email_status' => 'pending',
                ],
                $with
            )
        );
    }

    private function createIssuedInvoice(array $with = [])
    {
        return $this->fixtures->create(
            'invoice',
            array_merge(
                [
                    'status' => 'issued'
                ],
                $with
            )
        );
    }
}
