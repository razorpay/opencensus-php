<?php


namespace Unit\Models\Merchant\Asv\AsvClient;

use \RZP\Tests\Functional\TestCase;
use Google\ApiCore\ValidationException;
use RZP\Exception\IntegrationException;
use Rzp\Accounts\SyncDeviation\V1 as syncDeviationV1;
use Rzp\Accounts\SyncDeviation\V1\StringList as StringList;
use RZP\Models\Merchant\Acs\AsvClient\SyncAccountDeviationAsvClient;

class AsvSyncAccountDeviationClientTest extends TestCase
{
    public function testSyncDeviationSuccess()
    {

        $payload = [
            'account_id' => 'K0HiKkEpCud6Dv',
            'mode' => 'live',
            'mock' => false,
            'metadata' => [
                "async_job_name" => "RZP_Jobs_EsSync",
                "request_id" => "aa9de515d75be35083e7b0a9d568a77c",
                "route" => "none",
                "rzp_internal_app_name" => "none",
                "task_id" => "aa9de515d75be35083e7b0a9d568a77c"
            ]
        ];

        $detailsDiff = new StringList();
        $detailsDiff->setValue(array("ContactDetails.Policy: Dilip Kr Chauhan != Dilip Kumar Chauhan"));
        $response = [
            "account_id" => "K0HiKkEpCud6Dv",
            "success" => true,
            "diff" => [
                "account_diff" => $detailsDiff,
                "details_diff" => new StringList([]),
                "document_diff" => new StringList([]),
                "stakeholder_diff" => new StringList([])
            ]
        ];
        $responseProto = new syncDeviationV1\SyncAccountDeviationResponse($response);
        $expected = $responseProto;

        $mockSyncDeviationAsvClient = $this->createMock(SyncDeviationV1\SyncDeviationAPIClient::class);
        $mockSyncDeviationAsvClient->method('SyncAccountDeviation')->willReturn($responseProto);

        $syncDeviation = new SyncAccountDeviationAsvClient($mockSyncDeviationAsvClient);

        $actual = $syncDeviation->syncAccountDeviation($payload);

        $this->assertEquals($actual->serializeToJsonString(), $expected->serializeToJsonString(), 'testSyncDeviationSuccess');
    }

    public function testSyncDeviationFailureUnauthenticated()
    {

        $payload = [
            'account_id' => 'K0HiKkEpCud6Dv',
            'mode' => 'live',
            'mock' => false,
            'metadata' => [
                "async_job_name" => "RZP_Jobs_EsSync",
                "request_id" => "aa9de515d75be35083e7b0a9d568a77c",
                "route" => "none",
                "rzp_internal_app_name" => "none",
                "task_id" => "aa9de515d75be35083e7b0a9d568a77c"
            ]
        ];

        $mockError = new syncDeviationV1\TwirpError('unauthenticated', 'invalid username/password for authentication');
        $exptectError = new IntegrationException('Could not receive proper response from Account service');

        $mockSyncDeviationAsvClient = $this->createMock(SyncDeviationV1\SyncDeviationAPIClient::class);
        $mockSyncDeviationAsvClient->method('SyncAccountDeviation')->will($this->throwException($mockError));

        $syncDeviation = new SyncAccountDeviationAsvClient($mockSyncDeviationAsvClient);

        try {
            $syncDeviation->syncAccountDeviation($payload);
            $this->assertFalse(true, 'testSyncDeviationFailureUnauthenticated');
        } catch (IntegrationException $e) {
            $this->assertEquals($e->getCode(), $exptectError->getCode(), 'testSyncDeviationFailureUnauthenticated');
            $this->assertEquals($e->getError(), $exptectError->getError(), 'testSyncDeviationFailureUnauthenticated');
        }
    }

    public function testSyncDeviationFailureValidationError()
    {

        $payload = [
            'account_id' => '',
            'mode' => 'live',
            'mock' => false,
            'metadata' => [
                "async_job_name" => "RZP_Jobs_EsSync",
                "request_id" => "aa9de515d75be35083e7b0a9d568a77c",
                "route" => "none",
                "rzp_internal_app_name" => "none",
                "task_id" => "aa9de515d75be35083e7b0a9d568a77c"
            ]
        ];

        $expectedError = new ValidationException(' some of account_id, mode, mock or metadata fields are not present or contains invalid values');

        $syncDeviation = new SyncAccountDeviationAsvClient();

        try {
            $syncDeviation->syncAccountDeviation($payload);
            $this->assertFalse(true, 'testSyncDeviationFailureValidationError');
        } catch (ValidationException $e) {
            $this->assertEquals($e->getMessage(), $expectedError->getMessage(), 'testSyncDeviationValidationError');
        }
    }
}
