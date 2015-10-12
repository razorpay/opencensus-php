<?php
namespace RZP\Mock;

class MockData
{
    public static function getData()
    {
        return array(
            'key'               => array(
                'id'            => 'rzp_test_1RqSZPwsOG12RC',
                'entity'        => 'key',
                'created_at'    => time(),
                'expired_at'    => null,
                'secret'        => 'thisissecret'
            ),
            'merchant'          => array(
                'id'                => '10000000000000',
                'name'              => 'Tester',
                'email'             => 'test@razorpay.com',
                'activated'         => 0,
                'live'              => 0,
                'pricing_plan_id'   => null,
                'created_at'        => time(),
                'updated_at'        => time(),
                'entity'            => 'merchant'
            ),
            'pricing'      => array(
                'id'    => '1lxtWJ4wGwvHPr',
                'name'  => 'mockPlan',
                'entity'=> 'pricing',
                'count' => 1,
                'rules' => array(
                    array(
                        'id'                => '1NjWJJ1c8HQ62X',
                        'plan_id'           => '1lxtWJ4wGwvHPr',
                        'plan_name'         => 'testRule',
                        'gateway'           => null,
                        'payment_method'      => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network'   => null,
                        'payment_issuer'    => null,
                        'percent_rate'      => '223',
                        'fixed_rate'        => '223',
                        'created_at'        => time(),
                        'updated_at'        => time(),
                        'expired_at'        => null
                    )
                )
            ),
            'pricing_plan_rule' => array(
                'id'                => '1NjWJJ1c8HQ62X',
                'plan_id'           => '1lxtWJ4wGwvHPr',
                'plan_name'         => 'testRule',
                'gateway'           => null,
                'payment_method'    => 'card',
                'payment_method_type' => 'debit',
                'payment_network'   => null,
                'payment_issuer'    => null,
                'percent_rate'      => '223',
                'fixed_rate'        => '223',
                'created_at'        => time(),
                'updated_at'        => time(),
                'expired_at'        => null
            ),
            'terminal'          => array(
                'id'                    => '1fsgRBDNqnCgzw',
                'merchant_id'           => '10000000000000',
                'entity'                => 'terminal',
                'gateway'               => 'testGateway',
                'gateway_merchant_id'   => 'testMID',
                'gateway_terminal_id'   => 'testTID',
                'created_at'            => time(),
                'updated_at'            => time(),
                'card'                  => 1,
                'category'              => '4567'
            ),
            'payment'       => array(
                'id'                  => 'pay-1sm42A7OxlvJCv',
                'entity'              => 'payment',
                'amount'              => '499',
                'currency'            => 'INR',
                'status'              => 'authorized',
                'amount_refunded'     => '0',
                'refund_status'       => 'none',
                'description'         => null,
                'email'               => 'shk@gmail.com',
                'contact'             => '1234567890',
                'notes'               => array(),
                'error_code'          => null,
                'error_description'   => null,
                'created_at'          => time()
            ),
            'refund'            => array(
                'id'                => 'rfnd-25mabFpVm4L2Qp',
                'entity'            => 'refund',
                'amount'            => '100',
                'currency'          => 'INR',
                'payment_id'        => 'pay-1sm42A7OxlvJCv',
                'created_at'        => time()
            ),
            'settlement'      => array(
                'id'                => 'setl-1UtOTIcRbkXHVo',
                'entity'            => 'settlement',
                'amount'            => '399',
                'status'            => 'completed'
            ),
            'transaction'      => array(
                'id'                =>  'txn-1utOTIcRbkXHVo',
                'entity'            =>  'transaction',
                'entity_id'         =>  'pay-1sm42A7OxlvJCv',
                'entity_type'       =>  'payment',
                'amount'            =>  '499',
                'currency'          =>  'INR',
                'debit'             =>  '0',
                'credit'            =>  '400',
                'fee'               =>  '99'
            ),
            'bankaccount'     => array(
                'merchant_id'       =>  "10000000000000",
                'ifsc_code'         =>  "HDFC0678911",
                'beneficiary_name'  =>  "Tester",
                'account_number'    =>  "1222222222"
            )
        );
    }
}
