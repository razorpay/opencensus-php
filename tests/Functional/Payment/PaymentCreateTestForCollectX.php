<?php

namespace RZP\Tests\Functional\Payment;

use DateTimeZone;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Builder;
use Mail;
use Mockery;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factory;
use RZP\Constants\Mode;
use RZP\Models\Order;
use RZP\Services\EsClient;
use RZP\Models\Card\Network;
use RZP\Models\Card\Repository;
use RZP\Models\NetbankingConfig;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\Helpers\PaymentsUpiTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Error\PublicErrorCode;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Services\Dcs;
use RZP\Jobs\EsSync;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Services\RazorXClient;
use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\UpiMetadata;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Mail\Payment\Refunded as RefundedMail;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Mail\Merchant\AuthorizedPaymentsReminder;
use RZP\Mail\Payment\Authorized as AuthorizedMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\Merchant\OneClickCheckout\RtoPredictionService as RtoPredictionService;

class PaymentCreateTestForCollectX extends TestCase
{
    use OAuthTrait;
    use MocksSplitz;
    use PartnerTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use InvoiceTestTrait;
    use TerminalTrait;
    use HeimdallTrait;
    use PaymentsUpiTrait;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentCreateTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testCreateNetbankingPaymentSuccess()
    {
        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $paymentArray['amount'] = '1000000';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $paymentArray
        ];

        $response = $this->makeRequestParent($request);
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'],'created');
    }

    public function testCreateNetbankingPaymentSuccessForCollectXEnabled()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::COLLECTX_ENABLED]);

        $paymentArray = $this->getDefaultNetbankingPaymentArray();

        $paymentArray['amount'] = '1000000';

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $paymentArray
        ];

        $response = $this->makeRequestParent($request);
        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals($payment['status'],'created');
    }
}
