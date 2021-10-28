<?php

namespace RZP\Tests\Functional\Batch;

use Hash;
use Mail;
use Cache;
use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class HitachiChargebackTest extends TestCase
{
    use BatchTestTrait;
    use HeimdallTrait;
    use WorkflowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/HitachiChargebackTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBatchUploadHitachiChargeback()
    {
        $testCases = [
            [
                "entries" => [
                    [
                        "Sr. No"                 => "random value",
                        "Card Number"            => "random value",
                        "ARN"                    => "random value",
                        "Amt (4)"                => "random value",
                        "Currency (49)"          => "random value",
                        "Billing Amt (30)"       => "random value",
                        "Billing currency (149)" => "random value",
                        "Txn Date (12)"          => "random value",
                        "Settelment Date"        => "random value",
                        "MID (42)"              => "random value",
                        "TID (41)"               => "random value",
                        "ME Name (43)"           => "random value",
                        "Auth Code (38)"         => "random value",
                        "RRN (37)"               => "random value",
                        "MCC (26)"               => "random value",
                        "CB Reference No (95)"   => "random value",
                        "CB Date"                => "random value",
                        "Doc Indicator(0262)"    => "random value",
                        "Reason Code (25)"       => "random value",
                        "Message Text (72)"      => "random value",
                        "Fulfilement TAT"        => "random value",
                        "Ageing Days"            => "random value",
                        "Type"                   => "random value",
                    ]
                ],
                "type"     => "hitachi_cbk_mastercard",
            ],
            [
                "entries" => [
                    [
                        "Sr. No"           => "random Value",
                        "Card Number"      => "random Value",
                        "ARN"              => "random Value",
                        "Chgbk Amt"        => "random Value",
                        "Currency"         => "random Value",
                        "Source Amt"       => "random Value",
                        "Source Currency"  => "random Value",
                        "Billing Amt"      => "random Value",
                        "Billing Currency" => "random Value",
                        "Txn Date"         => "random Value",
                        "Settlement Date"  => "random Value",
                        "MID"              => "random Value",
                        "TID"              => "random Value",
                        "ME Name"          => "random Value",
                        "Auth Code"        => "random Value",
                        "RRN"              => "random Value",
                        "MCC Code"         => "random Value",
                        "CB Reference No"  => "random Value",
                        "CB Date"          => "random Value",
                        "Doc Indicator"    => "random Value",
                        "Reason Code"      => "random Value",
                        "Message Text"     => "random Value",
                        "Fulfilement TAT"  => "random Value",
                        "Duplicate RRN"    => "random Value",
                        "Ageing Days"      => "random Value",
                        "Date of Issue"    => "random Value",
                        "Dispute Type"     => "random Value",
                    ]
                ],
                "type"     => "hitachi_cbk_visa",
            ],
            [
                "entries" => [
                    [
                        "Sr. No"           => "random Value",
                        "Card Number"      => "random Value",
                        "ARN"              => "random Value",
                        "Chgbk Amt"        => "random Value",
                        "Currency"         => "random Value",
                        "Source Amt"       => "random Value",
                        "Source Currency"  => "random Value",
                        "Billing Amt"      => "random Value",
                        "Billing Currency" => "random Value",
                        "Txn Date"         => "random Value",
                        "Settlement Date"  => "random Value",
                        "MID"              => "random Value",
                        "TID"              => "random Value",
                        "ME Name"          => "random Value",
                        "Auth Code"        => "random Value",
                        "RRN"              => "random Value",
                        "MCC Code"         => "random Value",
                        "CB Reference No"  => "random Value",
                        "CB Date"          => "random Value",
                        "Doc Indicator"    => "random Value",
                        "Reason Code"      => "random Value",
                        "Message Text"     => "random Value",
                        "Fulfilement TAT"  => "random Value",
                        "Ageing Days"      => "random Value",
                        "Date of Issue"    => "random Value",
                        "Dispute Type"     => "random Value",
                    ],
                    [
                        "Sr. No"           => "random Value",
                        "Card Number"      => "random Value",
                        "ARN"              => "random Value",
                        "Chgbk Amt"        => "random Value",
                        "Currency"         => "random Value",
                        "Source Amt"       => "random Value",
                        "Source Currency"  => "random Value",
                        "Billing Amt"      => "random Value",
                        "Billing Currency" => "random Value",
                        "Txn Date"         => "random Value",
                        "Settlement Date"  => "random Value",
                        "MID"              => "random Value",
                        "TID"              => "random Value",
                        "ME Name"          => "random Value",
                        "Auth Code"        => "random Value",
                        "RRN"              => "random Value",
                        "MCC Code"         => "random Value",
                        "CB Reference No"  => "random Value",
                        "CB Date"          => "random Value",
                        "Doc Indicator"    => "random Value",
                        "Reason Code"      => "random Value",
                        "Message Text"     => "random Value",
                        "Fulfilement TAT"  => "random Value",
                        "Ageing Days"      => "random Value",
                        "Date of Issue"    => "random Value",
                        "Dispute Type"     => "random Value",
                    ]
                ],
                "type"     => "hitachi_cbk_rupay",
            ],
        ];

        $this->ba->adminAuth();

        $this->addPermissionToBaAdmin('bulk_hitachi_chargeback');

        foreach ($testCases as $testCase)
        {

            $this->testData[__FUNCTION__]['request']['content']['type'] = $testCase['type'];

            $this->createAndPutExcelFileInRequest($testCase['entries'], __FUNCTION__);

            $this->startTest();
        }

    }

    public function testBatchUploadHitachiFailedChargeback()
    {
        $testCases = [
            [
                "type" => "hitachi_cbk_mastercard"
            ],
            [
                "type" => "hitachi_cbk_visa"
            ],
            [
                "type" => "hitachi_cbk_rupay"
            ]
        ];

        $this->ba->adminAuth();

        $this->addPermissionToBaAdmin('bulk_hitachi_chargeback');

        foreach ($testCases as $testCase)
        {
            $entries = [
                [
                    "wrong header" => "wrng val"
                ]
            ];

            $this->testData[__FUNCTION__]['request']['content']['type'] = $testCase['type'];

            $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

            $this->startTest();
        }

    }

}
