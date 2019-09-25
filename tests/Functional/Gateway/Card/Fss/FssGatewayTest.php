<?php

namespace RZP\Tests\Functional\Gateway\Card\Fss;

use RZP\Gateway\Card\Fss\Fields;
use RZP\Gateway\Card\Fss\ErrorCodes\ErrorCodes;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class FssGatewayTest extends BobGatewayTest
{
    use DbEntityFetchTrait;

    protected $acquirer = 'fss';

    public function testPaymentAuthWithCardHolderNameSpecialChars()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['name'] = 'N. HJJKas1232';

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize_decrypted')
            {
                self::assertEquals('N HJJKas', $content['member']);
            }
        }, $this->gateway);

        $this->doAuthPayment($payment);
    }

    public function testPaymentAuthWithCardHolderNameSpecialCharsAndMultipleSpaces()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['card']['name'] = '  N. HJJK   as1232   ';

        $this->mockServerRequestFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize_decrypted')
            {
                self::assertEquals('N HJJK as', $content['member']);
            }
        }, $this->gateway);

        $this->doAuthPayment($payment);
    }

    public function testPaymentWithEmptyPaymentId()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                unset($content['trackid']);
            }
        }, $this->gateway);

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment');

        self::assertEquals('authorized',$payment['status']);
    }

    public function testPaymentFailure()
    {
        $payment = $this->getDefaultPaymentArray();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                unset($content[Fields::TRAN_DATA]);
                $content['error_text'] = ErrorCodes::ISSUER_AUTHENTICATION_SERVER_FAILURE;
            }
        });

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntityToArray('payment');
        $this->assertEquals('failed', $payment['status']);

        $gatewayPayment = $this->getDbLastEntityToArray('card_fss');

        $this->assertArraySelectiveEquals(
            [
                'payment_id'    => $payment['id'],
                'action'        => 'authorize',
                'error_message' => 'Issuer Authentication Server failure',
            ],
            $gatewayPayment
        );
    }
}
