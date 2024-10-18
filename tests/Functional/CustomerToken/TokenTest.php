<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;
use \WpOrg\Requests\Response;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Constants;
use RZP\Models\Card\Network;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TokenTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use TerminalTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['network_tokenization', 'allow_network_tokens']);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testFetchTokenCard()
    {
        $this->ba->privateAuth();

        $Payload = $this->testData['testCreateToken'];
        $createTokenResponse = $this->startTest($Payload);

        $id = $createTokenResponse['id'];
        $testData = $this->testData['testFetchTokenCardTestData'];
        $testData['request']['content']['token_id'] = $id;

        $this->ba->optimizerInternalAppAuth();
        $this->startTest($testData);
    }


    public function testFetchTokenCardWithInvalidToken()
    {
        $this->ba->optimizerInternalAppAuth();
        $this->startTest();
    }
}
