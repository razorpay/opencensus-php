<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class EmiPlan extends Base
{
    protected $items = [
        [
            'id'                => '10101010101010',
            'duration'          => '9',
            'rate'              => '1200',
            'methods'           => 'debitcard',
            'bank'              => 'HDFC',
            'min_amount'        => '300000',
        ],
        [
            'id'                => '10101010101000',
            'duration'          => '9',
            'subvention'        => 'merchant',
            'rate'              => '1200',
            'methods'           => 'debitcard',
            'bank'              => 'HDFC',
            'min_amount'        => '300000',
        ],
        [
            'id'                => '10101010101011',
            'duration'          => '3',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'UTIB',
            'min_amount'        => '300000',
        ],
        [
            'id'                => '10101010101100',
            'duration'          => '9',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'KKBK',
            'min_amount'        => '300000',
        ],
        [
            'id'                => '10101010101101',
            'duration'          => '9',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'INDB',
            'min_amount'        => '200000',
        ],
        [
            'id'                => '10101010101110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'RATN',
            'min_amount'        => '300000',
            'issuer_plan_id'    => '85009'
        ],
        [
            'id'                => '10101010101111',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'SCBL',
            'min_amount'        => '300000',
            'issuer_plan_id'    => '850092'
        ],
        [
            'id'                => '10101010111110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'ICIC',
            'min_amount'        => '300000',
            'issuer_plan_id'    => '1007773209'
        ],
        [
            'id'                => '10101011111110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'ICIC',
            'min_amount'        => '300000',
            'issuer_plan_id'    => '1007773209',
            'subvention'        => 'merchant',
            'merchant_payback'  => 549
        ],
        [
            'id'                => '10101111111110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'YESB',
            'min_amount'        => '250000',
        ],
    ];

    public function createDefaultEmiPlans()
    {
        $items = $this->items;

        $emiPlans = [];
        foreach ($items as $attributes)
        {
            $emiPlans[] = $this->fixtures->create('emi_plan', $attributes);
        }

        return $emiPlans;
    }
}
