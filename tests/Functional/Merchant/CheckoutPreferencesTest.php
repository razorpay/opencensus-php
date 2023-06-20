<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use App;
use DOMDocument;
use Illuminate\Http\Response;
use Mail;
use Event;
use Redis;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\TrustedBadge\Entity as TrustedBadge;
use RZP\Services\CheckoutService;
use RZP\Services\Mock;
use RZP\Models\Base\EsDao;
use RZP\Services\UfhService;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\Merchant\Account\AccountTest;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Jobs\FundAccountValidation;
use Illuminate\Cache\Events\CacheHit;
use RZP\Models\BankAccount\Repository;
use RZP\Models\FundAccount\Validation;
use RZP\Mail\Merchant as MerchantMail;
use Illuminate\Cache\Events\KeyWritten;
use RZP\Models\Payment\Processor\Wallet;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Database\Eloquent\Factory;
use Rzp\Credcase\Migrate\V1\RotateApiKeyRequest;
use Rzp\Credcase\Migrate\V1\MigrateApiKeyRequest;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

use RZP\Models\Key;
use RZP\Jobs\EsSync;
use RZP\Models\Admin;
use RZP\Services\Dcs;
use RZP\Models\Pricing;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Card\Network;
use RZP\Models\BankingAccount;
use RZP\Services\DiagClient;
use RZP\Services\HubspotClient;
use RZP\Services\RazorXClient;
use RZP\Models\Feature\Constants;
use RZP\Mail\User\MappedToAccount;
use RZP\Models\Settlement\Channel;
use RZP\Services\SalesForceClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\User\Core as UserCore;
use Illuminate\Support\Facades\Queue;
use RZP\Exception\BadRequestException;
use RZP\Mail\Merchant\EsEnabledNotify;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\User\Entity as UserEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Entity as Balance;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Mail\User\PasswordReset as PasswordResetMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\Merchant\Methods\Repository as MethodRepo;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Mail\Merchant\AccountChange as BankAccountChangeMail;
use RZP\Mail\InstrumentRequest\StatusNotify as StatusNotifyMail;

use function Clue\StreamFilter\fun;
use function foo\func;

class CheckoutPreferencesTest extends TestCase
{
    use PaymentTrait;
    use CreatesInvoice;
    use DbEntityFetchTrait;
    use MocksRedisTrait;
    use MocksRazorx;

    const DEFAULT_MERCHANT_ID     = '10000000000000';
    const GLOBAL_CUSTOMER_ID      = '10000gcustomer';
    const LOCAL_CUSTOMER_ID       = '100000customer';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $this->fixtures->create('org:hdfc_org');

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();
    }

    public function testSetBanks()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpiOtmFeatureFlag()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['upi_otm']);

        $response = $this->getPreferences();

        $this->assertArrayHasKey('upi_otm', $response['methods']);
        $this->assertSame(true, $response['methods']['upi_otm']);
        $this->assertArrayHasKey('upi_otm', $response['features']);
        $this->assertSame(true, $response['features']['upi_otm']);
    }

    public function testOneClickCheckoutStatus()
    {
        $this->fixtures->merchant->addFeatures(['one_cc_merchant_dashboard']);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetOneClickCheckoutStatus()
    {
        $this->fixtures->merchant->addFeatures(['one_cc_merchant_dashboard']);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $this->fixtures->create('merchant_checkout_detail', [
            'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            'status_1cc'        => 'live',
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOneClickCheckoutStatusFeatureNotPresent()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferences(): void
    {
        $this->ba->publicAuth();

        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'Test Name',
            'billing_label' => 'Test Brand Name',
        ]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingDisabled()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->disableNetbanking('10000000000000');

        $content = $this->startTest();

        $count = count($content['methods']['netbanking']);
        $this->assertEquals(0, $count);
    }

    public function testGetCheckoutPreferencesAmexRecurring()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->addFeatures(Constants::CHARGE_AT_WILL);
        $this->fixtures->merchant->enableMethod('10000000000000', 'card');
        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'amex',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'nodal account axis_genius',
            'gateway_terminal_password' => 'razorpay_password',
            'type'                      => [
                'recurring_3ds'  => '1',
            ],        );
        $this->fixtures->on('live')->create('terminal', $attributes);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithPartnerLogo()
    {
        $this->ba->publicLiveAuth();

        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->edit('10000000000000', ['partnership_url' => 'https://cdn.razorpay.com/logos/lalalala.png']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForMerchantDisabledBanks()
    {
        $this->testSetBanks();

        $this->ba->publicAuth();

        $content = $this->startTest();

        $banks = $content['methods']['netbanking'];

        $this->assertCount(2, $banks);
    }

    public function testGetCheckoutPreferencesForTpvEnabledMerchant()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableTPV();

        $content = $this->startTest();

        $banks = $content['methods']['netbanking'];

        $this->assertCount(48, $banks);

        $this->fixtures->merchant->disableTPV();
    }

    public function testGetCheckoutPreferencesForMagicEnabledMerchant()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['magic']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForSaveVpaEnabledMerchant()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['save_vpa']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForMagicDisabledMerchant()
    {
        $this->markTestSkipped();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithAllCardGeatewayDowntime()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'ALL',
            'issuer'  => 'ALL',
            'network' => 'VISA']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithDebitCardDisabled()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'ALL',
            'issuer'  => 'ALL',
            'network' => 'VISA']);

        $this->fixtures->merchant->disableDebitCard();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithCreditCardDisabled()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'ALL',
            'issuer'  => 'ALL',
            'network' => 'VISA']);

        $this->fixtures->merchant->disableCreditCard();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithCardDowntimeWithIssuerOrNetworkUnknown()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'first_data',
            'issuer'  => 'UNKNOWN',
            'network' => 'UNKNOWN']);

        $content = $this->startTest();

        $this->assertArrayNotHasKey('downtime', $content);
    }

    public function testGetCheckoutPreferencesWithCardDowntimeWithSpecificGatewayDown()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'hdfc',
            'issuer'  => 'ALL',
            'network' => 'ALL']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithCardDowntimeWithGatewayExclusiveNetworkDown()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:card', [
            'gateway' => 'hdfc',
            'issuer'  => 'ALL',
            'network' => 'DICL']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingDowntimeWithAllGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'ALL',
            'issuer'  => 'HDFC',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingDowntimeWithSharedNetbankingGateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALL',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNetbankingWithIssuerExclusiveTogateway()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway' => 'billdesk',
            'issuer'  => 'ALLA',]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithDirectNetbankingDowntime()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:netbanking', [
            'gateway'     => 'netbanking_hdfc',
            'issuer'      => 'ALL',
        ]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithWalletDowntime()
    {
        $this->ba->publicAuth();

        $dt = Carbon::createFromTimestamp(1517077800, Timezone::IST);

        Carbon::setTestNow($dt);

        $this->fixtures->create('gateway_downtime:wallet', [
            'gateway' => 'wallet_olamoney',
            'issuer'  => 'olamoney']);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNonOrderRelatedOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $offer = $this->fixtures->create('offer:wallet', [
            'checkout_display' => true,
            'display_text'     => 'Some display text',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
            'type'             => 'already_discounted',
            'merchant_id'      => '100000Razorpay',
        ]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithSharedMerchantOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $offer = $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '100000Razorpay',
            'checkout_display' => true,
            'display_text'     => 'Merchant specific offer',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
            'type'             => 'already_discounted'
        ]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithFreechargeOfferOnMerchantWithDirectFreechargeTerminal()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('terminal:direct_freecharge_terminal');

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $offer = $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '100000Razorpay',
            'checkout_display' => true,
            'display_text'     => 'Shared olamoney offer',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
            'type'             => 'already_discounted'
        ]);

        //
        // Tests that the freecharge offer is not shown as the merchant has a
        // direct freecharge terminal.
        //
        $this->fixtures->create('offer:wallet', [
            'merchant_id'      => '100000Razorpay',
            'issuer'           => 'freecharge',
            'checkout_display' => true,
            'display_text'     => 'Shared freecharge offer',
            'terms'            => 'Some terms',
            'starts_at'        => $startsAt,
        ]);

        $content = $this->startTest();

        $this->assertCount(1, $content['offers']);
    }

    public function testGetCheckoutPreferencesWithMultipleOrderOffers()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForUpi()
    {
        $this->fixtures->merchant->enableUpi('10000000000000');

        $response = $this->getPreferences();

        $this->assertEquals($response['methods']['upi'],true);

        $this->assertArrayHasKey('collect', $response['methods']['upi_type']);

        $this->assertArrayHasKey('intent', $response['methods']['upi_type']);
    }

    public function testGetCheckoutPreferencesForGpay()
    {
        $this->fixtures->merchant->addFeatures(Feature\Constants::GPAY);

        $response = $this->getPreferences();

        $this->assertEquals(true, $response['methods']['gpay']);
    }

    public function testGetCheckoutPreferencesForCardlessEmiTestMode()
    {
        $this->fixtures->merchant->enableCardlessEmi('10000000000000');

        $response = $this->getPreferences();

        $this->assertEquals($response['methods']['cardless_emi']['earlysalary'],true);

        $this->assertEquals($response['methods']['cardless_emi']['zestmoney'],true);

        $this->assertEquals($response['methods']['cardless_emi']['hdfc'],true);

        $this->assertEquals($response['methods']['cardless_emi']['kkbk'],true);

        $this->assertEquals($response['methods']['cardless_emi']['fdrl'],true);

        $this->assertEquals($response['methods']['cardless_emi']['idfb'],true);

        $this->assertEquals($response['methods']['cardless_emi']['icic'],true);

        $this->assertEquals($response['methods']['cardless_emi']['hcin'],true);

        $this->assertEquals($response['methods']['cardless_emi']['krbe'],true);

        $this->assertEquals($response['methods']['cardless_emi']['cshe'],true);

        $this->assertEquals($response['methods']['cardless_emi']['tvsc'],true);
    }

    public function testGetCheckoutPreferencesForPaylaterTestMode()
    {
        $this->fixtures->merchant->enablePayLater('10000000000000');

        $response = $this->getPreferences();

        $this->assertEquals($response['methods']['paylater']['epaylater'],true);

        $this->assertEquals($response['methods']['paylater']['getsimpl'],true);

        $this->assertEquals($response['methods']['paylater']['icic'],true);

        $this->assertEquals($response['methods']['paylater']['hdfc'],true);
    }

    public function testGetCheckoutPreferencesForDisabledUpi()
    {
        $this->fixtures->merchant->disableUpi('10000000000000');

        $response = $this->getPreferences();

        $this->assertEquals($response['methods']['upi'],false);

        $this->assertEquals($response['methods']['upi_type']['collect'], 0);

        $this->assertEquals($response['methods']['upi_type']['intent'], 0);
    }

    public function testGetCheckoutPreferencesForUpiIntent()
    {
        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->disableUpiCollect('10000000000000');

        $response = $this->getPreferences();

        $this->assertEquals($response['methods']['upi'],true);

        $this->assertEquals(false, $response['methods']['upi_type']['collect']);

        $this->assertEquals(true, $response['methods']['upi_type']['intent']);
    }

    public function testGetCheckoutPreferencesForUpiCollect()
    {
        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->disableUpiIntent('10000000000000');

        $response = $this->getPreferences();

        $this->assertEquals($response['methods']['upi'],true);

        $this->assertEquals(true, $response['methods']['upi_type']['collect']);

        $this->assertEquals(false, $response['methods']['upi_type']['intent']);
    }
    public function testGetCheckoutPreferencesForCredConsent()
    {
        $this->fixtures->merchant->addFeatures(Constants::CRED_MERCHANT_CONSENT);
        $response = $this->getPreferences();
        $this->assertEquals($response['features']['cred_merchant_consent'],true);
    }

    public function testGetCheckoutPreferencesWithForcedEmiSubventionOffer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [Merchant\Methods\EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'              => 'HDFC',
            'payment_network'     => null,
            'payment_method_type' => 'credit'
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $response = $this->getPreferences($order->getPublicId());

        // Only one expected, since HDFC is forced
        $this->assertEquals(1, count($response['methods']['emi_options']));
        $this->assertArrayHasKey('HDFC', $response['methods']['emi_options']);

    }

    public function testGetCheckoutPreferencesWithForcedEmiSubventionOfferWithMerchantSpecificEmi()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->edit(
            'methods',
            '10000000000000',
            [
                'emi' => [Merchant\Methods\EmiType::CREDIT => '1'],
            ]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'emi_durations'   => [6],
            'payment_network' => null,
            'payment_method_type' => 'credit',
            'min_amount' => 100000
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, [
            'force_offer' => true,
        ]);

        $response = $this->getPreferences($order->getPublicId());

        // Only one expected, since HDFC is forced
        $this->assertEquals(1, count($response['methods']['emi_options']));
        $this->assertArrayHasKey('HDFC', $response['methods']['emi_options']);
        $this->assertEquals('6', $response['methods']['emi_options']['HDFC'][0]['duration']);
        $this->assertEquals('0', $response['methods']['emi_options']['HDFC'][0]['interest']);

    }

    public function testGetCheckoutPreferencesForCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $this->fixtures->merchant->enableCardlessEmiProviders(['earlysalary' => 1]);

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('earlysalary', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesWithProcessingFeePlan()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableDebitEmiProviders();

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1 , 'UTIB' => 1 , 'KKBK' => 1]);

        $this->fixtures->create('emi_plan:default_emi_plans');

        $response = $this->getPreferences();

        $this->assertArrayHasKey('processing_fee_plan', $response['methods']['emi_options']['HDFC'][0]);

        $this->assertArrayHasKey('processing_fee_plan', $response['methods']['emi_options']['UTIB'][0]);

        $this->assertArrayHasKey('processing_fee_plan', $response['methods']['emi_options']['KKBK'][0]);
    }

    public function testGetCheckoutPreferencesForDebitEmi()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->enableDebitEmiProviders();

        $this->fixtures->emiPlan->create(
            [
                'merchant_id' => '10000000000000',
                'bank'        => 'HDFC',
                'type'        => 'debit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $response = $this->getPreferences();

        $this->assertArrayHasKey('HDFC_DC', $response['methods']['emi_options']);
    }

    public function testGetCheckoutPreferencesForDebitEmiProviders()
    {
        $this->fixtures->merchant->enableEmiDebit();

        $this->fixtures->merchant->enableDebitEmiProviders();

        $response = $this->getPreferences();

        $this->assertArraySelectiveEquals(['HDFC' => 1], $response['methods']['debit_emi_providers']);

        $this->assertTrue($response['methods']['emi_types']['debit']);

        $this->assertFalse($response['methods']['emi_types']['credit']);
    }

    public function testGetCheckoutPreferencesForCreditEmiProviders()
    {

        $this->fixtures->merchant->enableEmiCredit();

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101310',
                'merchant_id' => '100000Razorpay',
                'bank'        => 'HDFC',
                'type'        => 'credit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $response = $this->getPreferences();

        $this->assertArrayHasKey('HDFC', $response['methods']['emi_options']);

        $this->assertEquals(1, count($response['methods']['emi_options']));

        $this->assertTrue($response['methods']['emi_types']['credit']);

        $this->assertFalse($response['methods']['emi_types']['debit']);
    }

    public function testGetCheckoutPreferencesForPaylaterProviders()
    {

        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->merchant->enablePaylaterProviders(['icic' => 1]);

        $this->fixtures->create('terminal:paylater_icici_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['paylater']));

        $this->assertArrayHasKey('icic', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesForCardlessEmiProviders()
    {

        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->merchant->enableCardlessEmiProviders(['earlysalary' => 1]);

        $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('earlysalary', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesForDisabledDebitEmiProviders()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->merchant->disableDebitEmiProviders();

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101312',
                'merchant_id' => '10000000000000',
                'bank'        => 'HDFC',
                'type'        => 'debit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $response = $this->getPreferences();

        $this->assertArrayNotHasKey('HDFC_DC', $response['methods']['emi_options']);
    }

    public function testGetCheckoutPreferencesForDebitEmiWithExistingCreditEmi()
    {
        $this->fixtures->merchant->enableEmi();
        $this->fixtures->merchant->enableDebitEmiProviders();
        $this->fixtures->merchant->enableCreditEmiProviders(['HDFC' => 1]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101310',
                'merchant_id' => '100000Razorpay',
                'bank'        => 'HDFC',
                'type'        => 'credit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $this->fixtures->emiPlan->create(
            [
                'id'          => '10101010101312',
                'merchant_id' => '10000000000000',
                'bank'        => 'HDFC',
                'type'        => 'debit',
                'rate'        => 1200,
                'min_amount'  => 300000,
                'duration'    => 3,
            ]);

        $response = $this->getPreferences();

        $this->assertArrayHasKey('HDFC_DC', $response['methods']['emi_options']);
        $this->assertArrayHasKey('HDFC', $response['methods']['emi_options']);
    }

    public function testGetCheckoutPreferencesForPayLater()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->merchant->enablePaylaterProviders(['icic' => 1]);

        $this->fixtures->create('terminal:paylater_icici_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['paylater']));

        $this->assertArrayHasKey('icic', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesForPayLaterEnabledBanks()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->merchant->enablePaylaterProviders(['icic' => 1 , "hdfc" => 1]);

        $this->fixtures->create('terminal:paylater_icici_terminal');
        $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(2, count($response['methods']['paylater']));

        $this->assertArrayHasKey('icic', $response['methods']['paylater']);
        $this->assertArrayHasKey('hdfc', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesForPaytmWithTerminal()
    {
        $this->ba->proxyAuthTest();

        $this->fixtures->merchant->activate('10000000000000');

        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'paytm',
            'card'                      => 1,
            'netbanking'                => 1,
            'gateway_merchant_id'       => 'razorpaypaytm',
            'gateway_secure_secret'     => 'randomsecret',
            'gateway_terminal_id'       => 'nodalaccountpaytm',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'www.merchant.com',
            'enabled'                   =>  1
        );

        $this->fixtures->on('test')->create('terminal', $attributes);

        $response = $this->getPreferences();

        $this->assertArrayHasKey('paytm', $response['methods']['wallet']);

        $this->assertEquals(true, $response['methods']['wallet']['paytm']);
    }

    public function testGetCheckoutPreferencesForPaytmWithoutTerminal()
    {
        $this->ba->proxyAuthTest();

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enablePaytm();

        $response = $this->getPreferences();

        $this->assertArrayHasKey('paytm', $response['methods']['wallet']);
    }

    public function testGetCheckoutPreferencesForPaytmInLiveMode() // in live mode, paytm should not check for a terminal to be enabled
    {
        $this->ba->proxyAuthLive();

        (new Merchant\Methods\Core)->setModeAndDefaultConnection('live');

        $this->fixtures->merchant->activate('10000000000000');

        $this->fixtures->merchant->enablePaytm();

        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => [
                    'INR'
                ],
            ],
        ];

        $this->ba->publicLiveAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('paytm', $response['methods']['wallet']);

        $this->assertEquals(true, $response['methods']['wallet']['paytm']);
    }

    public function testGetCheckoutPreferencesForPrepaidRecurringEnabled()
    {
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => [
                    'INR'
                ],
            ],
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(["MasterCard","Visa","RuPay"], $response['methods']['recurring']['card']['prepaid']);
    }

    public function testGetCheckoutPreferencesAfterFilterForMinimumAmount()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->merchant->enablePaylaterProviders(['icic' => 1 , 'hdfc' => 1]);

        $this->fixtures->create('terminal:paylater_icici_terminal');
        $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('hdfc', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesWithCustomProviders()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->merchant->enableCardlessEmiProviders(
            [
                'hdfc' => 1 ,
                'icic' => 1 ,
                'barb' => 1 ,
                'kkbk' => 1 ,
                'fdrl' => 1 ,
                'idfb' => 1 ,
                'hcin' => 1 ,
                'krbe' => 1 ,
                'cshe' => 1 ,
                'tvsc' => 1 ,
            ]);

        $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNoCustomProviders()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->create('terminal:paylater_icici_terminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('custom_providers', $response['methods']);
    }

    public function testGetCheckoutPreferencesAfterFilterForMinimumAmountOnCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();
        $this->fixtures->merchant->enableCardlessEmiProviders(['walnut369' => 1]);

        $this->fixtures->create('terminal:shared_cardless_emi_walnut369_terminal');
//        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('walnut369', $response['methods']['cardless_emi']);
//        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);

    }

    public function testGetCheckoutPreferencesAfterFilterForMinimumAmountOnHomeCreditCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->merchant->enableCardlessEmiProviders(['hcin' => 1]);

        $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');
//        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('hcin', $response['methods']['cardless_emi']);
//        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);

    }

    public function testGetCheckoutPreferencesWithAmountGreaterForHomeCreditCardlessEmi()
    {
        $this->markTestSkipped("hcin is deprecated");

        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->merchant->enableCardlessEmiProviders([ 'hcin' => 1]);

        $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');

//        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('hcin', $response['methods']['cardless_emi']);
//        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);

    }

    public function testGetCheckoutPreferencesWithAmountGreaterForCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->merchant->enableCardlessEmiProviders(['walnut369' => 1]);

        $this->fixtures->create('terminal:shared_cardless_emi_walnut369_terminal');
//        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('walnut369', $response['methods']['cardless_emi']);
//        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesForCardlessEmiEnabledBanks()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->merchant->enableCardlessEmiProviders(['hdfc' => 1 , 'icic' => 1 , 'barb' => 1 , 'kkbk' => 1 , 'fdrl' => 1 , 'idfb' => 1 , 'hcin' => 1, 'krbe' => 1, 'cshe' => 1, 'tvsc' => 1]);

        $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');
        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $response = $this->getPreferences();

        $this->assertEquals(7, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('kkbk', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('hdfc', $response['methods']['cardless_emi']);
//        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);
//        $this->assertArrayHasKey('barb', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('cshe', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('krbe', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('tvsc', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesWithAmountGreater()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->merchant->enablePaylaterProviders(['icic' => 1 , 'hdfc' => 1]);

        $this->fixtures->create('terminal:paylater_icici_terminal');
        $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('hdfc', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesForPaypalCurrency()
    {
        $this->fixtures->merchant->enablePaypal();

        $this->fixtures->create('terminal:paypal_usd_terminal');

        $response = $this->getPreferences(null, 'USD');

        $this->assertEquals(true, $response['methods']['wallet']['paypal']);
    }

    public function testGetCheckoutPreferencesForPaypalCurrencyWithOrder()
    {
        $order = $this->fixtures->order->createWalletInternationalOrder();

        $this->fixtures->merchant->enablePaypal();

        $this->fixtures->create('terminal:paypal_usd_terminal');

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertEquals(true, $response['methods']['wallet']['paypal']);
    }

    public function testGetCheckoutPreferencesWithInactiveEmiSubventionOffer()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'payment_network' => null,
            'active'          => false
        ]);

        $order = $this->fixtures->order->createWithOffers($offer);

        $response = $this->getPreferences($order->getPublicId());

        // Offer is inactive now, so plans will be back to customer subvention
        foreach ($response['methods']['emi_options']['HDFC'] as $plan)
        {
            $this->assertEquals('customer', $plan['subvention']);
        }
    }

    public function testGetCheckoutPreferencesWithEmiSubventionOfferUnderMinAmount()
    {
        $this->fixtures->merchant->enableEmi();

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'              => 'HDFC',
            'payment_network'     => null,
            'payment_method_type' => 'credit',
            'emi_durations'       => [
                6,
                9,
            ],
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, ['amount' => 7000]);

        $response = $this->getPreferences($order->getPublicId());

        $hdfcPlans = $response['methods']['emi_options']['HDFC'];

        // Amount is under the minimum amount for EMI subvention offers,
        // so plans show up as customer subvention
        foreach ($response['methods']['emi_options']['HDFC'] as $plan)
        {
            $this->assertEquals('customer', $plan['subvention']);
        }

        $order = $this->fixtures->order->createWithOffers($offer, ['amount' => 700000]);

        $response = $this->getPreferences($order->getPublicId());

        $hdfcPlans = $response['methods']['emi_options']['HDFC'];

        // Amount is above the minimum amount for EMI subvention offers,
        // so plans show up as merchant subvention
        foreach ($response['methods']['emi_options']['HDFC'] as $plan)
        {
            $this->assertEquals('merchant', $plan['subvention']);
        }
    }

    public function testGetCheckoutPreferencesWithContactDetails()
    {
        $this->fixtures->create('contact', [
            'id' => 'ABCD123321DCBA',
        ]);

        $this->fixtures->create('fund_account', [
            'id'           => '100000000000fa',
            'source_id'    => 'ABCD123321DCBA',
            'source_type'  => 'contact',
            'account_type' => 'bank_account',
            'account_id'   => '1000000lcustba',
        ]);

        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency'      => 'INR',
                'contact_id'    => 'cont_ABCD123321DCBA',
            ],
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['contact']['id'], 'cont_ABCD123321DCBA');
        $this->assertEquals($response['contact']['fund_accounts'][0]['id'], 'fa_100000000000fa');
    }

    public function testGetCheckoutPreferencesIINDetails()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->fixtures->iin->create(
            [
                'iin'           => '411140',
                'network'       => 'Mastercard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'SBIN',
            ]
        );

        $this->fixtures->customer->create(
            [
                'id'            => '1000ggcustomer',
                'name'          => 'test123',
                'email'         => 'test@razorpay.com',
                'contact'       => '+919955555555',
                'merchant_id'   => '10000000000000'
            ]
        );

        $this->fixtures->token->create(
            [
                'id'            => '100022custcard',
                'token'         => '10003cardtoken',
                'customer_id'   => '1000ggcustomer',
                'method'        => 'card',
                'card_id'       => '100000002lcard',
                'used_at'       =>  10,
            ]
        );

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithContactDetailsWhereContactDoesNotExist()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForPaidOrder()
    {
        $order = $this->fixtures->order->createPaid();

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForCancelledInvoice()
    {
        $attributes = [
            'type'         => 'link',
            'status'       => 'cancelled',
            'amount'       => 100000,
            'cancelled_at' => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $invoice = $this->createInvoice($attributes);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForExpiredInvoice()
    {
        $attributes = [
            'type'       => 'link',
            'status'     => 'expired',
            'amount'     => 100000,
            'expired_at' => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $invoice = $this->createInvoice($attributes);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithOrderRelatedUndiscountedOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        foreach ($testData['tests'] as $test)
        {
            $data = [
                'request' => $request,
                'response' => $test['response'],
            ];

            $fixtureData = $test['offer'];
            $fixtureData['starts_at'] = $startsAt;

            $offer = $this->fixtures->create('offer', $fixtureData);
            $order = $this->fixtures->order->createWithUndiscountedOffers($offer, [
                'force_offer' => true,
            ]);

            $data['request']['url'] = '/preferences?order_id=' . $order->getPublicId();

            $this->runRequestResponseFlow($data);
        }
    }

    public function testGetCheckoutPreferencesWithoutOfferWithInvalidAmount()
    {
        $offerWithoutMinAmount = $this->fixtures->create('offer',[
            'name'       => 'offer_without_min_amount',
        ]);

        $offerWithMinAmount = $this->fixtures->create('offer', [
            'name'       => 'offer_with_min_amount',
            'min_amount' => 10000,
        ]);

        // Order created with 2 offers
        $order = $this->fixtures->order->createWithOffers([
            $offerWithoutMinAmount,
            $offerWithMinAmount
        ], [ 'amount' => 5000 ]);

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent([
            'method'  => 'GET',
            'url'     => '/preferences?order_id=' . $order->getPublicId(),
        ]);

        $preferencesOffers = $response['offers'];

        // Only 1 offers appears in preferences response
        $this->assertEquals(count($preferencesOffers), 1);
        // The one without a criteria on amount
        $this->assertEquals($preferencesOffers[0]['name'], 'offer_without_min_amount');
    }

    public function testGetCheckoutPreferencesWithOrderRelatedOffer()
    {
        $this->ba->publicAuth();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        foreach ($testData['tests'] as $test)
        {
            $data = [
                'request' => $request,
                'response' => $test['response'],
            ];

            $fixtureData = $test['offer'];
            $fixtureData['starts_at'] = $startsAt;

            $offer = $this->fixtures->create('offer', $fixtureData);
            $order = $this->fixtures->order->createWithOffers($offer);

            $data['request']['url'] = '/preferences?order_id=' . $order->getPublicId();

            $this->runRequestResponseFlow($data);
        }
    }

    public function testGetCheckoutPreferencesWithOrderInActiveOffer()
    {
        $this->ba->publicAuth();

        $offer = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],
            'active' => 0
        ]);

        $order = $this->fixtures->order->createWithOffers($offer);

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('offers', $response);
    }

    public function testGetCheckoutPreferencesWithOrderExpiredOffer()
    {
        $this->ba->publicAuth();

        $offer = $this->fixtures->create('offer:expired', ['iins' => ['401200'],
            'active' => 0
        ]);

        $order = $this->fixtures->order->createWithOffers($offer);

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('offers', $response);
    }

    public function testGetCheckoutPreferencesWithOrderForceOfferExpired()
    {
        $this->ba->publicAuth();

        $offer = $this->fixtures->create('offer:expired', ['iins' => ['401200'],
            'active' => 0
        ]);

        $order = $this->fixtures->order->createWithOffers($offer,[
            'force_offer' => true,
        ]);

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('offers', $response);
    }

    public function testGetCheckoutPreferencesWithMultipleOrderOffersInActive()
    {
        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],
            'active' => 0
        ]);

        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200'],
            'active' => 1]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $this->ba->publicAuth();

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertNotNull($response['offers']);

        $this->assertEquals(1, count($response['offers']));

        $this->assertEquals('offer_' . $offer2->getId(), $response['offers']['0']['id']);
    }

    public function testGetCheckoutPreferencesWithMultipleOrderOffersExpired()
    {
        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $endsAt = Carbon::now(Timezone::IST)->timestamp;

        $offer1 = $this->fixtures->create('offer:expired', ['iins' => ['401200']]);

        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $this->ba->publicAuth();

        $testData = $this->testData['testOfferCheckoutPreferences'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertNotNull($response['offers']);

        $this->assertEquals(1, count($response['offers']));

        $this->assertEquals('offer_' . $offer2->getId(), $response['offers']['0']['id']);
    }

    public function testGetCheckoutPreferencesWithEmiOffer()
    {
        $this->ba->publicAuth();

        $this->fixtures->merchant->enableEmi();

        $startsAt = Carbon::yesterday(Timezone::IST)->timestamp;

        $testData = $this->testData[__FUNCTION__];

        $offer = $this->fixtures->create('offer', [
            'payment_method' => 'emi',
            'error_message'  => 'Payment method used is not eligible for offer. ' .
                'Please try with a different payment method.',
            'display_text'   => 'Some display text',
            'percent_rate'   => 5000,
            'min_amount'     => 200000,
            'terms'          => 'Some terms',
            'type'           => 'instant',
        ]);

        $order = $this->fixtures->order->createWithOffers($offer, ['amount' => 300000]);

        $testData['request']['url'] = '/preferences?order_id=' . $order->getPublicId();

        $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPreferencesForCredSubtext()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'cred'  => 1,
                ],
                'custom_text' => [
                    'cred' => 'discount of 20% with CRED coins'
                ]
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['methods']['custom_text']);

    }

    public function testGetCheckoutPreferencesWithOrderMethodForNonTPVEnabledMerchant()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->create('order', ['method' => 'upi']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPreferencesWithConfigIdInOrder()
    {
        $this->ba->publicAuth();

        $config = $this->fixtures->create('config');

        $order = $this->fixtures->create('order', ['checkout_config_id' => $config->getId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayHasKey('checkout_config', $response);
    }

    public function testGetCheckoutPreferencesWithDefaultConfig()
    {
        $this->ba->publicAuth();

        $config = $this->fixtures->create('config');

        $order = $this->fixtures->create('order');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayHasKey('checkout_config', $response);
    }

    public function testGetCheckoutPreferencesWithDefaultConfigWhenMoreThanOneDefaultConfigExistsExpectsMostRecentlyUpdatedConfig()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('config', [
            'config'=> '{"sequence": ["block.gpay","card","block.hdfc"]}'
        ]);

        $this->fixtures->create('config', [
            'config' => '{"sequence": ["block.hdfc","card"]}',
            'updated_at' => Carbon::now()->getTimestamp() + 1
        ]);

        $order = $this->fixtures->create('order');

        $testData = $this->testData['testGetCheckoutPreferencesWithDefaultConfig'];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $expectedConfig = [];

        $expectedConfig['sequence'] = ['block.hdfc', 'card'];

        $this->assertArrayHasKey('checkout_config', $response);

        $this->assertEquals($expectedConfig, $response['checkout_config']);
    }

    public function testGetCheckoutPreferencesForInvoiceWithOffer()
    {
        $this->ba->publicAuth();

        $offer1 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);
        $offer2 = $this->fixtures->create('offer:live_card', ['iins' => ['401200']]);

        $order = $this->fixtures->order->createWithOffers([
            $offer1,
            $offer2,
        ]);

        $invoice = $this->fixtures->create('invoice', ["order_id" => $order->getId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['invoice_id'] = $invoice->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayHasKey('offers', $response);

    }

    public function testGetCheckoutPreferencesWithPreferredMethods()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayHasKey('preferred_methods', $response);

    }

    public function testGetCheckoutPreferencesForSiftJSIntegration()
    {
        $this->fixtures->merchant->addFeatures(Constants::DISABLE_SIFT_JS);

        $response = $this->getPreferences();

        $this->assertEquals($response['features']['disable_sift_js'],true);
    }

    public function testGetCheckoutPreferencesForCybersourceIntegration()
    {
        $this->fixtures->merchant->addFeatures(Constants::SHIELD_CYBERSOURCE_ROLLOUT);

        $response = $this->getPreferences();

        $this->assertEquals($response['features']['shield_cbs_rollout'], true);
    }

    public function testGetCheckoutPreferencesForHDFCCheckout2()
    {
        $this->fixtures->merchant->addFeatures(Constants::HDFC_CHECKOUT_2);

        $response = $this->getPreferences();

        $this->assertEquals($response['features']['hdfc_checkout_2'], true);
    }

    public function testGetCheckoutPreferencesForMORdisplay()
    {
        $this->fixtures->merchant->addFeatures(Constants::SHOW_MOR_TNC);

        $response = $this->getPreferences();

        $this->assertEquals($response['features']['show_mor_tnc'],true);
    }

    protected function getPreferences($orderId = null, $currency = 'INR')
    {
        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => [
                    $currency
                ],
            ],
        ];

        if ($orderId !== null)
        {
            $request['content']['order_id'] = $orderId;
        }

        $this->ba->publicAuth();
        return $this->makeRequestAndGetContent($request);
    }

    public function testGetCheckoutPersonalisationWithCustomerIdAndInputContact()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayHasKey('preferred_methods', $response);
    }

    public function testGetCheckoutPersonalisationWithCustomerIdInternal()
    {
        $this->ba->checkoutServiceProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayHasKey('preferred_methods', $response);
    }

    public function testGetCheckoutPersonalisation()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayHasKey('preferred_methods', $response);

    }

    public function testGetCheckoutPersonalisationForNonLoggedInUser()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForNonLoggedInUserWithContact()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForContact()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForContactInternal()
    {
        $this->ba->checkoutServiceProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForCustomerId()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('preferred_methods', $response);
    }

    public function testGetCheckoutPersonalisationForContactDifferentFromLogInContact()
    {
        $appToken = 'capp_1000000custapp';

        $this->mockSession($appToken);

        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testPersonalisationForContactDifferentFromLogInContactForCheckoutServiceAuth(): void
    {
        $appToken = 'capp_1000000custapp';

        $this->mockSession($appToken);

        $this->ba->checkoutServiceProxyAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForContactSameWithLogInContact()
    {
        $appToken = 'capp_1000000custapp';

        $this->mockSession($appToken);

        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationForLoogedInUserInternal()
    {
        $appToken = 'capp_1000000custapp';

        $this->mockSession($appToken);

        $this->ba->checkoutServiceProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);
    }

    // Scenario for below test case is:
    // CustomerId is passed in the input and there is already a user is logged in
    // The p13n response will be of that of the customerId
    public function testGetCheckoutPersonalisationWithCustomerIdAndLogInContact()
    {
        $appToken = 'capp_1000000custapp';

        $this->mockSession($appToken);

        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPreferencesWithRTB(): void
    {
        $this->ba->publicAuth();

        $this->fixtures->create('trusted_badge', [
            TrustedBadge::STATUS          => TrustedBadge::ELIGIBLE,
            TrustedBadge::MERCHANT_STATUS => TrustedBadge::WAITLIST,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals(true, $response['rtb']);
    }

    public function testPreferencesRTBWithOptoutStatus(): void
    {
        $this->ba->publicAuth();

        $this->fixtures->create('trusted_badge', [
            TrustedBadge::STATUS          => TrustedBadge::ELIGIBLE,
            TrustedBadge::MERCHANT_STATUS => TrustedBadge::OPTOUT,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals(false, $response['rtb']);
    }

    public function testPreferencesRTBWithIneligibleStatus(): void
    {
        $this->ba->publicAuth();

        $this->fixtures->create('trusted_badge', [
            TrustedBadge::STATUS          => TrustedBadge::INELIGIBLE,
            TrustedBadge::MERCHANT_STATUS => TrustedBadge::WAITLIST,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals(false, $response['rtb']);
    }

    public function testGetCheckoutPreferencesWithoutRTB(): void
    {
        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertFalse($response['rtb']);
    }

    public function testGetCheckoutPersonalisationWithNullPreferences()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPersonalisationWithNullPreferencesFalse()
    {
        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->runRequestResponseFlow($testData);
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    public function testGetCheckoutPreferencesExperimentDisabled()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->ba->publicAuth();

        $response = $this->getPreferences();

        $this->assertArrayNotHasKey('merchant_policy', $response);
    }

    public function testGetCheckoutPreferencesWithoutMerchantPolicy()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->ba->publicAuth();

        $response = $this->getPreferences();

        $this->assertArrayNotHasKey('merchant_policy', $response);
    }

    public function testGetCheckoutPreferencesWithMerchantPolicyActivatedMerchant()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $merchantId = '10000000000000';
        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
            'status'                   => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
                    "status"         => "submitted",
                    "published_url"  => env(\RZP\Models\Merchant\Website\Constants::MERCHANT_POLICIES_SUBDOMAIN) . '/compliance/' . $merchantId . '/contact_us'
                ]
            ]
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'app_urls'    => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url'  => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);
        $this->mockSplitzTreatment($output);

        $this->fixtures->create('merchant_detail', [
            "merchant_id"       => $merchantId,
            'business_website'  => "http://hello.com",
            "activation_status" => "activated"]);

        $this->ba->publicAuth();

        $response = $this->getPreferences();

        $this->assertArrayHasKey('merchant_policy', $response);
        $this->assertArrayHasKey('url', $response['merchant_policy']);
        $this->assertArrayHasKey('display_name', $response['merchant_policy']);
    }

    public function testGetCheckoutPreferencesWithPublishedWebsiteMerchantPolicy()
    {

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $merchantId='10000000000000';

        $this->fixtures->edit('merchant',$merchantId, []);

        $this->fixtures->create('merchant_detail', ["merchant_id"=> $merchantId,
                                                    'business_website' => "http://hello.com"]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'           => $merchantId,
            'status'      => 'submitted',
            "shipping_period"          => "3-5 days",
             "refund_request_period"    => "3-5 days",
             "refund_process_period"    => "3-5 days",
             "additional_data"          => [
                 "support_contact_number" => "9980004017",
                 "support_email"          => "kakarla.vasanthi@razorpay.com"
             ],
             "merchant_website_details" => [
                 "contact_us" => [
                     "section_status" => 3,
                     "status"         => "submitted",
                     "published_url"  => env(\RZP\Models\Merchant\Website\Constants::MERCHANT_POLICIES_SUBDOMAIN) . '/compliance/'.$merchantId.'/contact_us'
                 ]
             ]
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'app_urls' => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url' => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);


        $this->ba->publicAuth();

        $response = $this->getPreferences();

        $this->assertArrayHasKey('merchant_policy', $response);
        $this->assertArrayHasKey('url', $response['merchant_policy']);
        $this->assertArrayHasKey('display_name', $response['merchant_policy']);
    }

    public function testGetMerchantPolicyDetails(): void
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $merchantId='10000000000000';

        $this->fixtures->edit('merchant',$merchantId, []);

        $this->fixtures->create('merchant_detail', ["merchant_id"=> $merchantId,
            'business_website' => "http://hello.com"]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'           => $merchantId,
            'status'      => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
                    "status"         => "submitted",
                    "published_url"  => env(\RZP\Models\Merchant\Website\Constants::MERCHANT_POLICIES_SUBDOMAIN) . '/compliance/'.$merchantId.'/contact_us'
                ]
            ]
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'app_urls' => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url' => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $response = $this->startTest();
        $this->assertNotNull($response['url']);
        $this->assertNotNull($response['display_name']);
    }

    public function testGetCheckoutPreferencesWithoutPublishedWebsiteMerchantPolicy()
    {

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $merchantId = '10000000000000';

        $this->fixtures->edit('merchant', $merchantId, []);

        $this->fixtures->create('merchant_detail', ["merchant_id"      => $merchantId,
                                                    'business_website' => "http://hello.com"]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
            'status'                   => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 2,
                    "status"         => "submitted",
                    "published_url"  => env(\RZP\Models\Merchant\Website\Constants::MERCHANT_POLICIES_SUBDOMAIN) . '/compliance/' . $merchantId . '/contact_us'
                ]
            ]
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'app_urls'    => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url'  => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);

        $this->ba->publicAuth();

        $response = $this->getPreferences();

        $this->assertArrayNotHasKey('merchant_policy', $response);
    }

    public function testGetCheckoutPreferencesWithPublishedWebsiteMerchantPolicyFromCache()
    {

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $merchantId = '10000000000000';

        $this->fixtures->edit('merchant', $merchantId, []);

        $this->fixtures->create('merchant_detail', [
            "merchant_id"       => $merchantId,
            'business_website'  => "http://hello.com",
            "activation_status" => "activated"]);

        $data = [
            StoreConstants::NAMESPACE => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::POLICY_DATA    => [
                "url"          => "http://merchant.razorpay.com/policy/" . $merchantId,
                "display_name" => "About Merchant"
            ]
        ];

        $data = (new StoreCore())->updateMerchantStore($merchantId, $data, StoreConstants::INTERNAL);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
            'status'                   => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "contact_us" => [
                    "section_status" => 3,
                    "status"         => "submitted",
                    "published_url"  => env(\RZP\Models\Merchant\Website\Constants::MERCHANT_POLICIES_SUBDOMAIN) . '/compliance/' . $merchantId . '/contact_us'
                ]
            ]
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'app_urls'    => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url'  => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForDynamicWalletFlowRaas()
    {
        $this->fixtures->merchant->addFeatures(Feature\Constants::RAAS);

        $response = $this->getPreferences();

        $this->assertEquals(true, $response[Merchant\Checkout::DYNAMIC_WALLET_FLOW]);
    }

    public function testGetCheckoutPreferencesForDynamicWalletFlowOrgId()
    {
        $this->fixtures->merchant->activate('10000000000000');
        $this->fixtures->merchant->addFeatures(Feature\Constants::RAAS);
        $org = $this->fixtures->org->createAxisOrg();
        $this->fixtures->merchant->edit('10000000000000',
            [
                'org_id' => Admin\Org\Entity::AXIS_ORG_ID
            ]
        );

        $response = $this->getPreferences();

        $this->assertEquals(true, $response[Merchant\Checkout::DYNAMIC_WALLET_FLOW]);
    }

    public function testUpdateFetchCouponsURL()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testUpdateShippingInfoURL()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testUpdateCouponValidityURL()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testUpdateMerchantPlatform()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testUpdateMerchantPlatformInvalidBody()
    {
        $this->ba->proxyAuthTest();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithFeeConfigNull()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayNotHasKey('convenience_fee_config', $response['order']);
    }

    public function testGetCheckoutPreferencesWithOfflineEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::OFFLINE_PAYMENT_ON_CHECKOUT]);

        $this->fixtures->merchant->enableOffline();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithOfflineDisabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::OFFLINE_PAYMENT_ON_CHECKOUT]);

        $this->fixtures->merchant->disableOffline();

        $this->ba->publicAuth();

        $this->startTest();
    }


    public function testGetCheckoutPreferencesWithFeeConfigEmpty()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayNotHasKey('convenience_fee_config', $response['order']);
    }

    public function testGetCheckoutPreferencesWithFeeConfigEmptyRules()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayNotHasKey('convenience_fee_config', $response['order']);
    }

    public function testGetCheckoutPreferencesWithFeeConfigWithPayeeCustomerForUPI()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayHasKey('convenience_fee_config', $response['order']);

        $expectedResponse = [
            "label_on_checkout" => "Convenience Fee",
            "methods" => [
                "upi" => [
                    "amount" => 200
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response['order']['convenience_fee_config']);
    }

    public function testGetCheckoutPreferencesWithFeeConfigPayeeCustomerForCardTypes()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayHasKey('convenience_fee_config', $response['order']);
        $expectedResponse = [
            "label_on_checkout" => "Convenience Fee",
            "methods" => [
                "card" => [
                    "type" => [
                        "debit" => [
                            "amount" => 200
                        ],
                        "prepaid" => [
                            "amount" => 200
                        ],
                        "credit" => [
                            "amount" => 100
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response['order']['convenience_fee_config']);
    }

    public function testGetCheckoutPreferencesWithFeeConfigPayeeCustomerForCardAndDebitType()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayHasKey('convenience_fee_config', $response['order']);

        $expectedResponse = [
            "label_on_checkout" => "Convenience Fee",
            "methods" => [
                "card" => [
                    "type" => [
                        "debit" => [
                            "amount" => 200
                        ],
                        "credit" => []
                    ],
                    "amount" => 300
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response['order']['convenience_fee_config']);
    }

    public function testGetCheckoutPreferencesWithFeeConfigWithoutPrecalculatedCustomerFee()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayHasKey('convenience_fee_config', $response['order']);

        $expectedResponse = [
            "label_on_checkout" => "Convenience Fee"
        ];

        $this->assertEquals($expectedResponse, $response['order']['convenience_fee_config']);
    }

    public function testGetCheckoutPreferencesWithFeeConfigCardTypesWithoutPrecalculatedCustomerFee()
    {
        $data = $this->testData[__FUNCTION__];

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic']);

        $this->ba->privateAuth();

        $this->runRequestResponseFlow($data);

        $order = $this->getDbLastOrder();

        $response = $this->getPreferences($order->getPublicId(), 'INR');

        $this->assertArrayHasKey('convenience_fee_config', $response['order']);

        $expectedResponse = [
            "label_on_checkout" => "Convenience Fee",
            "methods" => [
                "card" => [
                    "type" => [
                        "credit" => [],
                        "debit" => [],
                        "prepaid" => []
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResponse, $response['order']['convenience_fee_config']);
    }

    public function testGetCheckoutPreferenceWithOutUserConsentTokenisation()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockSession();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixturesToCreateCardToken('100022xtokenl1', '100000003card1', '411140', '10000000000000');

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPreferenceWithUserConsentTokenisation()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockSession();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixturesToCreateCardToken('100022xtokenl1', '100000003card1', '411140', '10000000000000');

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGetCheckoutPreferenceWithUserConsentTokenisationWithCustomerId()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $order = $this->fixtures->order->create();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixtures->edit('token', '100001custcard', ['acknowledged_at' => Carbon::now()->timestamp]);

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testRTBExperimentOnNotLiveMerchants()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('trusted_badge', [
            TrustedBadge::STATUS          => TrustedBadge::INELIGIBLE,
            TrustedBadge::MERCHANT_STATUS => TrustedBadge::WAITLIST,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $request = array(
            'url'     => '/personalisation?contact=9999999999',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotContains('rtb_experiment', $response);
    }

    public function testGetRTBExperimentDetailsMerchantNotInExperimentList()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('trusted_badge', [
            TrustedBadge::STATUS          => TrustedBadge::ELIGIBLE,
            TrustedBadge::MERCHANT_STATUS => TrustedBadge::WAITLIST,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertNotContains('variant', $response['rtb_experiment']);

        $request = array(
            'url'     => '/personalisation?contact=9999999999',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(false, $response['rtb_experiment']['experiment']);
        $this->assertNotContains('variant', $response['rtb_experiment']);
    }

    public function testGetRTBExperimentDetailsMerchantInExperimentList()
    {
        $this->markTestSkipped();

        $this->fixtures->create('trusted_badge', [
            TrustedBadge::STATUS          => TrustedBadge::ELIGIBLE,
            TrustedBadge::MERCHANT_STATUS => TrustedBadge::WAITLIST,
        ]);

        $this->testRTBExperimentMerchantList();

        $this->ba->publicAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);

        $request = array(
            'url'     => '/personalisation?contact=9999999999',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['rtb_experiment']['experiment']);

        $this->assertEquals('not_applicable', $response['rtb_experiment']['variant']);
    }

    public function testRTBExperimentMerchantList()
    {
        $this->ba->trustedBadgeInternalAppAuth();

        // empty redis key test
        $request = array(
            'url'     => '/trusted_badge/experiment_list',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals([], $response);

        $request = array(
            'url'     => '/trusted_badge/experiment_list',
            'method'  => 'PUT',
            'content' => [
                'merchants' => ['10000000000001']
            ]
        );

        $this->makeRequestAndGetContent($request);

        $request = array(
            'url'     => '/trusted_badge/experiment_list',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(['10000000000001'], $response);

        // test for the put api call should overwrite the redis key.
        $request = array(
            'url'     => '/trusted_badge/experiment_list',
            'method'  => 'PUT',
            'content' => [
                'merchants' => ['10000000000000']
            ]
        );

        $this->makeRequestAndGetContent($request);

        $request = array(
            'url'     => '/trusted_badge/experiment_list',
            'method'  => 'get',
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(['10000000000000'], $response);
    }

    public function testGetCheckoutPreferencesFor1CCOrderWithLineItems() {
        $this->fixtures->merchant->addFeatures([Constants::ONE_CLICK_CHECKOUT]);
        $order = $this->fixtures->order->create1ccOrderWithLineItems();

        $this->ba->publicAuth();

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesForDudupeLocalOverGlobalTokensWhenGlobalTokenExpectsToReturnGlobalToken()
    {
        $this->markTestSkipped('This test case is not applicable as we are not supporting global tokens');

        $this->ba->publicAuth();

        $this->mockSession();

        $payload = $this->testData['testGetCheckoutPreferencesForDedupeLocalTokensOverGlobalTokens'];

        $this->fixtureToCreateIin();
        $this->fixturesToCreateCardToken('100022xtokeng1', '100000003card2', '411140');

        $response = $this->startTest($payload);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertContains('token_100022xtokeng1', $tokenIds);
    }

    public function testCheckoutPreferencesForDedupeRecurringCardLocalToken()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === RazorxTreatment::DEDUP_RECURRING_SAVED_CARD_TOKEN)
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        $this->ba->publicAuth();

        $this->mockSession();

        $this->fixturesToCreateRecurringCardToken('100022xtokeng1', '100000003card2',
            '411140',self::DEFAULT_MERCHANT_ID,self::LOCAL_CUSTOMER_ID );

        $this->fixturesToCreateRecurringTokenForExistingCard('100022xtokeng2', '100000003card2',
            self::DEFAULT_MERCHANT_ID,self::LOCAL_CUSTOMER_ID );

        $request = [
            'url'     => '/preferences',
            'method'  => 'get',
            'content' => [
                'currency' => [
                    'INR'
                ],
            ],
        ];

        $request['content']['customer_id'] = 'cust_' . self::LOCAL_CUSTOMER_ID;

        $response = $this->makeRequestAndGetContent($request);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertContains('token_100022xtokeng2', $tokenIds);

        $this->assertNotContains('token_100022xtokeng1', $tokenIds);

    }



    public function testGetCheckoutPreferencesForDudupeLocalOverGlobalTokensWhenGlobalAndLocalTokenOfSameCardExpectsToReturnLocalToken()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockSession();

        $payload = $this->testData['testGetCheckoutPreferencesForDedupeLocalTokensOverGlobalTokens'];

        $this->fixtureToCreateIin();
        $this->fixturesToCreateCardToken('100022xtokenl1', '100000003card1', '411140', '10000000000000');
        $this->fixturesToCreateCardToken('100022xtokeng1', '100000003card2', '411140');

        $response = $this->startTest($payload);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertContains('token_100022xtokenl1', $tokenIds);
        $this->assertNotContains('token_100022xtokeng1', $tokenIds);
    }

    public function testGetCheckoutPreferencesForDudupeLocalOverGlobalTokensWhenGlobalAndLocalTokenOfSameCardOfDiffMerchantExpectsToReturnLocalTokenOfLoggedInMercahant()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockSession();

        $payload = $this->testData['testGetCheckoutPreferencesForDedupeLocalTokensOverGlobalTokens'];

        $this->fixtureToCreateIin();
        $this->fixtures->merchant->createAccount('10000000000001');
        $this->fixturesToCreateCardToken('100022xtokenl2', '100000003card3', '411140', '10000000000001');
        $this->fixturesToCreateCardToken('100022xtokenl1', '100000003card1', '411140', '10000000000000');
        $this->fixturesToCreateCardToken('100022xtokeng1', '100000003card2', '411140');

        $response = $this->startTest($payload);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertContains('token_100022xtokenl1', $tokenIds);
        $this->assertNotContains('token_100022xtokeng1', $tokenIds);
        $this->assertNotContains('token_100022xtokenl2', $tokenIds);
    }


    public function testGetCheckoutPreferencesForDudupeLocalOverGlobalTokensWhenLocalCustomerExpectsToReturnOnlyLocalCustomerTokens()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $payload = $this->testData['testGetCheckoutPreferencesForDedupeLocalTokensOverGlobalTokens'];

        $payload['request']['content']['customer_id'] = 'cust_' . self::LOCAL_CUSTOMER_ID;

        $this->fixtureToCreateIin();
        $this->fixtureToCreateIin('411141');

        $this->fixturesToCreateCardToken(
            '10002tokenlcl1',
            '100000003card1',
            '411140',
            Merchant\Account::TEST_ACCOUNT,
            self::LOCAL_CUSTOMER_ID
        );
        $this->fixturesToCreateCardToken('10002tokengcl1', '100000003card2', '411141', Merchant\Account::TEST_ACCOUNT);
        $this->fixturesToCreateCardToken('100022xtokeng1', '100000003card3', '411141');

        $response = $this->startTest($payload);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertContains('token_10002tokenlcl1', $tokenIds);
        $this->assertNotContains('token_10002tokengcl1', $tokenIds);
        $this->assertNotContains('token_100022xtokeng1', $tokenIds);
    }

    public function testGetCheckoutPreferencesReturnsBothCardAndUpiTokensForGlobalCustomer(): void
    {
        $this->markTestSkipped('Skipping For Now Till We Figure Out Why This Works on Local But Fails on Github.');

        $this->ba->publicAuth();

        $this->mockSession();

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, 'upi');

        $this->fixtures->merchant->addFeatures(['save_vpa'], Merchant\Account::TEST_ACCOUNT);

        $globalUpiToken = $this->fixtures->create('customer:upi_payments_global_customer_token');

        $localUpiToken = $this->fixtures->create('customer:upi_payments_local_customer_token');

        // Local Card Token on Global Customer
        $this->fixturesToCreateCardToken(
            '10002tokenlcl1',
            '100000003card2',
            '411141',
            Merchant\Account::TEST_ACCOUNT
        );

        $payload = $this->testData['testGetCheckoutPreferencesForDedupeLocalTokensOverGlobalTokens'];

        $response = $this->startTest($payload);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertContains($globalUpiToken->getPublicId(), $tokenIds);

        $this->assertNotContains($localUpiToken->getPublicId(), $tokenIds);

        $this->assertContains('token_10002tokenlcl1', $tokenIds);
    }

    public function testGetCheckoutPreferencesReturnsBothCardAndUpiTokensForLocalCustomer(): void
    {
        $this->markTestSkipped('Skipping For Now Till We Figure Out Why This Works on Local But Fails on Github.');

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, 'upi');

        $this->fixtures->merchant->addFeatures(['save_vpa'], Merchant\Account::TEST_ACCOUNT);

        $globalUpiToken = $this->fixtures->create('customer:upi_payments_global_customer_token');

        $localUpiToken = $this->fixtures->create('customer:upi_payments_local_customer_token');

        // Local Card Token on Local Customer
        $this->fixturesToCreateCardToken(
            '10002tokenlcl1',
            '100000003card2',
            '411141',
            Merchant\Account::TEST_ACCOUNT,
            self::LOCAL_CUSTOMER_ID
        );

        $payload = $this->testData['testGetCheckoutPreferencesForDedupeLocalTokensOverGlobalTokens'];

        $payload['request']['content']['customer_id'] = 'cust_' . self::LOCAL_CUSTOMER_ID;

        $response = $this->startTest($payload);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertNotContains($globalUpiToken->getPublicId(), $tokenIds);

        $this->assertContains($localUpiToken->getPublicId(), $tokenIds);

        $this->assertContains('token_10002tokenlcl1', $tokenIds);
    }

    public function testPreferencesResponseDoesNotContainStatusInactiveCardTokens(): void
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->publicAuth();

        $this->mockSession();

        $payload = $this->testData['testGetCheckoutPreferencesForDedupeLocalTokensOverGlobalTokens'];

        $this->fixturesToCreateCardToken('100022xtokenl1', '100000003card1', '411140', '10000000000000', '10000gcustomer', ['vault' => 'rzpvault']);
        $this->fixturesToCreateCardToken('100022xtokenl2', '100000003card2', '411141', '10000000000000', '10000gcustomer', ['vault' => 'visa', 'status' => 'active']);
        $this->fixturesToCreateCardToken('100022xtokenl3', '100000003card3', '411142', '10000000000000', '10000gcustomer', ['vault' => 'visa']);
        $this->fixturesToCreateCardToken('100022xtokenl4', '100000003card4', '411143', '10000000000000', '10000gcustomer', ['vault' => 'visa', 'status' => 'deactivated']);
        $this->fixturesToCreateCardToken('100022xtokenl5', '100000003card5', '411144', '10000000000000', '10000gcustomer', ['vault' => 'visa', 'status' => 'deleted']);

        $response = $this->startTest($payload);

        $tokenIds = $this->extractTokenIdsFromResponse($response);

        $this->assertContains('token_100022xtokenl1', $tokenIds);
        $this->assertContains('token_100022xtokenl2', $tokenIds);
        $this->assertNotContains('token_100022xtokenl3', $tokenIds);
        $this->assertNotContains('token_100022xtokenl4', $tokenIds);
        $this->assertNotContains('token_100022xtokenl5', $tokenIds);
    }

    protected function extractTokenIdsFromResponse(array $response): array
    {
        $tokenIds = [];

        $tokens = $response['customer']['tokens']['items'] ?? [];

        foreach ($tokens as $token)
        {
            $tokenIds[] = $token['id'];
        }

        return $tokenIds;
    }

    protected function fixtureToCreateIin($iin = '411140'): void
    {
        $this->fixtures->iin->create(
            [
                'iin'     => $iin,
                'country' => 'IN',
                'issuer'  => 'HDFC',
                'network' => 'Visa',
                'flows'   => [
                    '3ds' => '1',
                    'headless_otp'  => '1',
                ]
            ]
        );
    }

    protected function fixturesToCreateCardToken(
        $tokenId,
        $cardId,
        $iin,
        $merchantId = '100000Razorpay',
        $customerId = self::GLOBAL_CUSTOMER_ID,
        $inputFields = []
    )
    {
        $this->fixtures->card->create(
            [
                'id'            => $cardId,
                'merchant_id'   => $merchantId,
                'name'          => 'test',
                'iin'           => $iin,
                'expiry_month'  => '12',
                'expiry_year'   => '2100',
                'issuer'        => 'HDFC',
                'network'       => $inputFields['network'] ?? 'Visa',
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => $inputFields['vault'] ?? 'rzpvault',
                'vault_token'   => 'test_token',
                'international' => $inputFields['international'] ?? null,
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'customer_id'     => $customerId,
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
                'status'          => $inputFields['status'] ?? NULL,
            ]
        );
    }

    protected function fixturesToCreateRecurringCardToken(
        $tokenId,
        $cardId,
        $iin,
        $merchantId = self::DEFAULT_MERCHANT_ID,
        $customerId = self::LOCAL_CUSTOMER_ID,
        $inputFields = []
    )
    {
        $this->fixtures->card->create(
            [
                'id'            => $cardId,
                'merchant_id'   => $merchantId,
                'name'          => 'test',
                'iin'           => $iin,
                'expiry_month'  => '12',
                'expiry_year'   => '2100',
                'issuer'        => 'HDFC',
                'network'       => $inputFields['network'] ?? 'Visa',
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => 'rzpvault',
                'vault_token'   => 'test_token',
                'international' => $inputFields['international'] ?? null,
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'customer_id'     => $customerId,
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
                'recurring'       => 1,
            ]
        );
    }

    protected function fixturesToCreateRecurringTokenForExistingCard(
        $tokenId,
        $cardId,
        $merchantId = self::DEFAULT_MERCHANT_ID,
        $customerId = self::LOCAL_CUSTOMER_ID,
        $inputFields = []
    )
    {
        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'customer_id'     => $customerId,
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
                'recurring'       => 1,
            ]
        );
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = array(
            'test_app_token'   => $appToken,
            'test_checkcookie' => '1'
        );

        $this->session($data);
    }

    public function testGetPreferencesWhenEmailOptionalOnCheckoutAndShowEmailOnCheckoutFeaturesEnabledExpectsBothTheFeatureFlagsInPreferencesResponse()
    {
        $this->fixtures->merchant->addFeatures([
            Dcs\Features\Constants::ShowEmailOnCheckout,
            Dcs\Features\Constants::EmailOptionalOnCheckout
        ]);

        $expDetails[] = [
            'experiment_id' => 'app.email_less_checkout_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertTrue($response['features'][Dcs\Features\Constants::ShowEmailOnCheckout]);
        $this->assertTrue($response['features'][Dcs\Features\Constants::EmailOptionalOnCheckout]);
    }

    public function testGetPreferenceWithFeatureFlagEligibilityCheckDeclineAndEligibilityOnStdCheckoutExperimentPresentInPreferencesResponse()
    {
        $this->fixtures->merchant->addFeatures([
            Dcs\Features\Constants::EligibilityCheckDecline,
        ]);

        $expDetails[] = [
            'experiment_id' => 'app.eligibility_on_std_checkout_splitz_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertTrue($response['features'][Dcs\Features\Constants::EligibilityCheckDecline]);
        $this->assertTrue($response['experiments']['eligibility_on_std_checkout']);
    }

    public function testGetPreferencesWhenShowEmailOnCheckoutFeaturesEnabledExpectsShowEmailOnCheckoutFeatureFlagInPreferencesResponse()
    {
        $this->fixtures->merchant->addFeatures([
            Dcs\Features\Constants::ShowEmailOnCheckout
        ]);

        $expDetails[] = [
            'experiment_id' => 'app.email_less_checkout_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertTrue($response['features'][Dcs\Features\Constants::ShowEmailOnCheckout]);
        $this->assertArrayHasKey(Dcs\Features\Constants::ShowEmailOnCheckout, $response['features']);
        $this->assertArrayNotHasKey(Dcs\Features\Constants::EmailOptionalOnCheckout, $response['features']);
    }

    public function testGetPreferencesWhenEmailOptionalOnCheckoutFeatureEnabledExpectsEmailOptionalOnCheckoutFeatureFlagInPreferencesResponse()
    {
        $this->fixtures->merchant->addFeatures([
            Dcs\Features\Constants::EmailOptionalOnCheckout
        ]);

        $expDetails[] = [
            'experiment_id' => 'app.email_less_checkout_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertTrue($response['features'][Dcs\Features\Constants::EmailOptionalOnCheckout]);
        $this->assertArrayHasKey(Dcs\Features\Constants::EmailOptionalOnCheckout, $response['features']);
        $this->assertArrayNotHasKey(Dcs\Features\Constants::ShowEmailOnCheckout, $response['features']);
    }

    public function testGetPreferencesWhenEmailOptionalOnCheckoutAndShowEmailOnCheckoutFeaturesNotEnabledExpectsBothFeatureFlagsNotInPreferencesResponse()
    {
        $expDetails[] = [
            'experiment_id' => 'app.email_less_checkout_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertArrayNotHasKey(Dcs\Features\Constants::EmailOptionalOnCheckout, $response['features']);
        $this->assertArrayNotHasKey(Dcs\Features\Constants::ShowEmailOnCheckout, $response['features']);
    }

    public function testGetPreferencesWhenOptimizerMerchantAndEmailOptionalOnCheckoutAndShowEmailOnCheckoutFeaturesNotEnabledExpectsShowEmailOnCheckoutFeatureFlagInPreferencesResponse()
    {
        $expDetails[] = [
            'experiment_id' => 'app.email_less_checkout_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        // Enabling optimizer on the merchant
        $this->fixtures->merchant->addFeatures([
            Feature\Constants::RAAS,
        ]);

        $response = $this->getPreferences();

        // Email mandatory on checkout
        $this->assertTrue($response['features'][Dcs\Features\Constants::ShowEmailOnCheckout]);
        $this->assertFalse($response['features'][Dcs\Features\Constants::EmailOptionalOnCheckout]);
    }

    public function testGetPreferencesWhenOptimizerMerchantAndEmailOptionalOnCheckoutEnabledExpectsShowEmailOnCheckoutFeatureFlagInPreferencesResponse()
    {
        $expDetails[] = [
            'experiment_id' => 'app.email_less_checkout_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        // Enabling optimizer on the merchant
        $this->fixtures->merchant->addFeatures([
            Dcs\Features\Constants::EmailOptionalOnCheckout,
            Feature\Constants::RAAS,
        ]);

        $response = $this->getPreferences();

        // Email mandatory on checkout
        $this->assertTrue($response['features'][Dcs\Features\Constants::ShowEmailOnCheckout]);
        $this->assertFalse($response['features'][Dcs\Features\Constants::EmailOptionalOnCheckout]);
    }

    public function testGetPreferencesWhenEmailLessCheckoutExperimentDisabledExpectsShowEmailOnCheckoutFeatureFlagInPreferencesResponse()
    {
        $expDetails[] = [
            'experiment_id' => 'app.email_less_checkout_experiment_id',
            'result' => 'variant_off'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertArrayHasKey(Dcs\Features\Constants::ShowEmailOnCheckout, $response['features']);
        $this->assertArrayNotHasKey(Dcs\Features\Constants::EmailOptionalOnCheckout, $response['features']);
    }

    public function testPreferencesForBajajPay()
    {
        $this->fixtures->merchant->enableAdditionalWallets([Wallet::BAJAJPAY]);
        $this->ba->publicAuth();
        $response = $this->getPreferences();
        $this->assertArrayHasKey(Wallet::BAJAJPAY, $response['methods']['wallet']);
        $this->assertTrue($response['methods']['wallet']['bajajpay']);
    }

    public function testPreferencesForBoost()
    {
        $this->fixtures->merchant->enableAdditionalWallets([Wallet::BOOST]);
        $this->ba->publicAuth();
        $response = $this->getPreferences();
        $this->assertArrayHasKey(Wallet::BOOST, $response['methods']['wallet']);
        $this->assertTrue($response['methods']['wallet']['boost']);
    }

    public function testPreferencesForMCash()
    {
        $this->fixtures->merchant->enableAdditionalWallets([Wallet::MCASH]);
        $this->ba->publicAuth();
        $response = $this->getPreferences();
        $this->assertArrayHasKey(Wallet::MCASH, $response['methods']['wallet']);
        $this->assertTrue($response['methods']['wallet']['mcash']);
    }

    public function testPreferencesForTouchNGO()
    {
        $this->fixtures->merchant->enableAdditionalWallets([Wallet::TOUCHNGO]);
        $this->ba->publicAuth();
        $response = $this->getPreferences();
        $this->assertArrayHasKey(Wallet::TOUCHNGO, $response['methods']['wallet']);
        $this->assertTrue($response['methods']['wallet']['touchngo']);
    }

    public function testPreferencesForGrabPay()
    {
        $this->fixtures->merchant->enableAdditionalWallets([Wallet::GRABPAY]);
        $this->ba->publicAuth();
        $response = $this->getPreferences();
        $this->assertArrayHasKey(Wallet::GRABPAY, $response['methods']['wallet']);
        $this->assertTrue($response['methods']['wallet']['grabpay']);
    }

    public function testGetPreferencesWhenCvvLessFlowDisabledOnMerchantExpectsCvvLessFlowDisabled()
    {
        $expDetails[] = [
            'experiment_id' => 'app.checkout_cvv_less_splitz_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $this->fixtures->merchant->addFeatures([
            Dcs\Features\Constants::CvvLessFlowDisabled,
        ]);

        $response = $this->getPreferences();

        $this->assertTrue($response['features'][Dcs\Features\Constants::CvvLessFlowDisabled]);
    }

    public function testGetPreferencesWhenCvvLessFlowDisabledFlagNotPresentOnMerchantExpectsCvvLessFlowEnabled()
    {
        $expDetails[] = [
            'experiment_id' => 'app.checkout_cvv_less_splitz_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertArrayNotHasKey(Dcs\Features\Constants::CvvLessFlowDisabled, $response['features']);
    }

    public function testGetPreferencesWhenOptimizerMerchantExpectsCvvLessFlowDisabled()
    {
        $expDetails[] = [
            'experiment_id' => 'app.checkout_cvv_less_splitz_experiment_id',
            'result' => 'variant_on'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        // Enabling optimizer on the merchant
        $this->fixtures->merchant->addFeatures([
            Feature\Constants::RAAS,
        ]);

        $response = $this->getPreferences();

        $this->assertTrue($response['features'][Dcs\Features\Constants::CvvLessFlowDisabled]);
    }

    public function testGetPreferencesWhenCvvLessFlowEnabledOnMerchantAndExperimentIsDisabledExpectsCvvLessFlowDisabled()
    {
        $expDetails[] = [
            'experiment_id' => 'app.checkout_cvv_less_splitz_experiment_id',
            'result' => 'variant_off'
        ];

        $this->mockCheckoutBulkExperiment($expDetails);

        $response = $this->getPreferences();

        $this->assertTrue($response['features'][Dcs\Features\Constants::CvvLessFlowDisabled]);
    }

    public function testCheckoutV1ApiPreferencesThroughCheckoutService(): void
    {
        $this->ba->publicAuth();

        $request = [
            'method'  => 'GET',
            'url'     => '/checkout',
            'content' => [],
        ];

        $splitzMockResponse = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzMockResponse);

        $checkoutServiceMockResponse = [
            'merchant_key' => 'rzp_test_TheTestAuthKey',
            'merchant_name' => 'checkout_service',
            'methods' => [
                'card' => true,
            ],
        ];
        $this->mockCheckoutService($checkoutServiceMockResponse);

        $response = $this->sendRequest($request);
        $responseContent = $response->getOriginalContent();

        $prefData = $this->extractPreferencesDataFromHtmlResponseString($responseContent);

        $this->assertNotNull($prefData);
        $this->assertEquals('rzp_test_TheTestAuthKey', $prefData['merchant_key']);
        $this->assertEquals('checkout_service', $prefData['merchant_name']);
        $this->assertEquals(true, $prefData['methods']['card']);
    }

    protected function mockCheckoutBulkExperiment($experimentIdsWithExpectedResult)
    {
        $output = [];

        foreach ($experimentIdsWithExpectedResult as $experimentDetails)
        {
            $output[] = [
                "experiment" => [
                    "id" => $this->app['config']->get($experimentDetails['experiment_id']),
                ],
                "variant"    => [
                    "name" => $experimentDetails['result'],
                ],
            ];
        }

        $this->mockSplitzTreatmentBulkRequest($output);
    }

    protected function mockSplitzTreatmentBulkRequest($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('bulkCallsToSplitz')
            ->andReturn($output);
    }

    protected function mockCheckoutService($output)
    {
        $this->checkoutServiceMock = Mockery::mock(CheckoutService::class)->makePartial();

        $this->app->instance('checkout_service', $this->checkoutServiceMock);

        $this->checkoutServiceMock
            ->shouldReceive('getCheckoutPreferencesFromCheckoutService')
            ->andReturn(new Response($output, 200, []));
    }

    protected function extractPreferencesDataFromHtmlResponseString(string $htmlResponse)
    {
        $dom = new DOMDocument();
        $dom->loadHTML($htmlResponse);

        $prefData = null;

        // Get all the script elements in the HTML
        $scriptElements = $dom->getElementsByTagName('script');

        // Loop through each script element and extract its data
        foreach ($scriptElements as $scriptElement) {
            $scriptData = $scriptElement->nodeValue;

            // Use regular expressions to match variable assignments
            $pattern = '/var\s+(\w+)\s*=\s*(.*);/i';
            preg_match_all($pattern, $scriptData, $matches, PREG_SET_ORDER);

            // Loop through each match and get the variable name and value
            foreach ($matches as $match) {
                $variableName = $match[1];
                $variableValue = $match[2];

                if ($variableName === "preferences") {
                    $prefData = json_decode($variableValue, true);
                    break;
                }
            }
        }

        return $prefData;
    }
}
