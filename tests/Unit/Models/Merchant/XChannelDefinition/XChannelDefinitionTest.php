<?php

namespace Unit\Models\Merchant\XChannelDefinition;

use Mockery;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Attribute;
use RZP\Models\Merchant\XChannelDefinition\Channels;
use RZP\Models\Merchant\XChannelDefinition\Constants;
use RZP\Models\Merchant\XChannelDefinition\Service;
use Tests\Unit\TestCase;

class XChannelDefinitionTest extends TestCase
{
    protected $xChannelDefinitionService;

    /**
     * Mock of \RZP\Models\Merchant\Attribute\Core
     *
     * @var Mockery\MockInterface
     */
    protected $attributeCoreMock;

    /**
     * Mock of \RZP\Models\Merchant\Attribute\Service
     *
     * @var Mockery\MockInterface
     */
    protected $attributeServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestDependencyMocks();

        $this->xChannelDefinitionService = new Service($this->attributeCoreMock, $this->attributeServiceMock);
    }

    public function testGetChannelAndSubchannel()
    {
        $testDataset = [
            [
                'input'          => [
                    'website'          => 'xproduct.razorpay.com/current-account-for-startups/',
                    'final_utm_source' => 'blog',
                    'final_utm_medium' => 'cta',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::DIRECTX_CA,
                    Constants::SUBCHANNEL => Channels::SUB_CHANNEL_BLOG,
                ]
            ],
            [
                'input'          => [
                    'website' => 'razorpay.com/',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::PG,
                    Constants::SUBCHANNEL => Channels::PG_DIRECT_RAZORPAY,
                ]
            ],
            [
                'input'          => [
                    'final_utm_source'   => 'pg',
                    'final_utm_medium'   => 'dashboard',
                    'final_utm_campaign' => 'app_switcher',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::PG,
                    Constants::SUBCHANNEL => Channels::PG_APP_SWITCHER,
                ]
            ],
            [
                'input'          => [
                    'website'          => 'razorpay.com/current-account-for-startups/',
                    'final_utm_source' => 'blog',
                    'final_utm_medium' => 'cta',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::PG,
                    Constants::SUBCHANNEL => Channels::SUB_CHANNEL_BLOG,
                ]
            ],
            [
                'input'          => [
                    'final_utm_source' => 'email',
                    'website'          => 'razorpay.com/x/corporate-cards/',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::DIRECTX_CAPITAL,
                    Constants::SUBCHANNEL => Channels::SUB_CHANNEL_OTHERS_MARKETING,
                ]
            ],
            [
                'input'          => [
                    'website' => 'x.razorpay.com/auth/signup',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::DIRECT_SIGNUPS,
                    Constants::SUBCHANNEL => Channels::UNMAPPED,
                ]
            ],
            [
                'input'          => [
                    'website' => 'x.razorpay.com/auth/signup',
                    'final_utm_source' => 'google',
                    'final_utm_medium' => 'cpc',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::DIRECT_SIGNUPS,
                    Constants::SUBCHANNEL => Channels::SUB_CHANNEL_PERFORMANCE,
                ]
            ],
            [
                'input'          => [
                    'website' => 'razorpay.com/learn/',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::OTHERS,
                    Constants::SUBCHANNEL => Channels::UNMAPPED,
                ]
            ],
            [
                'input'          => [
                    'website' => 'razorpay.com/learn/',
                    'final_utm_source' => 'google',
                    'final_utm_medium' => 'cpc',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::OTHERS,
                    Constants::SUBCHANNEL => Channels::SUB_CHANNEL_PERFORMANCE,
                ]
            ],
            [
                'input'          => [
                    'website'          => 'razorpay.com/x/payouts/',
                    'final_utm_source' => 'google',
                    'final_utm_medium' => 'cpc',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::DIRECTX_APPS,
                    Constants::SUBCHANNEL => Channels::SUB_CHANNEL_PERFORMANCE,
                ]
            ],
            [
                'input'          => [
                    'final_utm_source' => 'google',
                    'final_utm_medium' => 'cpc',
                ],
                'expectedResult' => [
                    Constants::CHANNEL    => Channels::UNMAPPED,
                    Constants::SUBCHANNEL => Channels::SUB_CHANNEL_PERFORMANCE,
                ]
            ],

        ];

        foreach ($testDataset as $testData)
        {
            $channelDetails = $this->xChannelDefinitionService->getChannelAndSubchannel($testData['input']);

            $this->assertEquals($testData['expectedResult'][Constants::CHANNEL], $channelDetails[Constants::CHANNEL]);
            $this->assertEquals($testData['expectedResult'][Constants::SUBCHANNEL], $channelDetails[Constants::SUBCHANNEL]);
        }
    }

    public function testStoreChannelDetailsForFirstTime()
    {
        $this->attributeCoreMock
            ->shouldReceive('fetchKeyValues')
            ->withArgs([Mockery::any(), Product::BANKING, Attribute\Group::X_MERCHANT_PREFERENCES, [Attribute\Type::X_SIGNUP_PLATFORM]])
            ->once()
            ->andReturn(new PublicCollection());

        $this->attributeCoreMock
            ->shouldReceive('fetchKeyValues')
            ->withArgs([Mockery::any(), Product::BANKING, Attribute\Group::X_SIGNUP, [Attribute\Type::CHANNEL]])
            ->once()
            ->andReturn(new PublicCollection());

        $expectedData = [
            [
                Attribute\Entity::TYPE  => Attribute\Type::CHANNEL,
                Attribute\Entity::VALUE => Channels::DIRECTX_APPS,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::SUBCHANNEL,
                Attribute\Entity::VALUE => Channels::SUB_CHANNEL_PERFORMANCE,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_SOURCE,
                Attribute\Entity::VALUE => 'google',
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_MEDIUM,
                Attribute\Entity::VALUE => 'cpc',
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_CAMPAIGN,
                Attribute\Entity::VALUE => Constants::UNKNOWN,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::REF_WEBSITE,
                Attribute\Entity::VALUE => 'razorpay.com/x/payouts/',
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::LAST_CLICK_SOURCE_CATEGORY,
                Attribute\Entity::VALUE => Constants::LCS_CATEGORY_PERFORMANCE,
            ],
        ];

        $this->attributeServiceMock
            ->shouldReceive('upsert')
            ->withArgs(function($group, $data, $product, $merchant) use ($expectedData) {
                sort($data);
                sort($expectedData);

                $this->assertEquals(Attribute\Group::X_SIGNUP, $group);
                $this->assertEquals(Product::BANKING, $product);
                $this->assertEquals($expectedData, $data);

                return true;
            })
            ->once()
            ->andReturn();

        $this->basicAuthMock
            ->shouldReceive('getMode')
            ->andReturn(Mode::TEST);

        $this->basicAuthMock
            ->shouldReceive('setModeAndDbConnection')
            ->withAnyArgs()
            ->andReturns();

        $merchant  = Mockery::mock('RZP\Models\Merchant\Entity');
        $utmParams = [
            'website'          => 'razorpay.com/x/payouts/',
            'final_utm_source' => 'google',
            'final_utm_medium' => 'cpc',
        ];

        $this->xChannelDefinitionService->storeChannelDetails($merchant, $utmParams);
    }

    public function testStoreChannelDetailsForMobileChannel()
    {
        $this->attributeCoreMock
            ->shouldReceive('fetchKeyValues')
            ->withArgs([Mockery::any(), Product::BANKING, Attribute\Group::X_MERCHANT_PREFERENCES, [Attribute\Type::X_SIGNUP_PLATFORM]])
            ->once()
            ->andReturn(
                new PublicCollection(
                    [
                        [
                            Attribute\Entity::PRODUCT => Product::BANKING,
                            Attribute\Entity::GROUP   => Attribute\Group::X_MERCHANT_PREFERENCES,
                            Attribute\Entity::TYPE    => Attribute\Type::X_SIGNUP_PLATFORM,
                            Attribute\Entity::VALUE   => Constants::X_MOBILE_APP,
                        ]
                    ])
            );

        $this->attributeCoreMock
            ->shouldReceive('fetchKeyValues')
            ->withArgs([Mockery::any(), Product::BANKING, Attribute\Group::X_SIGNUP, [Attribute\Type::CHANNEL]])
            ->once()
            ->andReturn(
                new PublicCollection(
                    [
                        [
                            Attribute\Entity::PRODUCT => Product::BANKING,
                            Attribute\Entity::GROUP   => Attribute\Group::X_SIGNUP,
                            Attribute\Entity::TYPE    => Channels::DIRECTX_CAPITAL,
                            Attribute\Entity::VALUE   => Channels::SUB_CHANNEL_BLOG,
                        ]
                    ])
            );

        $expectedData = [
            [
                Attribute\Entity::TYPE  => Attribute\Type::CHANNEL,
                Attribute\Entity::VALUE => Channels::MOBILE_APP_SIGNUPS,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::SUBCHANNEL,
                Attribute\Entity::VALUE => Channels::MOBILE_APP_SIGNUPS_DIRECT,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_SOURCE,
                Attribute\Entity::VALUE => 'google',
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_MEDIUM,
                Attribute\Entity::VALUE => 'cpc',
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_CAMPAIGN,
                Attribute\Entity::VALUE => Constants::UNKNOWN,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::REF_WEBSITE,
                Attribute\Entity::VALUE => 'razorpay.com/x/payouts/',
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::LAST_CLICK_SOURCE_CATEGORY,
                Attribute\Entity::VALUE => Constants::LCS_CATEGORY_PERFORMANCE,
            ],
        ];

        $this->attributeServiceMock
            ->shouldReceive('upsert')
            ->withArgs(function($group, $data, $product, $merchant) use ($expectedData) {
                sort($data);
                sort($expectedData);

                $this->assertEquals(Attribute\Group::X_SIGNUP, $group);
                $this->assertEquals(Product::BANKING, $product);
                $this->assertEquals($expectedData, $data);

                return true;
            })
            ->once()
            ->andReturn();

        $this->basicAuthMock
            ->shouldReceive('getMode')
            ->andReturn(Mode::TEST);

        $this->basicAuthMock
            ->shouldReceive('setModeAndDbConnection')
            ->withAnyArgs()
            ->andReturns();

        $merchant  = Mockery::mock('RZP\Models\Merchant\Entity');
        $utmParams = [
            'website'          => 'razorpay.com/x/payouts/',
            'final_utm_source' => 'google',
            'final_utm_medium' => 'cpc',
        ];

        $this->xChannelDefinitionService->storeChannelDetails($merchant, $utmParams);
    }

    public function testStoreChannelDetailsForPGMerchantNoUTMParams()
    {
        $this->attributeCoreMock
            ->shouldReceive('fetchKeyValues')
            ->withArgs([Mockery::any(), Product::BANKING, Attribute\Group::X_MERCHANT_PREFERENCES, [Attribute\Type::X_SIGNUP_PLATFORM]])
            ->once()
            ->andReturn(new PublicCollection());

        $this->attributeCoreMock
            ->shouldReceive('fetchKeyValues')
            ->withArgs([Mockery::any(), Product::BANKING, Attribute\Group::X_SIGNUP, [Attribute\Type::CHANNEL]])
            ->once()
            ->andReturn(new PublicCollection());

        $expectedData = [
            [
                Attribute\Entity::TYPE  => Attribute\Type::CHANNEL,
                Attribute\Entity::VALUE => Channels::PG,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::SUBCHANNEL,
                Attribute\Entity::VALUE => Channels::UNMAPPED,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_SOURCE,
                Attribute\Entity::VALUE => Constants::UNKNOWN,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_MEDIUM,
                Attribute\Entity::VALUE => Constants::UNKNOWN,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::FINAL_UTM_CAMPAIGN,
                Attribute\Entity::VALUE => Constants::UNKNOWN,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::REF_WEBSITE,
                Attribute\Entity::VALUE => Constants::UNKNOWN,
            ],
            [
                Attribute\Entity::TYPE  => Attribute\Type::LAST_CLICK_SOURCE_CATEGORY,
                Attribute\Entity::VALUE => 'Unknown',
            ],
        ];

        $this->attributeServiceMock
            ->shouldReceive('upsert')
            ->withArgs(function($group, $data, $product, $merchant) use ($expectedData) {
                sort($data);
                sort($expectedData);

                $this->assertEquals(Attribute\Group::X_SIGNUP, $group);
                $this->assertEquals(Product::BANKING, $product);
                $this->assertEquals($expectedData, $data);

                return true;
            })
            ->once()
            ->andReturn();

        $this->basicAuthMock
            ->shouldReceive('getMode')
            ->andReturn(Mode::TEST);

        $this->basicAuthMock
            ->shouldReceive('setModeAndDbConnection')
            ->withAnyArgs()
            ->andReturns();

        $merchant  = Mockery::mock('RZP\Models\Merchant\Entity');

        $merchant->shouldReceive('getSignupSource')->andReturn('primary');

        $this->xChannelDefinitionService->storeChannelDetails($merchant, []);
    }

    private function createTestDependencyMocks()
    {
        $this->attributeCoreMock = Mockery::mock('RZP\Models\Merchant\Attribute\Core');

        $this->attributeServiceMock = Mockery::mock('RZP\Models\Merchant\Attribute\Service');
    }
}
