<?php

namespace RZP\Tests\Functional\Gateway\Card\Fss;

class FssGatewayTest extends BobGatewayTest
{
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
}
