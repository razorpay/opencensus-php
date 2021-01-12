<?php

namespace Functional\Merchant;

use RZP\Constants\Mode;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class StakeholderTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/StakeholderTestData.php';
        parent::setUp();
    }

    public function testCreateStakeholderForCompletelyFilledRequest()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/'. $subMerchant->getId() .'/stakeholders';

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testFetchStakeholder'];
        $testData['request']['url'] = '/v2/accounts/'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateStakeholderCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);
    }

    public function testCreateStakeholderForThinRequest()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/'. $subMerchant->getId() .'/stakeholders';

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateStakeholderThinToCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/'. $subMerchant->getId() .'/stakeholders/'. $response['id'];
        $this->runRequestResponseFlow($testData);
    }
}
