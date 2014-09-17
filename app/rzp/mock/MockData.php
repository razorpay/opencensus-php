<?php
namespace RZP\Mock;

class MockData {
    public static function getData()
    {
        return array(
            'key'               => array(
                'id'            => "rzp_test_1394bbab963387ab84de5ef8",
                'entity'        => "key",
                'created_at'    => time(),
                'expired_at'    => NULL,
                'secret'        => "thisissecret"
            ),
            'merchant'          => array(
                'id'                => "363e4efa820b0c06208ccd99",
                'name'              => "Tester",
                'email'             => "test@razorpay.com",
                'activated'         => 0,
                'live'              => 0,
                'pricing_plan_id'   => null,
                'created_at'        => time(),
                'updated_at'        => time(),
                'entity'            => "merchant"
            ),
            'pricing_plan'      => array(
                'id'    => "1394832550f5e5d96eea81fe",
                'name'  => "mockPlan",
                'entity'=> "pricing_plan",
                'count' => 1,
                'rules' => array(
                    array(
                        'id'                => "139486ac075f729bb334aa57",
                        'plan_id'           => "1394832550f5e5d96eea81fe",
                        'plan_name'         => "testRule",
                        'gateway'           => NULL,
                        'payment_mode'      => "card",
                        'payment_mode_type' => "debit",
                        'payment_network'   => NULL,
                        'payment_issuer'    => NULL,
                        'percent_rate'      => "223",
                        'fixed_rate'        => "223",
                        'created_at'        => time(),
                        'updated_at'        => time(),
                        'expired_at'        => NULL
                    )
                )
            ),
            'pricing_plan_rule' => array(
                'id'                => "139486ac075f729bb334aa57",
                'plan_id'           => "1394832550f5e5d96eea81fe",
                'plan_name'         => "testRule",
                'gateway'           => NULL,
                'payment_mode'      => "card",
                'payment_mode_type' => "debit",
                'payment_network'   => NULL,
                'payment_issuer'    => NULL,
                'percent_rate'      => "223",
                'fixed_rate'        => "223",
                'created_at'        => time(),
                'updated_at'        => time(),
                'expired_at'        => NULL
            ),
            'terminal'          => array(
                'id'                    => "14aa47c4a93d6e9b9c7ef51a",
                'merchant_id'           => "363e4efa820b0c06208ccd99",
                'gateway'               => "testGateway",
                'gateway_merchant_id'   => "testMID",
                'gateway_terminal_id'   => "testTID",
                'created_at'            => time(),
                'updated_at'            => time()
            ),
            'transaction'       => array(
                'id'                  => "txn-13946931b04cd00f45057372",
                'entity'              => "transaction",
                'amount'              => "499",
                'currency'            => "INR",
                'status'              => "authorized",
                'amount_refunded'     => "0",
                'refund_status'       => "none",
                'description'         => NULL,
                'email'               => "shk@gmail.com",
                'contact'             => "1234567890",
                'udf'                 => array(),
                'error_code'          => NULL,
                'error_description'   => NULL,
                'created_at'          => time()
            ),
            'refund'            => array(
                'id'                => "rfnd-139469414bbe64deb0d1c0c5",
                'entity'            => "refund",
                'amount'            => "100",
                'currency'          => "INR",
                'transaction_id'    => "txn-13946931b04cd00f45057372",
                'created_at'        => time()
            )
        );
    }  
}