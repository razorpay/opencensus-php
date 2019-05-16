<?php

namespace RZP\Tests\P2p\Service\UpiAxis\BankAccount;

use RZP\Tests\P2p\Service\Base;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class BankAccountFailureTest extends TestCase
{
    public function testInitiateRetrieveInvalidBankId()
    {
        $helper = $this->getBankAccountHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'The id provided does not exist'
            ], $error);
        });

        $helper->initiateRetrieve('bank_' . Base\Constants::ARZP);
    }

    public function testRetrieveSdkFailure()
    {
        $this->fixtures->bank_account->setGatewayData([])->saveOrFail();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateRetrieve('bank_' . Base\Constants::ARZP_AXIS);

        $this->mockSdk()->withError('UNAUTHORIZED');

        $content = $this->handleSdkRequest($request);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'GATEWAY_ERROR',
                'description'   => 'Token is invalid or expired',
                'action'        => 'initiateGetToken',
            ], $error);
        }, 502);

        $helper->retrieve($request['callback'], $content);

    }

    public function testRetrieveBankFailure()
    {
        $this->fixtures->bank_account->setGatewayData([])->saveOrFail();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateRetrieve('bank_' . Base\Constants::ARZP_AXIS);

        $this->mockSdk()->withError('U13', 'Your bank is facing some issue, please try after sometime');

        $content = $this->handleSdkRequest($request);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'GATEWAY_ERROR',
                'description'   => 'U13. Your bank is facing some issue, please try after sometime',
            ], $error);
        }, 502);

        $helper->retrieve($request['callback'], $content);

    }

    public function testSetUpiPinFailure()
    {
        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateSetUpiPin($this->fixtures->bank_account->getPublicId());

        $this->mockSdkContentFunction(function(& $content)
        {
            $content['gatewayResponseCode'] = 'XN';
            $content['gatewayResponseMessage'] = 'You have entered incorrect card details';
        });

        $content = $this->handleSdkRequest($request);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'GATEWAY_ERROR',
                'description'   => 'XN. You have entered incorrect card details',
            ], $error);
        }, 502);

        $helper->retrieve($request['callback'], $content);

    }
}
