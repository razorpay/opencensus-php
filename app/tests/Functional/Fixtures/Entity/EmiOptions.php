<?php

namespace Tests\Functional\Fixtures\Entity;

class EmiOptions extends Base
{
    protected $items = array(
        array(
            'id'                => '10101010101010',
            'duration'          => '9',
            'rate'              => '1200',
            'methods'           => 'debitcard',
            'bank'              => 'HDFC',
            'min_amount'        => '500000',
        ),
        array(
            'id'                => '10101010101011',
            'duration'          => '3',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'UTIB',
            'min_amount'        => '500000',
        ),
    );

    public function createDefaultEmiOptions()
    {
        $items = $this->items;

        $emiOptions = [];
        foreach ($items as $attributes)
        {
            $emiOptions[] = $this->fixtures->create('emi_options', $attributes);
        }

        return $emiOptions;
    }
}