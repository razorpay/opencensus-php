<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Device;

use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Tests\P2p\Service\Base\DeviceHelper;

class DeviceMerchantTest extends TestCase
{
    public function testDeviceActionRestoreResponse()
    {
        $helper = $this->getMerchantDeviceHelper();

        $content = [];

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'restore_device', $content);

        $this->assertArraySubset([
            'id'        => $this->fixtures->device->getPublicId(),
            'action'    => 'restore_device',
            'success'   => true,
        ], $response);

        $this->assertCount(1, $response['data']['vpas']['items']);
        $vpa = $response['data']['vpas']['items'][0];

        // Nothing will change as there is only one vpa
        $this->assertFalse($this->fixtures->vpa->refresh()->trashed());

        $this->assertArraySubset([
            'id'            => $this->fixtures->vpa->getPublicId(),
            'default'       => true,
            'bank_account'  => [
                'id'        => $this->fixtures->bank_account->getPublicId(),
            ],
        ], $vpa);
    }

    public function testDeviceActionRestoreSetDefault()
    {
        // This will be new default
        $vpa = $this->fixtures->createVpa([
            'default'   => false,
        ]);
        $this->assertFalse($vpa->isDefault());

        $helper = $this->getMerchantDeviceHelper();

        $content = [
            'default'   => $vpa->getPublicId(),
        ];

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'restore_device', $content);

        $this->assertCollection($response['data']['vpas'], 2, [
            [
                'id'        => $vpa->getPublicId(),
                'default'   => true,
                'bank_account'  => [
                    'id'        => $this->fixtures->bank_account->getPublicId(),
                ],
            ],
            [
                'id'        => $this->fixtures->vpa->getPublicId(),
                'default'   => false,
                'bank_account'  => [
                    'id'        => $this->fixtures->bank_account->getPublicId(),
                ],
            ],
        ]);

        $this->assertFalse($this->fixtures->vpa->refresh()->isDefault());
    }

    public function testDeviceActionRestoreDeleted()
    {
        // This will restored and inactive
        $vpa1 = $this->fixtures->createVpa([
            'default'   => true,
        ]);

        $vpa1->delete();

        // This will not be changed and stay default
        $vpa2 = $this->fixtures->createVpa([
            'default'   => true,
        ]);

        $helper = $this->getMerchantDeviceHelper();

        $content = [];

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'restore_device', $content);

        $this->assertCollection($response['data']['vpas'], 3, [
            [
                'id'            => $vpa2->getPublicId(),
                'default'       => true,
                'bank_account'  => [
                    'id'    => $this->fixtures->bank_account->getPublicId(),
                ]
            ],
            [
                'id'            => $vpa1->getPublicId(),
                'default'       => false,
                'bank_account'  => null,
            ],
            [
                'id'            => $this->fixtures->vpa->getPublicId(),
                'default'       => false,
                'bank_account'  => [
                    'id'        => $this->fixtures->bank_account->getPublicId(),
                ],
            ],
        ]);
    }

    public function testDeviceActionRestoreUndeleted()
    {
        // Will remain deleted
        $vpa1 = $this->fixtures->createVpa([
            'default'   => true,
        ]);
        $vpa1->delete();

        // Will not be default any more
        $vpa2 = $this->fixtures->createVpa([
            'default'   => true,
        ]);

        // Will stay default but will be inactive
        $vpa3 = $this->fixtures->createVpa([
            'default'   => true,
        ]);
        $vpa3->delete();

        $helper = $this->getMerchantDeviceHelper();

        $content = [
            'deleted' => [
                $vpa1->getPublicId(),
            ]
        ];

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'restore_device', $content);

        $this->assertCollection($response['data']['vpas'], 3, [
            [
                'id'                => $vpa3->getPublicId(),
                'default'           => true,
                'bank_account'      => null,
            ],
            [
                'id'                => $vpa2->getPublicId(),
                'default'           => false,
                'bank_account'      => [
                    'id'            => $this->fixtures->bank_account->getPublicId(),
                ],
            ],
            [
                'id'                => $this->fixtures->vpa->getPublicId(),
                'default'           => false,
                'bank_account'      => [
                    'id'            => $this->fixtures->bank_account->getPublicId(),
                ],
            ],
        ]);
    }

    public function testDeviceActionRestoreSetDefaultUndeleted()
    {
        // Will remain deleted
        $vpa1 = $this->fixtures->createVpa([
            'default'   => true,
        ]);
        $vpa1->delete();

        // Will stay default
        $vpa2 = $this->fixtures->createVpa([
            'default'   => true,
        ]);

        // Will not be default but inactive
        $vpa3 = $this->fixtures->createVpa([
            'default'   => true,
        ]);
        $vpa3->delete();

        $helper = $this->getMerchantDeviceHelper();

        $content = [
            'default' => $vpa2->getPublicId(),
            'deleted' => [
                $vpa1->getPublicId(),
            ]
        ];

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'restore_device', $content);

        $this->assertCollection($response['data']['vpas'], 3, [
            [
                'id'                => $vpa3->getPublicId(),
                'default'           => false,
                'bank_account'      => null,
            ],
            [
                'id'                => $vpa2->getPublicId(),
                'default'           => true,
                'bank_account'      => [
                    'id'            => $this->fixtures->bank_account->getPublicId(),
                ],
            ],
            [
                'id'                => $this->fixtures->vpa->getPublicId(),
                'default'           => false,
                'bank_account'      => [
                    'id'            => $this->fixtures->bank_account->getPublicId(),
                ],
            ],
        ]);
    }

    public function testDeviceActionReassignCustomerResponse()
    {
        $helper = $this->getMerchantDeviceHelper();

        $customer = $this->fixtures->createCustomer([]);

        $content = [
            'customer_id' => $customer->getPublicId(),
        ];

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'reassign_customer', $content);

        $this->assertArraySubset([
            'id'        => $this->fixtures->device->getPublicId(),
            'action'    => 'reassign_customer',
            'success'   => true,
        ], $response);

        $this->assertCount(1, $response['data']);
        $device = $response['data']['device'];

        $this->assertSame($this->fixtures->device->getPublicId(), $device['id']);
        $this->assertSame($customer->getPublicId(), $device['customer_id']);

        // Device will be asked to re-register
        $this->withFailureResponse($helper, function($error)
        {
            $this->assertSame('initiateVerification', $error['action']);
        }, 401);

        $helper->setMerchantOnAuth(false);

        $helper->initiateGetToken([]);

        // Nothing will change as there is only one vpa
        $this->assertSame($customer->getId(), $this->fixtures->device->refresh()->getCustomerId());
    }

    public function testDeviceActionReassignCustomerForced()
    {
        $helper = $this->getMerchantDeviceHelper();

        // Setting in context
        $customer = $this->fixtures->createCustomer([], true);

        $newDevice = $this->fixtures->createDevice([]);

        $content = [
            'customer_id' => $customer->getPublicId(),
        ];

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'reassign_customer', $content);

        $device = $response['data']['device'];

        $this->assertSame($this->fixtures->device->getPublicId(), $device['id']);
        $this->assertNotEquals($customer->getPublicId(), $device['customer_id']);

        $content['forced'] = true;

        $response = $helper->updateWithAction($this->fixtures->device->getPublicId(), 'reassign_customer', $content);

        $device = $response['data']['device'];

        $this->assertSame($this->fixtures->device->getPublicId(), $device['id']);
        $this->assertSame($customer->getPublicId(), $device['customer_id']);

        // Nothing will change as there is only one vpa
        $this->assertSame($customer->getId(), $this->fixtures->device->refresh()->getCustomerId());

        // Auth token should have been changed for previous device
        $this->assertNotEquals($newDevice->getAuthToken(), $newDevice->refresh()->getAuthToken());
        // It will be attached to shared customer
        $this->assertSame('+919999999999', $newDevice->customer->getContact());
    }

    protected function getMerchantDeviceHelper(): DeviceHelper
    {
        return $this->getDeviceHelper()->setMerchantOnAuth(true);
    }
}
