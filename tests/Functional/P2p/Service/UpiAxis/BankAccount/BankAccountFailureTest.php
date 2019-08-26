<?php

namespace RZP\Tests\P2p\Service\UpiAxis\BankAccount;

use Carbon\Carbon;
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

    public function testRetrieveNoAccountFailure()
    {
        $this->fixtures->bank_account->setGatewayData([])->saveOrFail();

        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateRetrieve('bank_' . Base\Constants::ARZP_AXIS);

        $this->mockSdkContentFunction(function(& $content)
        {
            $content['accounts'] = [];
        });

        $content = $this->handleSdkRequest($request);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'No account found, please try with different bank',
                'action'        => 'initiateRetrieve'
            ], $error);
        });

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

    public function testRetrieveSdkNetworkFailure()
    {
        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateRetrieve('bank_' . Base\Constants::ARZP_AXIS);

        $this->mockSdk()->withError('NETWORK_ERROR');

        $content = $this->handleSdkRequest($request);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'GATEWAY_ERROR',
                'description'   => 'Unable to connect to bank',
            ], $error);
        }, 502);

        $helper->retrieve($request['callback'], $content);
    }

    public function testRetrieveSdkInvalidData()
    {
        $helper = $this->getBankAccountHelper();

        $request = $helper->initiateSetUpiPin($this->fixtures->bank_account->getPublicId());

        $this->mockSdk()->withError('INVALID_DATA');

        $content = $this->handleSdkRequest($request);

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'GATEWAY_ERROR',
                'description'   => 'Action could not be completed at bank',
            ], $error);
        }, 502);

        $helper->retrieve($request['callback'], $content);
    }

    public function testSetUpiPinTokenExpiryFailure()
    {
        $this->setDeviceTokenExpiryValidation(true);

        $this->fixtures->deviceToken(self::DEVICE_1)->generateRefreshedAt()->saveOrFail();

        $this->now((clone $this->testCurrentTime)->addMinutes(9));

        $helper = $this->getBankAccountHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'Token is invalid or expired',
                'action'        => 'initiateGetToken',
            ], $error);
        }, 400);

        $helper->initiateSetUpiPin($this->fixtures->bank_account->getPublicId());
    }

    public function testFetchBalanceTokenExpiryFailure()
    {
        $this->setDeviceTokenExpiryValidation(true);

        $helper = $this->getBankAccountHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'Token is invalid or expired',
                'action'        => 'initiateGetToken',
            ], $error);
        }, 400);

        $helper->initiateFetchBalance($this->fixtures->bank_account->getPublicId());
    }
}
