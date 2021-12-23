<?php


namespace RZP\Tests\Functional\Growth;


use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksGrowth;

class GrowthTest extends TestCase
{
    use MocksGrowth;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/GrowthTestData.php';

        parent::setUp();
    }

    public function testGrowthResponse()
    {
        $input = [
            "channel_id"  => "randomChannel",
            "merchant_id" => "randomMerchant",
            "asset"       => "randomAsset",
        ];

        $output = [
            "channel_id"=> "HTdu8cC7FJEIHC",
            "asset_data"=> [
                [
                    "tracking_data"=> [
                        "campaign"=> "stage QA aman - 15",
                        "campaign_description"=> "QA campaign don't use this campaign",
                        "sub_campaign"=> "stage QA check aman sc - 15",
                        "sub_campaign_description"=> "QA sub campaign do not use"
                    ],
                    "templates"=> [
                        [
                            "id"=> "Hgc1LHDtLcpJLY",
                            "channel_id"=> "HTdu8cC7FJEIHC",
                            "asset"=> "ANNOUNCEMENT",
                            "name"=> "Subscription Buttons For QA",
                            "description"=> "Now collect one time and subscription payments with a single button on your website",
                            "data"=> [
                                "buttons"=> [
                                    [
                                        "label"=> "Try Now",
                                        "type"=> "button",
                                        "url"=> "/subscriptions"
                                    ],
                                    [
                                        "id"=> "announcement-details-l2",
                                        "label"=> "Read More",
                                        "type"=> "primary-inverted",
                                        "url"=> "/announcements/whats-new-subs-btn-jan2021/"
                                    ]
                                ],
                                "description"=> "Now collect one time and subscription payments with a single button on your website",
                                "icon"=> "https=>//cdn.razorpay.com/static/assets/notifs/payment-button.svg",
                                "l2_content"=> [
                                    "buttons"=> [
                                        [
                                            "label"=> "Try Now",
                                            "type"=> "button",
                                            "url"=> "/subscriptions"
                                        ],
                                        [
                                            "label"=> "Read More",
                                            "type"=> "primary-inverted",
                                            "url"=> "https=>//razorpay.com/docs/payment-button/subscription-buttons/"
                                        ]
                                    ],
                                    "content"=> "Introducing Subscriptions on Payment Button"
                                ],
                                "title"=> "Introducing Subscriptions",
                                "video_url"=> "https=>//www.youtube.com/embed/RlUVGyMs8B8"
                            ],
                            "status"=> "DRAFT",
                            "created_by"=> "Aman - QA",
                            "updated_by"=> "Aman - QA",
                            "created_at"=> "2021-08-03T05:37:33Z",
                            "updated_at"=> "2021-08-03T05:37:33Z"
                        ],
                        [
                            "id"=> "Hh32TmGYcq55Zs",
                            "channel_id"=> "HTdu8cC7FJEIHC",
                            "asset"=> "ANNOUNCEMENT",
                            "name"=> "Subscription Buttons For QA -15 2nd template",
                            "description"=> "Now collect one time and subscription payments with a single button on your website -15",
                            "data"=> [
                                "buttons"=> [
                                    [
                                        "label"=> "Try Now",
                                        "type"=> "button",
                                        "url"=> "/subscriptions"
                                    ],
                                    [
                                        "id"=> "announcement-details-l2",
                                        "label"=> "Read More",
                                        "type"=> "primary-inverted",
                                        "url"=> "/announcements/whats-new-subs-btn-jan2021/"
                                    ]
                                ],
                                "description"=> "Now collect one time and subscription payments with a single button on your website",
                                "icon"=> "https=>//cdn.razorpay.com/static/assets/notifs/payment-button.svg",
                                "l2_content"=> [
                                    "buttons"=> [
                                        [
                                            "label"=> "Try Now",
                                            "type"=> "button",
                                            "url"=> "/subscriptions"
                                        ],
                                        [
                                            "label"=> "Read More",
                                            "type"=> "primary-inverted",
                                            "url"=> "https=>//razorpay.com/docs/payment-button/subscription-buttons/"
                                        ]
                                    ],
                                    "content"=> "Introducing Subscriptions on Payment Button"
                                ],
                                "title"=> "Introducing Subscriptions",
                                "video_url"=> "https=>//www.youtube.com/embed/RlUVGyMs8B8"
                            ],
                            "status"=> "DRAFT",
                            "created_by"=> "Aman - QA - 15",
                            "updated_by"=> "Aman - QA - 15",
                            "created_at"=> "2021-08-04T08:03:21Z",
                            "updated_at"=> "2021-08-04T08:03:21Z"
                        ]
                    ]
                ]
            ]
        ];

        $this->ba->proxyAuth();

        $this->mockGrowthTreatment($input, $output, 'getAssetDetails');

        $this->startTest();
    }

    public function testEnableDowntimeNotificationForXDashboard()
    {
        $input = [
            "template" =>  [
                "id"            => "124",
                "asset"         => "X_TOP_BANNER",
                "data"          => [
                    "description"   => "test 123",
                    "isCloseable"   => false,
                    "priority"      => 1,
                    "bgColour"      => "rgba(243, 105, 105, 0.54)",
                    "bgImage"       => "",
                    "textColor"     => "rgba(255, 255, 255, 0.87)",
                    "start_at"      => "123456",
                    "end_at"        => "456789",
                ]
            ],
            "subcampaign" => [
                "sub_campaign_id" => "IBcO8lQk6hqy0W",
                "action" => "ACTIVATED"
            ]
        ];

        $output = [
            "status_code" => "200",
        ];

        $this->mockGrowthTreatment($input, $output, 'editTemplateAndEnableDowntimeNotificationForXDashboard');

        $this->ba->adminAuth();

        $this->startTest();
    }
    
    public function testFilterAndSyncEventsFromPinot()
    {
        $this->mockGrowthTreatment([], [], 'filterAndSyncEventsFromPinot');
        
        $this->ba->cronAuth();
        
        $this->startTest();
    }

}
