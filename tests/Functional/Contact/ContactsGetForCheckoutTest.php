<?php

namespace Functional\Contact;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class ContactsGetForCheckoutTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ContactsGetForCheckoutTestData.php';

        parent::setUp();
    }

    public function testGetContactDetailsForCheckout(): void
    {
        $contact = $this->fixtures->create(
            'contact',
            [
                'id' => '1000000contact',
                'email' => 'test@test5.com',
                'contact' => '8888888888',
                'name' => 'eum'
            ]
        );

        $this->ba->privateAuth();

        $this->createFundAccount($contact->getPublicId());

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }


    protected function createFundAccount($contactId)
    {
        $testdata = [
            'request'  => [
                'url'     => '/fund_accounts',
                'method'  => 'post',
                'content' => [
                    'account_type' => 'bank_account',
                    'contact_id'   => $contactId,
                    'bank_account'      => [
                        'name'           => 'test',
                        'ifsc'           => 'SBIN0007105',
                        'account_number' => '111000',
                    ],
                ],
            ],
            'response' => [
                'content' => [
                ],
                'status_code' => 201
            ],
        ];

        return $this->runRequestResponseFlow($testdata);
    }
}
