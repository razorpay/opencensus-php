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

class CheckoutFeeCalculationTest extends TestCase
{
    use PaymentTrait;
    use CreatesInvoice;
    use DbEntityFetchTrait;
    use MocksRedisTrait;
    use MocksRazorx;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/FeeCalculationTestData.php';

        parent::setUp();

        $this->fixtures->create('org:hdfc_org');

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();
    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "percentage_value": 50}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "percentage_value": 50}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "flat_value": 100}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerFlatValueWithCFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeCustomerPercentageValueWithCFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "percentage_value": 50}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessPercentageValueWithCFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "percentage_value": 50}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigNetBankingPayeeBusinessFlatValueWithCFB()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "flat_value": 100}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'netbanking',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeCustomerFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "flat_value": 200}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'upi',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 400,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeCustomerPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "customer", "percentage_value": 50}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'upi',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 400,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeBusinessPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "percentage_value": 50}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'upi',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 400,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesForUPIWithFeeConfigNetBankingPayeeBusinessFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"netbanking": {"fee": {"payee": "business", "flat_value": 100}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'upi',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 400,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
            'fee_bearer' => 'customer'
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigCreditCardPayeeCustomerFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "flat_value": 200}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigCreditCardPayeeCustomerPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "customer", "percentage_value": 40}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigCreditCardPayeeBusinessPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "percentage_value": 60}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigCreditCardPayeeBusinessFlatValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"card": {"type": {"credit": {"fee": {"payee": "business", "flat_value": 100}}}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigWalletPayeeCustomerFlatValueZero()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"wallet": {"fee": {"payee": "customer", "flat_value": 0}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'wallet',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigWalletPayeeCustomerFlatValueGreaterthanFee()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"wallet": {"fee": {"payee": "customer", "flat_value": 400}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'wallet',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

    public function testPaymentCalculateFeesWithFeeConfigWalletPayeeBusinessPercentageValue()
    {
        $paymentConfig = $this->fixtures->create('config', ['name' => '10000000000000_fee_config', 'type' => 'convenience_fee', 'config'=>'{"label": "Convenience Fee", "rules": {"wallet": {"fee": {"payee": "business", "percentage_value": 33}}}}']);

        $pricingPlan = [
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'wallet',
            'payment_method_type' => null,
            'payment_network' => null,
            'payment_issuer' => null,
            'percent_rate' => 300,
            'fixed_rate' => 0,
            'org_id'    => '100000razorpay',
        ];

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $order = $this->fixtures->create('order', ['amount' => 10000, 'reference7' => $paymentConfig->getId()]);

        $this->fixtures->edit('merchant','10000000000000' ,['fee_bearer' => 'dynamic', 'pricing_plan_id' => $plan->getPlanId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->ba->publicAuth();

        $this->startTest();

    }

}
