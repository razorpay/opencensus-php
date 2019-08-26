<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Device;

use Illuminate\Foundation\Testing\TestResponse;
use RZP\Models\P2p\Device;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\P2p\Service\Base\DeviceHelper;
use RZP\Tests\P2p\Service\Base\P2pRequest;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class DeviceFailureTest extends TestCase
{
    public function testMissingHandleInHeader()
    {
        $helper = $this->getDeviceHelper();

        $helper->registerRequestHandler(function(P2pRequest $request)
        {
            $request->server([
                'HTTP_X_RAZORPAY_VPA_HANDLE' => null
            ]);
        });

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'The handle field is required.'
            ], $error);
        });

        $helper->initiateVerification();
    }

    public function testInvalidHandleInHeader()
    {
        $helper = $this->getDeviceHelper();

        $helper->registerRequestHandler(function(P2pRequest $request)
        {
            $request->server([
                'HTTP_X_RAZORPAY_VPA_HANDLE' => 'invalid'
            ]);
        });

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'Invalid handle is passed in the request'
            ], $error);
        });

        $helper->initiateVerification();
    }

    public function testUnauthorizedMerchantInHeader()
    {
        $helper = $this->getDeviceHelper();

        $this->fixtures->handle->setMerchantId('10000000000011')->saveOrFail();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'Merchant is not allowed to use the handle'
            ], $error);
        }, 401);

        $helper->initiateVerification();
    }

    public function testWrongCustomerInRequest()
    {
        $helper = $this->getDeviceHelper();

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'The id provided does not exist'
            ], $error);
        });

        $helper->initiateVerification([
            'customer_id' => 'cust_10000gcustomer'
        ]);
    }

    public function testUnauthorizedHandleInHeader()
    {
        $helper = $this->getDeviceHelper();

        $helper->registerRequestHandler(function(P2pRequest $request)
        {
            $request->server([
                'HTTP_X_RAZORPAY_VPA_HANDLE' => Fixtures::RZP_AXIS,
            ]);
        });

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'Device is not registered for the given handle',
                'action'        => 'initiateVerification'
            ], $error);
        });

        $helper->initiateGetToken();
    }

    public function testUnauthorizedAuthToken()
    {
        $helper = $this->getDeviceHelper();

        $this->fixtures->device->setAuthToken('null');

        $this->withFailureResponse($helper, function($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
                'description'   => 'The api secret provided is invalid',
                'action'        => 'initiateVerification'
            ], $error);
        }, 401);

        $helper->initiateGetToken();
    }
}
