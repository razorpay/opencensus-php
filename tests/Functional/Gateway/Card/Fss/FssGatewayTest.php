<?php

namespace RZP\Tests\Functional\Gateway\Card\Fss;

class FssGatewayTest extends BobGatewayTest
{
    protected $acquirer = 'fss';

    public function testUnformattedError()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['error']   = 'incorrect PIN';
            }
        });

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment();
        });
    }
}

