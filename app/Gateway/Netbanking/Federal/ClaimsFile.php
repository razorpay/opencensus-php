<?php

namespace RZP\Gateway\Netbanking\Federal;

use RZP\Gateway\Base;

class ClaimsFile extends Base\RefundFile
{
    /**
     * Not generating a claims file because Federal
     * Only needs total count and total amount to be sent across
     */
    public function generate($input)
    {
        return $this->getClaimsData($input);
    }

    protected function getClaimsData(array $input)
    {
        $totalAmount = 0;
        $count = 0;

        foreach ($input['data'] as $row)
        {
            $totalAmount += $row['payment']['amount'] / 100;

            $count++;
        }

        return [
            'total_amount'   => $totalAmount,
            'count'          => $count,
            'signed_url'     => '',
            'local_file_path' => '',
        ];
    }
}
