<?php

namespace RZP\Tests\Functional\Gateway\Esigner\Digio;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class DigioGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/DigioGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_digio_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'esigner_digio';
    }

    public function testSuccessfulEsignGeneration()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);
    }

    protected function runPaymentCallbackFlowEsignerDigio($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        if ($mock)
        {
            $content = $response->getContent();

            $json = $this->getPaymentJsonFromCallback($content, 'data');

            $url = getTextBetweenStrings($content, '***', '***');
            $method = 'post';
            $content = ['json' => $json];

            $this->ba->noAuth();
            $request = $this->makeFirstGatewayPaymentMockRequest(
                                                $url, $method, $content);
        }

        return $this->submitPaymentCallbackRequest($request);
    }
}
