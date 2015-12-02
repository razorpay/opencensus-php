<?php

namespace Tests\Functional\Fixtures\Entity;

class Iin extends Base
{
   public function createDbEntries()
    {
        $rows = [
            [
                'iin' =>'510128',
                'category' => 'Gold',
                'network' => 'MasterCard',
                'type' => 'debit',
                'country' => 'IN',
                'issuer' => "SBI CARDS AND PAYMENT SERVICES PVT., LTD.",
            ],
        ];

        $this->addIinsToDb($rows);
    }

    public function addIinsToDb($rows)
    {
        $repo = new \Models\Card\IIN\Repository();

        foreach ($rows as $row)
        {
            $iin = new \Models\Card\IIN\Entity();
            $iin->fill($row);
            $repo->saveOrFail($iin);
        }
    }

}
