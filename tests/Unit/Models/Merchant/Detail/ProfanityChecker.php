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

    function testProfanityCheckerSiteModeration()
    {
        $this->profanityCheckerModerationHelper('site');
    }

    // ------------------ Helpers -------------------- //
    function profanityCheckerModerationHelper(string $moderationType)
    {
        $entityType = 'test_entity';

        $entityId = '1234';

        $caller = 'test_caller';

        $merchantId = '100000Razorpay';

        $expectedRequestPayload = [
            'MerchantId'     => $merchantId,
            'ModerationType' => $moderationType,
            'EntityType'     => $entityType,
            'EntityId'       => $entityId,
            'Caller'         => $caller,
        ];

        $target = '';

        $depth = 0;

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
        else if ($moderationType === 'site')
        {
            $target = 'https://testimages.com';

            $depth = 2;

            $expectedRequestPayload['URL'] = $target;

            $expectedRequestPayload['Depth'] = $depth;
        }

        $mrsMock = $this->getMrsRequestMock($expectedRequestPayload);

        $res = $mrsMock->enqueueProfanityCheckerRequest($merchantId, $moderationType, $entityType, $entityId, $target, $depth, $caller);

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
