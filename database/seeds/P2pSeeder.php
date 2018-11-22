<?php

use Illuminate\Database\Seeder;

use RZP\Models\P2p;
use RZP\Models\Customer;
use RZP\Tests\P2p\Service\Base\Constants;

class P2pSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        $this->seedRzpCustomer();

        $this->seedP2pDevices();

        $this->seedDeviceTokens();

        $this->seedBanks();

        $this->seedBankAccounts();

        $this->seedHandles();

        $this->seedVpas();
    }

    private function seedRzpCustomer()
    {
        Customer\Entity::whereIn(
            Customer\Entity::ID,
            [
                Constants::RZP_LOCAL_CUSTOMER_1,
                Constants::RZP_LOCAL_CUSTOMER_2
            ])->delete();

        factory(Customer\Entity::class)->create(
            [
                'id'                    => Constants::RZP_LOCAL_CUSTOMER_1,
                'merchant_id'           => Constants::TEST_MERCHANT,
                'contact'               => '+919988771111'
            ]);

        factory(Customer\Entity::class)->create(
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
            ])->delete();

        factory(P2p\Device\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_1,
                'customer_id'           => Constants::RZP_LOCAL_CUSTOMER_1,
                'merchant_id'           => Constants::TEST_MERCHANT,
                'contact'               => '+919988771111',
                'auth_token'            => Constants::CUSTOMER_1_DEVICE_1
            ]);

        factory(P2p\Device\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_2_DEVICE_1,
                'customer_id'           => Constants::RZP_LOCAL_CUSTOMER_2,
                'merchant_id'           => Constants::TEST_MERCHANT,
                'contact'               => '+919988772222',
                'auth_token'            => Constants::CUSTOMER_1_DEVICE_1
            ]);
    }

    private function seedDeviceTokens()
    {
        P2p\Device\DeviceToken\Entity::whereIn(
            P2p\Device\DeviceToken\Entity::ID,
            [
                Constants::CUSTOMER_1_DEVICE_TOKEN_1,
                Constants::CUSTOMER_1_DEVICE_TOKEN_2,
                Constants::CUSTOMER_2_DEVICE_TOKEN_1,
                Constants::CUSTOMER_2_DEVICE_TOKEN_2,
            ])->delete();

        factory(P2p\Device\DeviceToken\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_TOKEN_1,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'status'                => P2p\Device\Status::VERIFIED,
            ]);

        factory(P2p\Device\DeviceToken\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_1_DEVICE_TOKEN_2,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RZP_SHARP,
                'status'                => P2p\Device\Status::PENDING,
            ]);

        factory(P2p\Device\DeviceToken\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_2_DEVICE_TOKEN_1,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'status'                => P2p\Device\Status::VERIFIED,
            ]);
    }

    private function seedBanks()
    {
        P2p\BankAccount\Bank\Entity::whereIn(
            P2p\BankAccount\Bank\Entity::IFSC,
            [
                Constants::ARZP,
                Constants::BRZP,
                Constants::CRZP,
            ])->delete();

        factory(P2p\BankAccount\Bank\Entity::class)->create(
            [
                'ifsc'                    => Constants::ARZP,
            ]);

        factory(P2p\BankAccount\Bank\Entity::class)->create(
            [
                'ifsc'                    => Constants::BRZP,
            ]);

        factory(P2p\BankAccount\Bank\Entity::class)->create(
            [
                'ifsc'                    => Constants::CRZP,
            ]);
    }

    private function seedBankAccounts()
    {
        P2p\BankAccount\Entity::whereIn(
            P2p\BankAccount\Entity::ID,
            [
                Constants::CUSTOMER_1_BANK_ACCOUNT_1,
                Constants::CUSTOMER_2_BANK_ACCOUNT_1,
            ])->delete();

        factory(P2p\BankAccount\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_1_BANK_ACCOUNT_1,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'bank'                  => Constants::ARZP
            ]);

        factory(P2p\BankAccount\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_2_BANK_ACCOUNT_1,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'bank'                  => Constants::BRZP
            ]);
    }

    private function seedHandles()
    {
        P2p\Vpa\Handle\Entity::whereIn(
            P2p\Vpa\Handle\Entity::HANDLE,
            [
                Constants::RAZOR_SHARP,
                Constants::RZP_SHARP,
                Constants::NORZP_SHARP,
            ])->delete();

        factory(P2p\Vpa\Handle\Entity::class)->create(
            [
                'handle'                => Constants::RAZOR_SHARP,
                'acquirer'              => Constants::P2P_UPI_SHARP,
                'active'                => true,
            ]);

        factory(P2p\Vpa\Handle\Entity::class)->create(
            [
                'handle'                => Constants::RZP_SHARP,
                'acquirer'              => Constants::P2P_UPI_SHARP,
                'active'                => true,
            ]);

        factory(P2p\Vpa\Handle\Entity::class)->create(
            [
                'handle'                => Constants::NORZP_SHARP,
                'acquirer'              => Constants::P2P_UPI_SHARP,
                'active'                => false,
            ]);
    }

    private function seedVpas()
    {
        P2p\Vpa\Entity::whereIn(
            P2p\Vpa\Entity::HANDLE,
            [
                Constants::CUSTOMER_1_VPA_1,
                Constants::CUSTOMER_1_VPA_2,
                Constants::CUSTOMER_2_VPA_1,
                Constants::CUSTOMER_2_VPA_2,
            ])->delete();

        factory(P2p\Vpa\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_1_VPA_1,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'username'              => Constants::CUSTOMER_1_VPA_1,
                'bank_account_id'       => Constants::CUSTOMER_1_BANK_ACCOUNT_1,
            ]);

        factory(P2p\Vpa\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_1_VPA_2,
                'device_id'             => Constants::CUSTOMER_1_DEVICE_1,
                'handle'                => Constants::RZP_SHARP,
                'username'              => Constants::CUSTOMER_1_VPA_2,
                'bank_account_id'       => null,
            ]);

        factory(P2p\Vpa\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_2_VPA_1,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RAZOR_SHARP,
                'username'              => Constants::CUSTOMER_2_VPA_1,
                'bank_account_id'       => Constants::CUSTOMER_2_BANK_ACCOUNT_1,
            ]);

        factory(P2p\Vpa\Entity::class)->create(
            [
                'id'                    => Constants::CUSTOMER_2_VPA_2,
                'device_id'             => Constants::CUSTOMER_2_DEVICE_1,
                'handle'                => Constants::RZP_SHARP,
                'username'              => Constants::CUSTOMER_2_VPA_2,
                'bank_account_id'       => null,
            ]);
    }
}
