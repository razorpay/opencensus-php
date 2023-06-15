<?php

namespace RZP\Tests\P2p\Service\UpiAxisOlive\Device;

use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\P2p\Preferences\Constants;

use  RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\P2p\Service\UpiAxisOlive\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\P2p\Service\Base\Traits\MetricsTrait;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class PreferencesTest extends TestCase
{
    use EventsTrait;
    use MetricsTrait;
    use TransactionTrait;
    use TestsWebhookEvents;

    public function testGetGatewayPreferences()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey('customer', $response);

        $this->assertArrayHasKey('gateways', $response);

        $this->assertArrayHasKey('popular_banks', $response);

        $this->assertArraySelectiveEquals(Constants::getPopularBanksList(), $response['popular_banks']);
    }

    public function testCreateBankAccountForCustomerForPreferences()
    {
        $helper = $this->getPreferencesHelper()->setMerchantOnAuth(true);;

        $helper->withSchemaValidated();

        $customer_id = $this->fixtures->customer->getPublicId();

        $this->fixtures->enableFeatures("tpv");

        $features = $this->fixtures->merchant->getEnabledFeatures();

        $response = $helper->createBankAccountForCustomerForPreferences($customer_id, []);

        $helper = $this->getPreferencesHelper();

        $response = $helper->getGatewayPreferences($this->gateway, []);
        
        $this->assertArrayHasKey('tpv', $response);

        $this->assertArrayHasKey('is_tpv', $response);

        $this->assertArrayHasKey('restrict_bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('account_number', $response["tpv"]["bank_accounts"][0]);
    }

    public function testCreateMultipleBankAccountForCustomerForPreferences()
    {
        $helper = $this->getPreferencesHelper()->setMerchantOnAuth(true);;

        $helper->withSchemaValidated();

        $customer_id = $this->fixtures->customer->getPublicId();

        $this->fixtures->enableFeatures("tpv");

        $features = $this->fixtures->merchant->getEnabledFeatures();

        $response = $helper->createBankAccountForCustomerForPreferences($customer_id, []);

        $bank_account2 = [
            "ifsc_code" => "ICIC0001208",
            'account_number' => '04030403040305',
            'beneficiary_name'=> 'RATN0000002',
            "beneficiary_address1"  => "address 1",
            "beneficiary_address2"  => "address 2",
            "beneficiary_address3"  => "address 3",
            "beneficiary_address4"  => "address 4",
            "beneficiary_email"     => "random@email.com",
            "beneficiary_mobile"    => "9988776655",
            "beneficiary_city"      =>"Kolkata",
            "beneficiary_state"     => "WB",
            "beneficiary_country"   => "IN",
            "beneficiary_pin"      =>"123456"
        ];

        $response = $helper->createBankAccountForCustomerForPreferences($customer_id, $bank_account2);

        $helper = $this->getPreferencesHelper();

        $response = $helper->getGatewayPreferences($this->gateway, []);

        $this->assertArrayHasKey('tpv', $response);

        $this->assertArrayHasKey('is_tpv', $response);

        $this->assertArrayHasKey('restrict_bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('bank_accounts', $response["tpv"]);

        $this->assertArrayHasKey('account_number', $response["tpv"]["bank_accounts"][0]);

        $this->assertArrayHasKey('account_number', $response["tpv"]["bank_accounts"][1]);
    }

    public function testGetGatewayPreferencesWithInvalidCustomerId()
    {
        $helper = $this->getPreferencesHelper();

        $helper->withSchemaValidated();

        $this->expectException(BadRequestValidationFailureException::class);

        $content = [
            'customer_id' => $this->fixtures->customer->getPublicId()."xyz",
        ];

        $response = $helper->getGatewayPreferences($this->gateway, $content);

        $this->expectExceptionMessage($this->fixtures->customer->getPublicId()."xyz is not a valid id");
    }
}
