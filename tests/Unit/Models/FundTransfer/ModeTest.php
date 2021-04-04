<?php

namespace RZP\Tests\Unit\Models\FundTransfer;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FundTransfer;
use RZP\Tests\Functional\TestCase;

class ModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->input = [
            'merchant_id' => 'merchant_id',
            'product'     => 'payout',
            'network'     => 'MasterCard',
        ];

        $this->m2pSupportedModesInput = [
            [
                'issuer'   => 'CORP',
                'cardType' => 'debit',
                'network'  => 'Visa',
                'iin'      => "",
                "expected" => [
                    [
                        "channel"  => "m2p",
                        "mode"     => "CT",
                        "priority" => 1
                    ]
                ],
            ],
            [
                'issuer'   => 'KKBK',
                'cardType' => 'debit',
                'network'  => 'MasterCard',
                'iin'      => '456',
                "expected" => [],
            ],
            [
                'issuer'   => '',
                'cardType' => 'debit',
                'network'  => 'Maestro',
                'iin'      => '',
                "expected" => [],
            ],
            [
                'issuer'   => 'YESB',
                'cardType' => 'debit',
                'network'  => 'MasterCard',
                'iin'      => '',
                "expected" => [
                    [
                        "channel"  => "m2p",
                        "mode"     => "CT",
                        "priority" => 1
                    ]
                ],
            ],
            [
                'issuer'   => 'YESB',
                'cardType' => 'debit',
                'network'  => 'MasterCard',
                'iin'      => '222844',
                "expected" => [
                    [
                        "channel"  => "m2p",
                        "mode"     => "CT",
                        "priority" => 1
                    ],
                    [
                        "channel"  => "m2p",
                        "mode"     => "CT",
                        "priority" => 2
                    ],
                ],
            ],
        ];
    }

    public function testMerchantBlacklistedByProduct()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->fixtures->create(
            'settings',
            [
                'module'      => 'm2p_transfer',
                'entity_type' => 'merchant',
                'entity_id'   => $newMerchant['id'],
                'key'         => 'settlement',
                'value'       => 'true',
            ]
        );

        $result = (new FundTransfer\Mode)->m2pMerchantBlacklisted($newMerchant, $this->input['network'], 'settlement');

        $this->assertEquals($result["blacklisted"], true);
        $this->assertEquals($result["error_code"], ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT);
    }

    public function testMerchantBlacklistedByNetwork()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $this->fixtures->create(
            'settings',
            [
                'module'      => 'm2p_transfer',
                'entity_type' => 'merchant',
                'entity_id'   => $newMerchant['id'],
                'key'         => 'MC',
                'value'       => 'true',
            ]
        );


        $result = (new FundTransfer\Mode)->m2pMerchantBlacklisted($newMerchant, $this->input['network'], 'settlement');
        $this->assertEquals($result["blacklisted"], true);
        $this->assertEquals($result["error_code"], ErrorCode::BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_BY_NETWORK);
    }

    public function testMerchantNotBlacklisted()
    {
        $newMerchant = $this->fixtures->create('merchant');

        $result = (new FundTransfer\Mode)->m2pMerchantBlacklisted($newMerchant, $this->input['network'], 'payout');
        $this->assertEquals($result["blacklisted"], false);
        $this->assertEquals($result["error_code"], "");
    }

    // Following test depends on the configs added in M2PConfigs.php.
    // Adding/Removing any config from there may affect following test case.
    // Please change the test case dummy data defined above in setUp function too if the test fails.
    public function testGetM2PSupportedChannelModeConfig()
    {
        $newMerchant = $this->fixtures->create('merchant');
        $this->fixtures->create(
            'settings',
            [
                'module'      => 'm2p_transfer',
                'entity_type' => 'merchant',
                'entity_id'   => $newMerchant['id'],
                'key'         => 'payout',
                'value'       => 'true',
            ]
        );

        foreach($this->m2pSupportedModesInput as $input) {
            $result = (new FundTransfer\Mode)->getM2PSupportedChannelModeConfig($input["issuer"], $input["network"], $input["cardType"], $input["iin"]);

            $this->assertEquals($input["expected"], $result);
        }
    }
}
