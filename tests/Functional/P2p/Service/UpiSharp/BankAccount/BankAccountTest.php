<?php

namespace RZP\Tests\P2p\Service\UpiSharp\BankAccount;

use RZP\Tests\P2p\Service\Base;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;

class BankAccountTest extends TestCase
{
    public function testFetchBanks()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $banks = $helper->fetchBanks();

        $this->assertCollection($banks, 3);
    }

    public function testInitiateRetrieve()
    {
        $id = 'bank_' . Base\Constants::ARZP;

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateRetrieve($id);
    }

    public function testRetrieve()
    {
        $id = 'bank_' . Base\Constants::ARZP;

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateRetrieve($id);

        $helper->withSchemaValidated();

        $helper->retrieve($request['callback']);
    }

    public function testFetchAll()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $bankAccounts = $helper->fetchAll();

        $this->assertCollection($bankAccounts, 1);
    }

    public function testFetch()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $bankAccount = $helper->fetch($bankAccountId);

        $this->assertSame($bankAccount[Entity::ID], $bankAccountId);
    }

    public function testInitiateSetUpiPin()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateSetUpiPin($bankAccountId);
    }

    public function testSetUpiPin()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateSetUpiPin($bankAccountId);

        $helper->withSchemaValidated();

        $helper->setUpiPin($request['callback']);
    }

    public function testSetUpiPinTimedout()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateSetUpiPin($bankAccountId);

        // Validating that the exception was eventually thrown
        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'GATEWAY_ERROR',
                'description'   => 'The gateway request to submit payment information timed out. ' .
                                    'Please submit your details again',
            ], $error);
        }, 504);

        $helper->setUpiPin($request['callback'], [
            'sdk' => [
                'error_code'                    => 'GATEWAY_ERROR_REQUEST_TIMEOUT',
                'error_description'             => 'HTTP is not all powerful',
                'gateway_error_code'            => 'Curl: Timedout',
            ]
        ]);

        $bankAccount = $this->fixtures->bank_account->refresh();

        $this->assertArraySubset([
            'id'                => $bankAccount->getId(),
            'sharpId'           => $bankAccount->getId(),
            'upi_pin_state'     => 'unknown',
        ], $bankAccount->getGatewayData());
    }

    public function testInitiateFetchBalance()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->initiateFetchBalance($bankAccountId);
    }

    public function testFetchBalance()
    {
        $bankAccountId = $this->fixtures->bank_account->getPublicId();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateFetchBalance($bankAccountId);

        $helper->withSchemaValidated();

        $helper->fetchBalance($request['callback'], [
            'sdk'   => [
                'creds' => [
                    [
                        'code'     => 'NPCI',
                        'ki'       => '20150822',
                        'string'   => '2.0|QNSo1fHj5iTFseh6RlfZh9u/bX5AyYiVYCTUMYXzd+g==',
                        'sub_type' => 'MPIN',
                        'type'     => 'PIN'
                    ],
                ],
            ]
        ]);
    }
}
