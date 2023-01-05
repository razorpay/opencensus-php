<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use RZP\Models\P2p;
use RZP\Models\Customer;
use RZP\Models\P2p\Client;
use RZP\Tests\P2p\Service\Base\Constants;

class P2pSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        //Eloquent::unguard();

        $this->seedRzpCustomer();

        $this->seedP2pDevices();

        $this->seedDeviceTokens();

        $this->seedBanks();

        $this->seedBankAccounts();

        $this->seedHandles();

        $this->seedVpas();

        $this->seedClients();
    }

    private function seedRzpCustomer()
    {
        Customer\Entity::whereIn(
            Customer\Entity::ID,
            [
                Constants::RZP_LOCAL_CUSTOMER_1,
                Constants::RZP_LOCAL_CUSTOMER_2
            ])->delete();

        Customer\Entity::factory()->create(
            [
                'id'                    => Constants::RZP_LOCAL_CUSTOMER_1,
                'merchant_id'           => Constants::TEST_MERCHANT,
                'contact'               => '+919988771111'
            ]);

        Customer\Entity::factory()->create(
            [
                'id'                    => Constants::RZP_LOCAL_CUSTOMER_2,
                'merchant_id'           => Constants::TEST_MERCHANT,
                'contact'               => '+919988772222',
            ]);
    }

    private function seedP2pDevices()
    {
        P2p\Device\Entity::whereIn(
            P2p\Device\Entity::ID,
            [
                Constants::CUSTOMER_1_DEVICE_1,
                Constants::CUSTOMER_2_DEVICE_1,
            ])->forceDelete();

        P2p\Device\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_1,
                'customer_id'           => Constants::RZP_LOCAL_CUSTOMER_1,
                'merchant_id'           => Constants::TEST_MERCHANT,
                'contact'               => '919988771111',
                'auth_token'            => Constants::CUSTOMER_1_DEVICE_1
            ]);

        P2p\Device\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_DEVICE_1,
                'customer_id'           => Constants::RZP_LOCAL_CUSTOMER_2,
                'merchant_id'           => Constants::TEST_MERCHANT,
                'contact'               => '919988772222',
                'auth_token'            => Constants::CUSTOMER_2_DEVICE_1
            ]);
    }

    private function seedDeviceTokens()
    {
        P2p\Device\DeviceToken\Entity::whereIn(
            P2p\Device\DeviceToken\Entity::ID,
            [
                Constants::CUSTOMER_2_DEVICE_TOKEN_1_SHARP,
                Constants::CUSTOMER_1_DEVICE_TOKEN_2_SHARP,
                Constants::CUSTOMER_2_DEVICE_TOKEN_1_SHARP,
                Constants::CUSTOMER_2_DEVICE_TOKEN_2_SHARP,
                Constants::CUSTOMER_1_DEVICE_TOKEN_1_AXIS,
                Constants::CUSTOMER_1_DEVICE_TOKEN_2_AXIS,
                Constants::CUSTOMER_2_DEVICE_TOKEN_1_AXIS,
                Constants::CUSTOMER_2_DEVICE_TOKEN_2_AXIS,
            ])->forceDelete();

        P2p\Device\DeviceToken\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_TOKEN_1_SHARP,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'status'                => P2p\Device\Status::VERIFIED,
            ]);

        P2p\Device\DeviceToken\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_TOKEN_2_SHARP,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RZP_SHARP,
                'status'                => P2p\Device\Status::PENDING,
            ]);

        P2p\Device\DeviceToken\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_DEVICE_TOKEN_1_SHARP,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'status'                => P2p\Device\Status::VERIFIED,
            ]);

        P2p\Device\DeviceToken\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_TOKEN_1_AXIS,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_AXIS,
                'status'                => P2p\Device\Status::VERIFIED,
                'gateway_data'          => [
                    'merchantCustomerId'    => Constants::CUSTOMER_1_DEVICE_TOKEN_1_AXIS,
                ],
            ]);

        P2p\Device\DeviceToken\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_TOKEN_2_AXIS,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RZP_AXIS,
                'status'                => P2p\Device\Status::PENDING,
            ]);

        P2p\Device\DeviceToken\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_DEVICE_TOKEN_1_AXIS,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_AXIS,
                'status'                => P2p\Device\Status::VERIFIED,
                'gateway_data'          => [
                    'merchantCustomerId'    => Constants::CUSTOMER_2_DEVICE_TOKEN_1_AXIS,
                ],
            ]);
    }

    private function seedBanks()
    {
        P2p\BankAccount\Bank\Entity::whereIn(
            P2p\BankAccount\Bank\Entity::ID,
            [
                Constants::ARZP,
                Constants::BRZP,
                Constants::CRZP,
                Constants::ARZP_AXIS,
                Constants::BRZP_AXIS,
                Constants::CRZP_AXIS,
            ])->delete();

        P2p\BankAccount\Bank\Entity::factory()->create(
            [
                'id'                    => Constants::ARZP,
                'handle'                => Constants::RAZOR_SHARP,
            ]);

        P2p\BankAccount\Bank\Entity::factory()->create(
            [
                'id'                    => Constants::BRZP,
                'handle'                => Constants::RAZOR_SHARP,
            ]);

        P2p\BankAccount\Bank\Entity::factory()->create(
            [
                'id'                    => Constants::CRZP,
                'handle'                => Constants::RAZOR_SHARP,
            ]);

        P2p\BankAccount\Bank\Entity::factory()->create(
            [
                'id'                    => Constants::ARZP_AXIS,
                'handle'                => Constants::RAZOR_AXIS,
            ]);

        P2p\BankAccount\Bank\Entity::factory()->create(
            [
                'id'                    => Constants::BRZP_AXIS,
                'handle'                => Constants::RAZOR_AXIS,
            ]);

        P2p\BankAccount\Bank\Entity::factory()->create(
            [
                'id'                    => Constants::CRZP_AXIS,
                'handle'                => Constants::RAZOR_AXIS,
            ]);
    }

    private function seedBankAccounts()
    {
        P2p\BankAccount\Entity::whereIn(
            P2p\BankAccount\Entity::ID,
            [
                Constants::CUSTOMER_1_BANK_ACCOUNT_1_SHARP,
                Constants::CUSTOMER_2_BANK_ACCOUNT_1_SHARP,
                Constants::CUSTOMER_1_BANK_ACCOUNT_1_AXIS,
                Constants::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
            ])->forceDelete();

        P2p\BankAccount\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_BANK_ACCOUNT_1_SHARP,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'bank_id'               => Constants::ARZP,
                'gateway_data'          => [
                    'id'                => Constants::CUSTOMER_1_BANK_ACCOUNT_1_SHARP,
                    'sharpId'           => Constants::CUSTOMER_1_BANK_ACCOUNT_1_SHARP,
                ],
            ]);

        P2p\BankAccount\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_BANK_ACCOUNT_1_SHARP,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'bank_id'               => Constants::BRZP,
                'gateway_data'          => [
                    'id'                => Constants::CUSTOMER_2_BANK_ACCOUNT_1_SHARP,
                    'sharpId'           => Constants::CUSTOMER_2_BANK_ACCOUNT_1_SHARP,
                ]
            ]);

        P2p\BankAccount\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_BANK_ACCOUNT_1_AXIS,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_AXIS,
                'bank_id'               => Constants::ARZP_AXIS,
                'gateway_data'          => [
                    'id'                => Constants::CUSTOMER_1_BANK_ACCOUNT_1_AXIS,
                    'referenceId'       => Constants::CUSTOMER_1_BANK_ACCOUNT_1_AXIS,
                ],
            ]);

        P2p\BankAccount\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_AXIS,
                'bank_id'               => Constants::BRZP_AXIS,
                'gateway_data'          => [
                    'id'                => Constants::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
                    'referenceId'       => Constants::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
                ],
            ]);
    }

    private function seedHandles()
    {
        P2p\Vpa\Handle\Entity::whereIn(
            P2p\Vpa\Handle\Entity::CODE,
            [
                Constants::RAZOR_SHARP,
                Constants::RZP_SHARP,
                Constants::NORZP_SHARP,
                Constants::RAZOR_AXIS,
                Constants::RZP_AXIS,
                Constants::NORZP_AXIS,
                Constants::RAZOR_AXIS_OLIVE,
                Constants::RZP_AXIS_OLIVE,
                Constants::NORZP_AXIS_OLIVE,
            ])->delete();

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::RAZOR_SHARP,
                'acquirer'              => Constants::P2P_UPI_SHARP,
                'bank'                  => 'ARZP',
                'active'                => true,
            ]);

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::RZP_SHARP,
                'acquirer'              => Constants::P2P_UPI_SHARP,
                'bank'                  => 'BRZP',
                'active'                => true,
            ]);

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::NORZP_SHARP,
                'acquirer'              => Constants::P2P_UPI_SHARP,
                'bank'                  => 'CRZP',
                'active'                => false,
            ]);

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::RAZOR_AXIS,
                'acquirer'              => Constants::P2P_UPI_AXIS,
                'bank'                  => 'ARZP',
                'active'                => true,
            ]);

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::RZP_AXIS,
                'acquirer'              => Constants::P2P_UPI_AXIS,
                'bank'                  => 'BRZP',
                'active'                => true,
            ]);

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::NORZP_AXIS,
                'acquirer'              => Constants::P2P_UPI_AXIS,
                'bank'                  => 'CRZP',
                'active'                => false,
            ]);
    }

    private function seedVpas()
    {
        P2p\Vpa\Entity::whereIn(
            P2p\Vpa\Entity::HANDLE,
            [
                Constants::CUSTOMER_1_VPA_1_SHARP,
                Constants::CUSTOMER_1_VPA_2_SHARP,
                Constants::CUSTOMER_2_VPA_1_SHARP,
                Constants::CUSTOMER_2_VPA_2_SHARP,
                Constants::CUSTOMER_1_VPA_1_AXIS,
                Constants::CUSTOMER_1_VPA_2_AXIS,
                Constants::CUSTOMER_2_VPA_1_AXIS,
                Constants::CUSTOMER_2_VPA_2_AXIS,
            ])->forceDelete();

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_VPA_1_SHARP,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'username'              => Constants::CUSTOMER_1_VPA_1_SHARP,
                'bank_account_id'       => Constants::CUSTOMER_1_BANK_ACCOUNT_1_SHARP,
            ]);

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_VPA_2_SHARP,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RZP_SHARP,
                'username'              => Constants::CUSTOMER_1_VPA_2_SHARP,
                'bank_account_id'       => null,
            ]);

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_VPA_1_SHARP,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'username'              => Constants::CUSTOMER_2_VPA_1_SHARP,
                'bank_account_id'       => Constants::CUSTOMER_2_BANK_ACCOUNT_1_SHARP,
            ]);

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_VPA_2_SHARP,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RZP_SHARP,
                'username'              => Constants::CUSTOMER_2_VPA_2_SHARP,
                'bank_account_id'       => null,
            ]);

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_VPA_1_AXIS,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_AXIS,
                'username'              => Constants::CUSTOMER_1_VPA_1_AXIS,
                'bank_account_id'       => Constants::CUSTOMER_1_BANK_ACCOUNT_1_AXIS,
            ]);

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_1_VPA_2_AXIS,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RZP_AXIS,
                'username'              => Constants::CUSTOMER_1_VPA_2_AXIS,
                'bank_account_id'       => null,
            ]);

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_VPA_1_AXIS,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_AXIS,
                'username'              => Constants::CUSTOMER_2_VPA_1_AXIS,
                'bank_account_id'       => Constants::CUSTOMER_2_BANK_ACCOUNT_1_AXIS,
            ]);

        P2p\Vpa\Entity::factory()->create(
            [
                'id'                    => Constants::CUSTOMER_2_VPA_2_AXIS,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RZP_AXIS,
                'username'              => Constants::CUSTOMER_2_VPA_2_AXIS,
                'bank_account_id'       => null,
            ]);
        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::RAZOR_AXIS_OLIVE,
                'acquirer'              => Constants::P2M_UPI_AXIS_OLIVE,
                'bank'                  => 'ARZP',
                'active'                => true,
            ]);

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::RZP_AXIS_OLIVE,
                'acquirer'              => Constants::P2M_UPI_AXIS_OLIVE,
                'bank'                  => 'BRZP',
                'active'                => false,
            ]);

        P2p\Vpa\Handle\Entity::factory()->create(
            [
                'code'                  => Constants::NORZP_AXIS_OLIVE,
                'acquirer'              => Constants::P2M_UPI_AXIS_OLIVE,
                'bank'                  => 'CRZP',
                'active'                => false,
            ]);

    }

    private function seedClients()
    {
        P2p\Client\Entity::whereIn(
            P2p\Client\Entity::ID,
            [
                Constants::CLIENT_1_RAZORAXIS_MER1,
                Constants::CLIENT_2_RAZORAXIS_MER2,
                Constants::CLIENT_1_RAZORSHARP_MER1,
                Constants::CLIENT_3_RAZORSHARP_MER1,
                Constants::CLIENT_4_RAZORSHARP_MER1,
            ])->forceDelete();

        P2p\Client\Entity::factory()->create([
            'id'            => Constants::CLIENT_1_RAZORAXIS_MER1,
            'handle'        => Constants::RAZOR_AXIS,
            'client_type'                    => 'merchant',
            'client_id'                      => Constants::TEST_MERCHANT,
            'gateway_data'                        => [
                'merchantId'          => env('P2P_UPI_AXIS_MERCHANT_ID'),
                'merchantChannelId'   => env('P2P_UPI_AXIS_MERCHANT_CHANNEL_ID'),
                'mcc'                 => env('P2P_UPI_AXIS_MERCHANT_CATEGORY_CODE'),
            ],
            'config'                         => [
                Client\Config::MAX_VPA          => 5,
                Client\Config::VPA_SUFFIX       => '.suf',
                Client\Config::SMS_SENDER       => 'SENDER',
                Client\Config::APP_FULL_NAME    => 'APPLICATION NAME',
                Client\Config::SMS_SIGNATURE    => 'SMS SIGNATURE',
                Client\Config::APP_COLLECT_LINK => 'AppCollectLink',
            ],
        ]);

        P2p\Client\Entity::factory()->create([
            'id'             => Constants::CLIENT_2_RAZORAXIS_MER2,
            'handle'         => Constants::RAZOR_AXIS,
            'client_type'    => 'merchant',
            'client_id'      => Constants::DEMO_MERCHANT,
            'gateway_data'        => [
                'merchantId'        => 'TEST_CRED' ,
                'merchantChannelId' => 'TEST_CHANNEL_CRED',
                'mcc'               => '1200',
            ],
            'config'                         => [
                Client\Config::MAX_VPA          => 3,
                Client\Config::VPA_SUFFIX       => '.suf2',
                Client\Config::SMS_SENDER       => 'Sender',
                Client\Config::APP_FULL_NAME    => 'P2P Application',
            ],
        ]);

        P2p\Client\Entity::factory()->create([
            'id'             => Constants::CLIENT_1_RAZORSHARP_MER1,
            'handle'         => Constants::RAZOR_SHARP,
            'client_type'    => 'merchant',
            'client_id'      => Constants::TEST_MERCHANT,
        ]);

        P2p\Client\Entity::factory()->create([
                 'id'             => Constants::CLIENT_3_RAZORSHARP_MER1,
                 'handle'         => Constants::RAZOR_AXIS_OLIVE,
                 'client_type'    => 'merchant',
                 'client_id'      => Constants::TEST_MERCHANT,
                 'gateway_data'        => [
                     'merchantId'        => 'RAZORPAYAGG' ,
                     'merchantChannelId' => 'OLIVEAPP',
                     'mcc'               => '7299',
                     "subMerchantId"     => "OLIVE",
                 ],
             ]);

        P2p\Client\Entity::factory()->create([
             'id'             => Constants::CLIENT_4_RAZORSHARP_MER1,
             'handle'         => Constants::RAZOR_AXIS_OLIVE,
             'client_type'    => 'merchant',
             'client_id'      => Constants::TEST_MERCHANT,
             'gateway_data'        => [
                 'merchantId'        => 'RAZORPAYAGG' ,
                 'merchantChannelId' => 'OLIVEAPP',
                 'mcc'               => '7299',
                 "subMerchantId"     => "OLIVE",
             ],
        ]);
    }
}
