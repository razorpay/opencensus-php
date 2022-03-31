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
            'merchant_payback'  => '518',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '20101010101010',
            'duration'          => '6',
            'rate'              => '1200',
            'methods'           => 'debitcard',
            'bank'              => 'HDFC',
            'min_amount'        => '300000',
            'merchant_payback'  => '518',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10101010101011',
            'duration'          => '3',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'UTIB',
            'min_amount'        => '300000',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10101010101100',
            'duration'          => '9',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'KKBK',
            'min_amount'        => '300000',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10101010101101',
            'duration'          => '9',
            'rate'              => '1400',
            'methods'           => 'debitcard',
            'bank'              => 'INDB',
            'min_amount'        => '200000',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10101010101110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'RATN',
            'min_amount'        => '300000',
            'issuer_plan_id'    => '85009',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10101010101111',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'SCBL',
            'min_amount'        => '300000',
            'issuer_plan_id'    => '850092',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10101010111110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'ICIC',
            'min_amount'        => '300000',
            'issuer_plan_id'    => '1007773209',
            'merchant_id'       => '100000Razorpay',
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
            'merchant_payback'  =>  549,
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10101111111110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'YESB',
            'min_amount'        => '250000',
            'merchant_id'       => '100000Razorpay',

        ],
        [
            'id'                => '11111111111110',
            'duration'          => '9',
            'rate'              => '1300',
            'methods'           => 'debitcard',
            'bank'              => 'YESB',
            'min_amount'        => '250000',
            'subvention'        => 'merchant',
            'merchant_payback'  => 549,
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '10111110111110',
            'duration'          => '9',
            'rate'              => '1200',
            'methods'           => 'debitcard',
            'network'           => 'AMEX',
            'min_amount'        => '300000',
            'merchant_payback'  => '518',
            'bank'              =>  null,
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '11101010111111',
            'duration'          => '6',
            'rate'              => '1200',
            'methods'           => 'debitcard',
            'network'           => 'AMEX',
            'min_amount'        => '300000',
            'merchant_payback'  => '600',
            'bank'              =>  null,
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '30101010101011',
            'duration'          => '9',
            'rate'              => '1400',
            'methods'           => 'creditcard',
            'bank'              => 'SBIN',
            'min_amount'        => '300000',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '40101010101011',
            'duration'          => '12',
            'rate'              => '1400',
            'methods'           => 'creditcard',
            'bank'              => 'SBIN',
            'min_amount'        => '300000',
            'merchant_id'       => '100000Razorpay',
        ],
        [
            'id'                => '50101010101011',
            'duration'          => '12',
            'rate'              => '1400',
            'methods'           => 'creditcard',
            'bank'              => 'CITI',
            'min_amount'        => '300000',
            'merchant_id'       => '100000Razorpay',
        ],
    ];

    protected $merchantSpecificItems = [
        [
            'id'                => '11101010101010',
            'duration'          => '6',
            'rate'              => '1250',
            'methods'           => 'debitcard',
            'bank'              => 'HDFC',
            'min_amount'        => '25000',
            'merchant_payback'  => '518',
            'merchant_id'       => '10000000000000',
        ],
        [
            'id'                => '50101010101012',
            'duration'          => '12',
            'rate'              => '1300',
            'methods'           => 'creditcard',
            'bank'              => 'CITI',
            'min_amount'        => '2000000',
            'merchant_id'       => '10000000000000',
        ],
        [
            'id'                => '30111111111110',
            'duration'          => '9',
            'rate'              => '1200',
            'methods'           => 'credit',
            'network'           => 'BAJAJ',
            'merchant_payback'  => 0,
            'min_amount'        => '300000',
            'merchant_id'       => '10000000000000',
            'bank'              => null,
        ],
        [
            'id'                 => '30111111111112',
            'duration'           => '3',
            'rate'               => '1200',
            'methods'            => 'credit',
            'cobranding_partner' => 'onecard',
            'merchant_payback'   => 0,
            'min_amount'         => '300000',
            'merchant_id'        => '10000000000000',
            'bank'               => null,
        ],
        [
            'id'                => '20101010101011',
            'duration'          => '6',
            'rate'              => '1200',
            'methods'           => 'card',
            'type'              => 'debit',
            'bank'              => 'HDFC',
            'min_amount'        => 500000,
            'merchant_payback'  => 0,
            'merchant_id'       => '100000Razorpay',
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

    public function createMerchantSpecificEmiPlans()
    {
        $items = $this->merchantSpecificItems;

        $emiPlans = [];
        foreach ($items as $attributes)
        {
            $emiPlans[] = $this->fixtures->create('emi_plan', $attributes);
        }

        return $emiPlans;
    }

    public function enableMerchantSubvention(string $planId)
    {
        $attributes = ['emi_plan_id' => $planId];

        $this->fixtures->create('emi_merchant_subvention', $attributes);
    }
}
