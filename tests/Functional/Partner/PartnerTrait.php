<?php

namespace RZP\Tests\Functional\Partner;

use Mockery;
use RZP\Models\Merchant;
use RZP\Constants\Mode;
use RZP\Models\User\Role;
use WpOrg\Requests\Response;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\Feature\Constants as FName;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Services\Mock\LOSService as MockLOSService;
use RZP\Tests\Functional\Fixtures\Entity\Org as Org;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Tests\Functional\Fixtures\Entity\Base as BaseFixture;

trait PartnerTrait
{
    use OAuthTrait;

    public function mockCapitalPartnershipSplitzExperiment(): void
    {
        $input = [
            "experiment_id" => "L0rynez0HhIXHb",
            "id" => self::DEFAULT_MERCHANT_ID,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];
        $this->mockSplitzTreatment($input, $output);
    }

    public function mockCreateApplicationRequestOnLOSService($mockLOSService): void
    {
        $mockLOSService->shouldReceive('sendRequest')
                       ->atLeast()
                       ->once()
                       ->with(
                           MerchantConstants::CREATE_CAPITAL_APPLICATION_LOS_URL,
                           Mockery::type('array'),
                           Mockery::type('array')
                       );

        $mockLOSService->shouldReceive('parseResponse')->times(1);
    }

    public function mockGetProductsRequestOnLOSService($mockLOSService): void
    {
        $mockLOSService->shouldReceive('sendRequest')
                       ->atLeast()
                       ->once()
                       ->with(
                           MerchantConstants::GET_PRODUCTS_LOS_URL,
                           Mockery::type('array'),
                           Mockery::type('array')
                       )
                       ->andReturnUsing(
                           function() {
                               $resp              = new Response;
                               $resp->success     = true;
                               $resp->status_code = 200;
                               $resp->body        = json_encode(
                                   [
                                       "products" => MockLOSService::PRODUCT_LIST
                                   ]
                               );

                               return $resp;
                           }
                       );
    }

    public function mockGetNoApplicationRequestOnLOSService($mockLOSService): void
    {
        $mockLOSService->shouldReceive('sendRequest')
            ->atLeast()
            ->once()
            ->with(
                MerchantConstants::GET_CAPITAL_APPLICATIONS_URL,
                Mockery::type('array'),
                Mockery::type('array')
            )->andReturnUsing(
                function() {
                    $resp              = new Response;
                    $resp->success     = false;
                    $resp->status_code = 404;
                    $resp->body        = json_encode(
                        [
                            "msg"  => "record not found",
                            "code" => "not_found"
                        ]
                    );

                    return $resp;
                }
            );
    }

    public function mockGetApplicationRequestOnLOSService($mockLOSService): void
    {
        $payload = json_encode(["id" => "LfFGpg2vt6zh5E", "product_id" => "JsP6pHbeMKn10E", "state" => "STATE_CREATED"]);

        $mockLOSService->shouldReceive('sendRequest')
            ->atLeast()
            ->once()
            ->with(
                MerchantConstants::GET_CAPITAL_APPLICATIONS_URL,
                Mockery::type('array'),
                Mockery::type('array')
            )->andReturnUsing(
                function() use ($payload) {
                    $resp              = new Response;
                    $resp->success     = true;
                    $resp->status_code = 200;
                    $resp->body        = json_encode(
                        [
                            "applications"  => [
                                $payload
                            ]
                        ]
                    );

                    return $resp;
                }
            );
    }

    public function setUpPartnerMerchantAppAndGetClient(
        string $env = 'dev',
        array $attributes = [],
        string $partnerId = '10000000000000',
        string $partnerType = 'fully_managed')
    {
        $attributes = array_merge($attributes, ['merchant_id' => $partnerId, 'partner_type' => $partnerType]);

        $client = $this->createPartnerApplicationAndGetClientByEnv($env, $attributes);

        $this->fixtures->edit('merchant', $partnerId, ['partner_type' => $partnerType]);

        $this->fixtures->merchant->addFeatures(['partner']);

        return $client;
    }

    public function createPartnerAndApplication($partnerAttributes = [], $appAttributes = [])
    {
        $merchantId = $partnerAttributes['id'] ?? 'DefaultPartner';
        unset($partnerAttributes['id']);

        if (empty($partnerAttributes['partner_type']) === true)
        {
            $defaultPartnerAttributes = ['partner_type' => 'aggregator'];

            $partnerAttributes = array_merge($defaultPartnerAttributes, $partnerAttributes);

            $partnerType = 'aggregator';
        }
        else if ($partnerAttributes['partner_type'] === 'pure_platform')
        {
            $partnerType = 'pure_platform';
        }
        else
        {
            $partnerType = 'reseller';
        }

        $partner = $this->fixtures->merchant->createMerchantWithDetails(Org::RZP_ORG, $merchantId, $partnerAttributes);

        $this->fixtures->user->createUserMerchantMapping(
            [
                'merchant_id' => $merchantId,
                'user_id'     => User::MERCHANT_USER_ID,
                'role'        => 'owner',
            ]);

        $defaultAppAttributes = [
            'merchant_id' => $partner->getId(),
            'partner_type'=> $partnerType,
        ];

        $appAttributes  = array_merge($defaultAppAttributes, $appAttributes);

        $app = $this->fixtures->merchant->createDummyPartnerApp($appAttributes);

        return [$partner, $app];
    }

    public function createConfigForPartnerApp($appId, $submerchantId = null, $attributes = [])
    {
        if ($submerchantId === null)
        {
            $attributes[PartnerConfig\Entity::ENTITY_TYPE] = PartnerConfig\Constants::APPLICATION;
            $attributes[PartnerConfig\Entity::ENTITY_ID]   = $appId;

            $attributes[PartnerConfig\Entity::ORIGIN_TYPE] = null;
            $attributes[PartnerConfig\Entity::ORIGIN_ID]   = null;
        }
        else
        {
            $attributes[PartnerConfig\Entity::ENTITY_TYPE] = PartnerConfig\Constants::MERCHANT;
            $attributes[PartnerConfig\Entity::ENTITY_ID]   = $submerchantId;

            $attributes[PartnerConfig\Entity::ORIGIN_TYPE] = PartnerConfig\Constants::APPLICATION;
            $attributes[PartnerConfig\Entity::ORIGIN_ID]   = $appId;
        }

        $defaultAttributes = $this->getDefaultPartnerConfigAttributes();

        $attributes        = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->create('partner_config', $attributes);
    }

    protected function getDefaultPartnerConfigAttributes()
    {
        return [
            'commissions_enabled' => 1,
            'default_plan_id'     => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];
    }

    public function createSubMerchant($merchant, $app, $subMerchantAttributes = [], $accessMapAttributes = [])
    {
        $subMerchantId = $subMerchantAttributes['id'] ?? 'submerchantNum';
        unset($subMerchantAttributes['id']);

        $this->fixtures->merchant->createAccount($subMerchantId);

        $subMerchant = $this->fixtures->merchant->edit($subMerchantId, $subMerchantAttributes);

        $subMerchantDetails = [
            'merchant_id' => $subMerchant->getId(),
            'business_type' => 1,
            'business_category' => 'financial_services',
            'business_subcategory' => 'mutual_fund',
        ];

        $subMerchantDetails = $this->fixtures->merchant_detail->createMerchantDetail($subMerchantDetails);

        $this->fixtures->on('live')->merchant_detail->createSane($subMerchantDetails);
        $this->fixtures->on('test')->merchant_detail->createSane($subMerchantDetails);

        $accessMapData = [
            'entity_type'     => 'application',
            'entity_id'       => $app->getId(),
            'merchant_id'     => $subMerchant->getId(),
            'entity_owner_id' => $merchant->getId(),
        ];

        $accessMapData = array_merge($accessMapAttributes, $accessMapData);

        $accessMap = $this->fixtures->create('merchant_access_map', $accessMapData);

        return [$subMerchant, $accessMap];
    }

    public function createPartnerMerchantAndSubMerchant(string $partnerType)
    {
        $this->fixtures->merchant->createAccount(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->createAccount(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => $partnerType,
            ]
        );

        $this->createDefaultSubmerchantPricingPlan();

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'pricing_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            ]
        );

        $application = $this->createOAuthApplication(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                'id'          => Constants::DEFAULT_PLATFORM_APP_ID,
                'type'        => 'partner',
                'partner_type'=> $partnerType,
            ]
        );

        $accessMap = $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'entity_id'       => Constants::DEFAULT_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            ]
        );

        return [$application, $accessMap];
    }

    public function setPurePlatformContext(string $mode = 'live'): string
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $token = $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
                'mode' => $mode,
            ],
            $client);

        $this->ba->oauthBearerAuth($token->toString());

        return $token->toString();
    }

    public function createPurePlatFormMerchantAndSubMerchant()
    {
        $partner = $this->fixtures->merchant->createAccount(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->createAccount(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->user->createUserMerchantMapping(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                'user_id'     => User::MERCHANT_USER_ID,
                'role'        => 'owner',
            ]);

        $this->fixtures->merchant->activate(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->activate(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::PURE_PLATFORM,
            ]
        );

        $this->createDefaultSubmerchantPricingPlan();

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'pricing_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            ]
        );

        $application = $this->createOAuthApplication(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                'id'          => Constants::DEFAULT_PLATFORM_APP_ID,
                'partner_type' => Merchant\Constants::PURE_PLATFORM,
            ]
        );

        $accessMap = $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'entity_id'       => Constants::DEFAULT_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            ]
        );

        return [$application, $accessMap, $partner];
    }

    public function createAggregatorMalaysianMerchantAndSubMerchant()
    {
        $partner = $this->fixtures->merchant->createAccount(Constants::DEFAULT_PLATFORM_MERCHANT_ID, true, 'MY');
        $this->fixtures->merchant->createAccount(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, true, 'MY');

        $this->fixtures->user->createUserMerchantMapping(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                'user_id'     => User::MERCHANT_USER_ID,
                'role'        => 'owner',
            ]);

        $this->fixtures->merchant->activate(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->fixtures->merchant->activate(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $this->createDefaultSubmerchantPricingPlan();

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'pricing_plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
            ]
        );

        $application = $this->createOAuthApplication(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                'id'          => Constants::DEFAULT_PLATFORM_APP_ID,
                'partner_type' => Merchant\Constants::AGGREGATOR,
            ]
        );

        $accessMap = $this->fixtures->create(
            'merchant_access_map',
            [
                'merchant_id'     => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'entity_id'       => Constants::DEFAULT_PLATFORM_APP_ID,
                'entity_type'     => 'application',
                'entity_owner_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            ]
        );

        return [$application, $accessMap, $partner];
    }

    public function setSubmerchantPublicAuth($merchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID)
    {
        $key = $this->fixtures->create('key', ['merchant_id' => $merchantId]);

        $key = $key->getKey();

        $this->ba->publicAuth('rzp_test_' . $key);
    }

    public function setSubmerchantPrivateAuth($merchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID)
    {
        $key = $this->fixtures->create('key', ['merchant_id' => $merchantId]);

        $key = $key->getKey();

        $this->ba->privateAuth('rzp_test_' . $key);
    }

    public function createImplicitPricingPlan($planId = Constants::DEFAULT_IMPLICIT_PRICING_PLAN)
    {
        $this->fixtures->create('pricing:implicit_partner_pricing_plan', [
            'plan_id' => $planId,
            'type'    => 'pricing',
        ]);
    }

    public function createImplicitPricingPlanWithOrgId($planId = Constants::DEFAULT_IMPLICIT_PRICING_PLAN, $orgId = Org::RZP_ORG)
    {
        $this->fixtures->create('pricing:implicit_partner_pricing_plan', [
            'plan_id' => $planId,
            'type'    => 'pricing',
            'org_id'  => $orgId
        ]);
    }

    public function createDefaultSubmerchantPricingPlan($planId = Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN)
    {
        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => $planId,
            'type'    => 'pricing',
        ]);
    }

    public function setPostpaidFeeModel(string $submerchantId)
    {
        $this->fixtures->merchant->edit($submerchantId, ['fee_model' => 'postpaid']);
    }

    /**
     * The method will simulate exact environment as of Partner Auth Merchant.
     * 1. Load the oAuth Factories required for oAuth Models
     * 2. Setup the partner account for Test Merchant
     * 3. Remove the key for the test merchant.
     *
     * @return array
     */
    protected function setUpPartnerAuthForPayment()
    {
        $partnerId = '100000Razorpay';

        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);

        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
            ]
        );

        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() - 20]);

        return [$client->getId(), 'acc_' . $submerchantId];
    }

    public function setUpPartnerAuthAndGetSubMerchantId($activated = true, $category = 4722)
    {
        if ($activated === true)
        {
            $subMerchant = $this->fixtures->create('merchant', ['activated' => 1, 'category' => $category]);
        }
        else
        {
            $subMerchant = $this->fixtures->create('merchant', ['category' => $category]);
        }

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], '10000000000000','aggregator');

        $merchantDetailAttribute = [
            "merchant_id"       => $subMerchantId,
            "business_type"     => 2,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'entity_id'       => $client->getApplicationId(),
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        (new BaseFixture)->createEntity('merchant_detail', [
            'merchant_id' => '10000000000000',
            'submitted'   => true,
            'business_registered_state' => 'KA',
            'locked'      => true
        ]);

        $this->ba->partnerAuth($subMerchantId, 'rzp_test_partner_' . $client->getId(), $client->getSecret());

        return $subMerchantId;
    }

    public function setUpPartnerAuthAndGetSubMerchantIdWithClient($activated = true, $category = 4722)
    {
        if ($activated === true)
        {
            $subMerchant = $this->fixtures->create('merchant', ['activated' => 1, 'category' => $category]);
        }
        else
        {
            $subMerchant = $this->fixtures->create('merchant', ['category' => $category]);
        }

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], '10000000000000','aggregator');

        $merchantDetailAttribute = [
            "merchant_id"       => $subMerchantId,
            "business_type"     => 2,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'entity_id'       => $client->getApplicationId(),
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        (new BaseFixture)->createEntity('merchant_detail', [
            'merchant_id' => '10000000000000',
            'submitted'   => true,
            'business_registered_state' => 'KA',
            'locked'      => true
        ]);

        $this->ba->partnerAuth($subMerchantId, 'rzp_test_partner_' . $client->getId(), $client->getSecret());

        return [$subMerchantId,$client];
    }


    public function markMerchantAsNonPurePlatformPartner(string $merchantId, string $partnerType)
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $merchantId, $partnerType);

        $this->fixtures->merchant->edit($merchantId, ['partner_type' => $partnerType]);

        return $client;
    }

    public function setUpPartnerWithKycHandled()
    {
        $features = [
            FName::NO_COMM_WITH_SUBMERCHANTS,
            FName::KYC_HANDLED_BY_PARTNER,
            FName::SUBMERCHANT_ONBOARDING,
        ];

        $this->fixtures->merchant->addFeatures($features);

        return $this->setUpNonPurePlatformPartner();
    }

    public function setUpPartnerWithKycNotHandled()
    {
        $features = [
            FName::NO_COMM_WITH_SUBMERCHANTS,
            FName::SUBMERCHANT_ONBOARDING,
            FName::FORCE_GREYLIST_INTERNAT,
        ];

        $this->fixtures->merchant->addFeatures($features);

        return $this->setUpNonPurePlatformPartner();
    }

    public function setUpNonPurePlatformPartner()
    {
        $client = $this->markMerchantAsNonPurePlatformPartner('10000000000000', MerchantConstants::AGGREGATOR);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], Role::OWNER, Mode::LIVE);

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        return [$client, $user];
    }

    public function setUpNonPurePlatformPartnerAndSubmerchant($partnerId = '10000000000000', $submerchantId = '100submerchant')
    {
        $client = $this->markMerchantAsNonPurePlatformPartner($partnerId, MerchantConstants::AGGREGATOR);

        $user = $this->fixtures->user->createUserForMerchantONLiveAndTest($partnerId, [], Role::OWNER);

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $this->fixtures->merchant->createAccount($submerchantId);

        $this->createDefaultSubmerchantPricingPlan();

        $this->fixtures->merchant->edit(
            $submerchantId,
            [
                'pricing_plan_id' => 'SubmerchantPln',
                'parent_id'       => $partnerId
            ]
        );

        $appIds = (new MerchantApplications\Core)->getMerchantAppIds($partnerId, [MerchantApplications\Entity::MANAGED]);

        $accessMap = $this->fixtures->create(
            'merchant_access_map',
            [
                'id'              => 'J00dqRlTeStNzb',
                'merchant_id'     => $submerchantId,
                'entity_id'       => $appIds[0],
                'entity_type'     => 'application',
                'entity_owner_id' => $partnerId,
            ]
        );

        return $client;
    }

    public function setUpPartnerAuthWithoutSubMerchantAccountId()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $features = [
            FName::KYC_HANDLED_BY_PARTNER,
            FName::SUBMERCHANT_ONBOARDING,
        ];

        $this->fixtures->merchant->addFeatures($features);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator', 'pricing_plan_id' => '1hDYlICobzOCYt']);

        $partner = $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());
    }

    protected function allowAdminToAccessMerchant(string $merchantId)
    {
        $merchant = Merchant\Entity::find($merchantId);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        return $merchant;
    }
}
