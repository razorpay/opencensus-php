<?php

namespace Functional\FreshdeskTicket;

use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Admin\FreshchatReport;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class FreshchatTest extends TestCase
{

    use RequestResponseFlowTrait;

    /**
     * @var Mockery\Mock
     */
    protected $freshchatClientMock;

    protected $cache;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FreshchatTestData.php';

        parent::setUp();

        $this->flushCache();

        $this->setUpFreshchatClientMock();

        $this->ba->cronAuth();

        $dt = Carbon::createFromTimestamp(1600000000);

        Carbon::setTestNow($dt);
    }

    protected function setUpFreshchatClientMock(): void
    {
        $this->freshchatClientMock = Mockery::mock('RZP\Services\FreshchatClient', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app['freshchat_client'] = $this->freshchatClientMock;
    }

    protected function expectFreshchatRequestAndRespondWith($expectedPath, $expectedMethod, $expectedContent, $respondWith = [], $times = 1)
    {
        $this->freshchatClientMock
            ->shouldReceive('sendRequest')
            ->times($times)
            ->with(Mockery::on(function($actualPath) use ($expectedPath)
            {
                return $expectedPath === $actualPath;
            }),  Mockery::on(function($actualMethod) use ($expectedMethod)
            {
                return strtolower($actualMethod) === strtolower($expectedMethod);
            }),
                Mockery::on(function($actualContent) use ($expectedContent)
            {return $expectedContent === $actualContent;
            }))
            ->andReturnUsing(function () use ($respondWith)
            {
                $response = new \Requests_Response;

                $response->body = json_encode($respondWith);

                $response->status_code = 202;

                return $response;
            });
    }

    /**
     * Submits a request to extract a report. The response object contains a unique identifier of the
     * report and a reference to the requested report.
     * Asserts unique identifier is stored in redis for retrieval of the report later
     */
    public function testExtractReport()
    {
        $this->expectFreshchatRequestAndRespondWith('reports/raw', 'post',
        [
            'start'     => '2020-09-12T12:26:40Z',
            'end'       => '2020-09-13T12:26:40Z',
            'event'     => 'Conversation-Agent-Assigned',
            'format'    => 'csv',
        ],
        [
            'id'    => 'random_report_retrieval_id',
            'link'  => [
                'rel'   => 'extracts',
                'href'  => '/reports/raw/random_report_retrieval_id'
            ]
        ]);

        $this->startTest();

        $actualData = $this->app['cache']->get('freshchat_extract_report_metadata');

        $expectedData = [
            'start'         => '2020-09-12T12:26:40Z',
            'end'           => '2020-09-13T12:26:40Z',
            'event'         => 'Conversation-Agent-Assigned',
            'report_id'     => 'random_report_retrieval_id',
            'format'        => 'csv',
        ];

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * Retrieves report that was created earlier and placed in cache(see testExtractReport)
     */
    public function testRetrieveReport()
    {
        Mail::fake();

        $this->expectFreshchatRequestAndRespondWith('reports/raw/random_report_retrieval_id', 'get',
            [],
            [
                'id'       => '10946812-e675-4d4f-a42f-dbcb58c12727',
                'status'   => 'COMPLETED',
                'links'    => [
                    [
                        'link'   => [
                            'rel'  => 'extraction',
                            'href' => 'https://some-s3-link',
                        ],
                        'from'   => '2020-09-12T12:26:40Z',
                        'to'     => '2020-09-12T29:59:59Z',
                        'status' => 'COMPLETED',
                    ],
                    [
                        'link'   => [
                            'rel'  => 'extraction',
                            'href' => 'https://some-s3-link',
                        ],
                        'from'   => '2021-01-05T00:00:00',
                        'to'     => '2021-01-05T12:26:59',
                        'status' => 'COMPLETED',
                    ],
                ],
            ]);

        $this->freshchatClientMock
            ->shouldReceive('getLinkContent')
            ->andReturn('random content');

        $this->app['cache']->put('freshchat_extract_report_metadata', [
            'start'         => '2020-09-12T12:12:40Z',
            'end'           => '2020-09-13T12:12:40Z',
            'event'         => 'Conversation-Agent-Assigned',
            'report_id'     => 'random_report_retrieval_id',
            'format'        => 'csv',
        ], 1000);

        $this->startTest();

        Mail::assertSent(FreshchatReport::class, function (FreshchatReport $mail)
        {
            $this->assertEquals('Freshchat chat dump from 2020-09-12T12:12:40Z to 2020-09-13T12:12:40Z (UTC)', $mail->subject);

            $this->assertCount(1, $mail->to);

            $this->assertArraySelectiveEquals([
                [
                    'address' => 'chat-reports@razorpay.com',
                ]
            ], $mail->to);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }
}
