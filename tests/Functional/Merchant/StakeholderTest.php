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

    protected function setUp(): void
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

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testFetchStakeholder'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/sth_'. $response['id'];
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateStakeholderCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/sth_'. $response['id'];
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testFetchAllAccountStakeholders'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';
        $this->runRequestResponseFlow($testData);
    }

    public function testCreateStakeholderInvalidPercentageOwnership()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';
        $this->runRequestResponseFlow($testData);
    }

    public function testCreateStakeholderForThinRequest()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders';

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateStakeholderThinToCompleteRequest'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/stakeholders/sth_'. $response['id'];
        $this->runRequestResponseFlow($testData);
    }
}
