<?php

namespace Functional\Edge;

use Illuminate\Support\Facades\DB;
use Mockery;
use RZP\Constants\Table;
use Hash;
use RZP\Http\RequestHeader;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class EdgeAuthenticateTest extends TestCase
{
    use RequestResponseFlowTrait;
    private $defaultRequestData;
    const TEST_MERCHANT_ID = '10000000000000';
    const TEST_UNMAPPED_MERCHANT_ID = '10000000000011';
    const TEST_INVALID_MERCHANT_ID = '20000000000011';
    const TEST_INVALID_ORG_ID = '100001razorpay';
    const TEST_ORG_HOSTNAME = 'dashboard.razorpay.in';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EdgeAuthenticateTestData.php';
        parent::setUp();
        $this->createMerchantAdminMap(Org::SUPER_ADMIN, self::TEST_MERCHANT_ID);
    }

    public function testInvalidAuth()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testNonDashboardApp()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testValidAppButDifferentSecret()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithAdminTokenRequiredFalse()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithAdminToken()
    {
        $data = array_merge($this->testData['baseRequest'], $this->testData['baseResponse']);
        $this->createMerchantAdminMap(Org::SUPER_ADMIN, self::TEST_MERCHANT_ID);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithInvalidAdminToken()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithAdminTokenAndAccountId()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $data = array_merge($data, $this->testData['baseResponse']);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithAdminTokenAndSignedAccountId()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $data = array_merge($data, $this->testData['baseResponse']);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithAdminTokenAndInvalidAccountId()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithAdminTokenAndUnmappedAccountId()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithoutAdminToken()
    {
        $data = array_merge($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        unset($data['request']['content']['dashboard']['headers'][RequestHeader::X_ADMIN_TOKEN]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithProxyAuthKey()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithInvalidOrg()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithValidOrgHostname()
    {
        $data = array_merge($this->testData['baseRequest'], $this->testData['baseResponse']);
        unset($data['request']['org_id']);
        $data = array_replace_recursive($data, $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithMIDInRouteParams()
    {
        $data = array_merge($this->testData['baseRequest'], $this->testData['baseResponse']);
        $data = array_replace_recursive($data, $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInternalAuthWithUnmappedMIDInRouteParams()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testProxyAuthWithAdminToken()
    {
        $data = array_merge($this->testData['baseRequest'], $this->testData['baseResponse']);
        $data = array_replace_recursive($data, $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testInvalidProxyAuthWithAdminToken()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testProxyAuthWithInternalAuthCreds()
    {
        $data = array_replace_recursive($this->testData['baseRequest'], $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testProxyAuthWithValidOrgHostname()
    {
        $data = array_merge($this->testData['baseRequest'], $this->testData['baseResponse']);
        unset($data['request']['org_id']);
        $data = array_replace_recursive($data, $this->testData[__FUNCTION__]);
        $this->runRequestResponseFlow($data);
    }

    public function testProductAndSubProductInResponse()
    {
        // Arrange
        $data = array_merge($this->testData['baseRequest'], $this->testData['baseResponse']);
        $data['request']['auth'] = 'proxy';

        $splitzMock = Mockery::mock('overload:RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator');
        $splitzMock->allows('evaluateExperiment')
            ->andReturns(['variant' => 'variant']);

        $this->runRequestResponseFlow($data);
    }

    public function testProductAndSubProductInResponseWithoutSplitz()
    {
        // Arrange
        $data = array_merge($this->testData['baseRequest'], $this->testData['baseResponse']);
        $data['request']['auth'] = 'proxy';

        $splitzMock = Mockery::mock('overload:RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator');
        $splitzMock->allows('evaluateExperiment')
            ->andReturns(['variant' => null]);

        $this->runRequestResponseFlow($data);
    }

    private function createMerchantAdminMap($adminId, $merchantId)
    {
        DB::table(Table::MERCHANT_MAP)->insertOrIgnore([
            [
                'merchant_id'   => $merchantId,
                'entity_id'     => $adminId,
                'entity_type'   => 'admin',
            ]
        ]);
    }
}
