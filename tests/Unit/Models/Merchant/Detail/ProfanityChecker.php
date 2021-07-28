<?php


namespace Unit\Models\Merchant\Detail;


use RZP\Services\MerchantRiskClient;
use RZP\Tests\Functional\TestCase;

class ProfanityChecker extends TestCase
{
    function testProfanityCheckerTextModeration()
    {
        $this->profanityCheckerModerationHelper('text');
    }

    function testProfanityCheckerImageModeration()
    {
        $this->profanityCheckerModerationHelper('image');
    }

    // ------------------ Helpers -------------------- //
    function profanityCheckerModerationHelper(string $moderationType)
    {
        $entityType = 'test_entity';

        $entityId = '1234';

        $caller = 'test_caller';

        $expectedRequestPayload = [
            'ModerationType' => $moderationType,
            'EntityType'     => $entityType,
            'EntityId'       => $entityId,
            'Caller'         => $caller,
        ];

        $target = '';

        if ($moderationType === 'text')
        {
            $target = 'test';

            $expectedRequestPayload['Text'] = $target;
        }
        else if ($moderationType === 'image')
        {
            $target = 'https://testimages.com/1.jpg';

            $expectedRequestPayload['URL'] = $target;
        }

        $mrsMock = $this->getMrsRequestMock($expectedRequestPayload);

        $res = $mrsMock->enqueueProfanityCheckerRequest($moderationType, $entityType, $entityId, $target, $caller);

        $this->assertEquals(true, $res['success']);
    }

    function getMrsRequestMock(array $expectedPayload)
    {
        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->onlyMethods(['requestAndGetParsedBody'])
            ->getMock();


        $mockMR->expects($this->once())
            ->method('requestAndGetParsedBody')
            ->with($this->equalTo(MerchantRiskClient::ENQUEUE_PROFANITY_CHECKER), $this->equalTo($expectedPayload))
            ->willReturn([
                'success' => true,
            ]);

        return $mockMR;
    }
}
