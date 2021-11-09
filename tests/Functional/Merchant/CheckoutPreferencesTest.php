<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use App;
use Mail;
use Event;
use Redis;
use Mockery;
use Carbon\Carbon;
use RZP\Models\TrustedBadge\Entity as TrustedBadge;
use RZP\Services\Mock;
use RZP\Models\Base\EsDao;
use RZP\Services\UfhService;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Jobs\FundAccountValidation;
use Illuminate\Cache\Events\CacheHit;
use RZP\Models\BankAccount\Repository;
use RZP\Models\FundAccount\Validation;
use RZP\Mail\Merchant as MerchantMail;
use Illuminate\Cache\Events\KeyWritten;
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
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

use RZP\Models\Key;
use RZP\Jobs\EsSync;
use RZP\Models\Admin;
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

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->fixtures->create('org:hdfc_org');

        $this->app->make(Factory::class)->load($factoryPath);

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
        $this->assertContains(['upi_otm'], $response['features']);
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

        $this->assertCount(46, $banks);

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

        $this->fixtures->create('emi_plan:default_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'payment_network' => null,
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

        $this->fixtures->create('emi_plan:default_emi_plans');

        $this->fixtures->create('emi_plan:merchant_specific_emi_plans');

        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'HDFC',
            'emi_durations'   => [6],
            'payment_network' => null,
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

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('earlysalary', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesForCardlessEmiFlexmoney()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:cardless_emi_flexmoney_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('flexmoney', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesForEmptyEnabledBanks()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:cardlessEmiFlexMoneyEmptyEnabledBanks');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('flexmoney', $response['methods']['cardless_emi']);
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

        $this->fixtures->create('terminal:paylater_epaylater_terminal');

        $response = $this->getPreferences();

        $this->assertEquals(1, count($response['methods']['paylater']));

        $this->assertArrayHasKey('epaylater', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesForPayLaterEnabledBanks()
    {
        $this->fixtures->merchant->enablePayLater();

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

    public function testGetCheckoutPreferencesAfterFilterForMinimumAmount()
    {
        $this->fixtures->merchant->enablePayLater();

        $this->fixtures->create('terminal:paylater_icici_terminal');
        $this->fixtures->create('terminal:paylater_flexmoney_terminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('hdfc', $response['methods']['paylater']);
    }

    public function testGetCheckoutPreferencesAfterFilterForMinimumAmountOnCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:shared_cardless_emi_walnut369_terminal');
        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('walnut369', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);

    }

    public function testGetCheckoutPreferencesAfterFilterForMinimumAmountOnHomeCreditCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');
        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('hcin', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);

    }

    public function testGetCheckoutPreferencesWithAmountGreaterForHomeCreditCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();
        $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');

        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('hcin', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);

    }

    public function testGetCheckoutPreferencesWithAmountGreaterForCardlessEmi()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:shared_cardless_emi_walnut369_terminal');
        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('walnut369', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesForCardlessEmiEnabledBanks()
    {
        $this->fixtures->merchant->enableCardlessEmi();

        $this->fixtures->create('terminal:cardlessEmiFlexMoneySubproviderTerminal');
        $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $response = $this->getPreferences();

        $this->assertEquals(7, count($response['methods']['cardless_emi']));

        $this->assertArrayHasKey('kkbk', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('hdfc', $response['methods']['cardless_emi']);
        $this->assertArrayHasKey('zestmoney', $response['methods']['cardless_emi']);
    }

    public function testGetCheckoutPreferencesWithAmountGreater()
    {
        $this->fixtures->merchant->enablePayLater();

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
            'issuer'          => 'HDFC',
            'payment_network' => null,
            'emi_durations'   => [
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

    public function testGetCheckoutPersonalisationForNonLoggedInUserWithInternationalContact()
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

        $this->assertEquals($testData['response']['content'], $response);
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

        $this->assertEquals($testData['response']['content'], $response);
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
        
        $this->assertEquals($testData['response']['content'], $response);
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = array(
            'test_app_token'   => $appToken,
            'test_checkcookie' => '1'
        );

        $this->session($data);
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

    public function testGetCheckoutPreferencesWithCovidReliefBothEnable()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'on');

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::COVID_19_RELIEF]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithoutCovidReliefBothDisable()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'off');

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithoutCovidReliefRazorXOff()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'off');

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::COVID_19_RELIEF]);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithoutCovidReliefFeatureOff()
    {
        $this->mockRazorxTreatmentV2(RazorxTreatment::COVID_19_DONATION_SHOW, 'off');

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
        $this->ba->privateAuth();

        $this->startTest();
    }
    
    public function testUpdateShippingInfoURL()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUpdateCouponValidityURL()
    {
        $this->ba->privateAuth();

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


}
