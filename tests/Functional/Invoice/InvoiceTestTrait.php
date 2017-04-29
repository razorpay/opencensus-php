<?php

namespace RZP\Tests\Functional\Invoice;

trait InvoiceTestTrait
{
    protected function createDraftInvoice(array $with = [])
    {
        return $this->fixtures->create(
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

    /**
     * Returns expected upsert index params for ES client method bulkUpsert method.
     *
     * @param array $with
     *
     * @return array
     */
    private function getExpectedUpsertIndexParams($with = [])
    {
        $expected = $this->testData['expectedUpsertIndexParams'];

        if (array_key_exists('id', $with))
        {
            $expected['body'][0]['index']['_id'] = $with['id'];
        }

        $expected['body'][1] = array_merge($expected['body'][1], $with);

        return $expected;
    }
}
