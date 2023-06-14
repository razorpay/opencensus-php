<?php

namespace Tests\Unit\Models\User;

use Mockery;
use Carbon\Carbon;
use Tests\Unit\TestCase;
use RZP\Error\ErrorCode;
use RZP\Models\User\Core;
use RZP\Models\User\Entity;
use RZP\Models\User\Constants;
use RZP\Exception\LogicException;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Redis;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Services\Raven as RavenService;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\User\Service as UserService;
use RZP\Models\User\Validator as Validator;
use Illuminate\Support\Facades\Mail as Mail;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Exception\BadRequestValidationFailureException;

class UserTest extends TestCase
{

    protected $userService;

    protected $merchantServiceMock;

    protected $m2mReferralServiceMock;

    protected $userEntityMock;

    protected $coreMock;

    protected $repoMock;

    protected $userRepoMock;

    protected $orgRepoMock;

    protected $merchantRepoMock;

    protected $userValidator;

    protected $merchantEntityMock;

    protected $deviceEntityMock;

    protected $merchantDetailRepoMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\RZP\Models\Merchant\M2MReferral\Entity
     */
    private $m2mReferralEntityMock;

    protected function setUp(): void
    {

        parent::setUp();

        //wip, can be plugged out and run only once for all the tests.
        $this->createTestDependencyMocks();

        $this->userService = new UserService($this->coreMock, $this->userValidator, $this->merchantServiceMock,$this->m2mReferralServiceMock);
    }

    public function mockRedis()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['get'])
                          ->getMock();

        Redis::shouldReceive('connection')
             ->andReturn($redisMock);

        $redisMock->method('get')
                  ->will($this->returnValue(0));
    }

    public function testUserRegister()
    {
        $content = [
            'input' => [
                'user_id'               => '100002Razorpay',
                'business_name'         => 'dummy-business',
                'email'                 => 'dummy5@example.com',
            ],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy5@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
            'merchantData' => [
                'name'                  => 'dummy',
                'email'                 => 'dummy5@example.com',
                'org_id'                => 'org100razorpay',
                'signup_source'         => 'banking',
            ],
        ];

        Mail::fake();

        $orgRepoMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $mailMock = Mockery::mock('RZP\Mail');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('m2m_referral')->andReturn($this->m2mReferralEntityMock);

        $this->m2mReferralServiceMock->shouldReceive('extractFriendBuyParams')->andReturn([]);

        $this->m2mReferralServiceMock->shouldReceive('sendSignUpEventIfApplicable')->andReturn(true);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgRepoMock);

        $this->merchantRepoMock->shouldReceive('getByMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isProductBanking')->andReturn(true);

        $this->merchantServiceMock->shouldReceive('create')->andReturn((new MerchantEntity())->build($content['merchantData'])->toArrayPublic());

        $this->merchantServiceMock->shouldReceive('storeRelevantPreSignUpSourceInfoForBanking')->andReturn(null);

        $this->coreMock->shouldReceive('attach')->andReturn(((new Core())->create($content['userData']))->toArrayPublic());

        $this->coreMock->shouldReceive('checkIfMobileAlreadyExists')->withAnyArgs()->andReturn(false);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn(((new Core())->create($content['userData'])));

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn((new MerchantEntity())->build($content['merchantData']));

        $orgRepoMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgRepoMock);

        $orgRepoMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $response = $this->userService->register($content['userData']);

        $this->assertEquals($content['userData']['name'], $response['name']);

        $this->assertEquals($content['userData']['email'], $response['email']);
    }

    public function testCreate()
    {
        $content = [
            'id'                    => '100002Razorpay',
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'dummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->mockRedis();

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $response = $this->userService->create($content);

        $this->assertEquals($expected, $response);

    }

    public function testCreateWithoutCaptcha()
    {
        $content = [
            'id'                    => '100002Razorpay',
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'dummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->mockRedis();

        $userValidator = Mockery::mock('RZP\Models\User\Validator')->shouldAllowMockingProtectedMethods();
        $userValidator->shouldNotReceive('validateCaptcha');

        $response = $this->userService->create($content, 'create_without_captcha');

        $this->assertEquals($expected, $response);
    }

    public function testEdit()
    {
        $content = [
            'id'                    => '100002Razorpay',
            'name'                  => 'yummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'yummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->mockRedis();

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn($this->userEntityMock);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->coreMock->shouldReceive('edit')->andReturn((new Core())->create($content));

        $response = $this->userService->edit($content['id'] , $content);

        $this->assertEquals($expected, $response);
    }

    public function testUpdateUserMerchantMapping()
    {
        $content = [
            'merchantData' => [
                'merchant_id'           => '1cXSLlUU8V9sXl',
                'product'               => 'banking',
                'role'                  => 'manager',
                'action'                => 'edit',],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'dummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->mockRedis();

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn($this->userEntityMock);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->coreMock->shouldReceive('edit')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn(((new Core())->create($content['userData']))->toArrayPublic());

        $response = $this->userService->updateUserMerchantMapping('100002Razorpay', $content['merchantData']);

        $this->assertEquals($expected, $response);
    }

    public function testBulkUpdateUserMapping()
    {
        $content = [
            'merchantData' => [
                [
                    'user_id'               => '100002Razorpay',
                    'merchant_id'           => '1cXSLlUU8V9sXl',
                    'product'               => 'banking',
                    'role'                  => 'manager',
                    'action'                => 'edit',
                ],
                [
                    'user_id'               => '100003Razorpay',
                    'merchant_id'           => '1cXSLlUU8V9sXl',
                    'product'               => 'banking',
                    'role'                  => 'manager',
                    'action'                => 'edit',
                ],],
            'userData' => [
                [
                    'id'                    => '100002Razorpay',
                    'name'                  => 'dummy',
                    'email'                 => 'dummy@example.com',
                    'password'              => 'blahblah123',
                    'password_confirmation' => 'blahblah123',
                    'contact_mobile'        => '9999999999',
                    'confirm_token'         => 'hello123',
                    'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                ],
                [
                    'id'                    => '100003Razorpay',
                    'name'                  => 'yummy',
                    'email'                 => 'dummy@example.com',
                    'password'              => 'blahblah123',
                    'password_confirmation' => 'blahblah123',
                    'contact_mobile'        => '9999999999',
                    'confirm_token'         => 'hello123',
                    'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                ],
            ],
        ];

        $this->mockRedis();

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn($this->userEntityMock);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100003Razorpay')->andReturn($this->userEntityMock);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->coreMock->shouldReceive('edit')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn(((new Core())->create($content['userData'][0]))->toArrayPublic());

        $response = $this->userService->bulkUpdateUserMapping($content['merchantData']);

        $this->assertEquals([], $response);

    }

    public function testUpdateMerchantManageTeam()
    {
        $content = [
            'merchantData' => [
                'merchant_id'           => '1cXSLlUU8V9sXl',
                'product'               => 'banking',
                'role'                  => 'manager',
                'action'                => 'edit',],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ]
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'dummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->mockRedis();

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn($this->userEntityMock);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->coreMock->shouldReceive('edit')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn(((new Core())->create($content['userData']))->toArrayPublic());

        $response = $this->userService->updateMerchantManageTeam('100002Razorpay', $content['merchantData']);

        $this->assertEquals($expected, $response);
    }

    public function testEditSelf()
    {
        $content = [
            'id'                    => '100002Razorpay',
            'name'                  => 'yummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'yummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->mockRedis();

        $this->userEntityMock->shouldReceive('edit')->andReturn([]);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn(((new Core())->create($content))->toArrayPublic());

        $response = $this->userService->editSelf($content);

        $this->assertEquals($expected, $response);
    }

    public function testUpgradeUserToMerchant()
    {
        $content = [
            'input' => [
                'user_id'               => '100002Razorpay',
                'business_name'         => 'dummy-business',
                'email'                 => 'dummy@example.com',
            ],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
            'merchantData' => [
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'org_id'                => 'org100razorpay',
                'signup_source'         => 'banking',
            ],
        ];

        $expected = [
            'id'    => 'Fjaq2TmKg86djS',
            'name'  => 'dummy',
            'email' => 'dummy@example.com',
        ];

        Mail::fake();

        $this->mockRedis();

        $orgRepoMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgRepoMock);

        $this->merchantRepoMock->shouldReceive('getByMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn((new MerchantEntity())->build($content['merchantData']));

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isProductBanking')->andReturn(true);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn(((new Core())->create($content['userData'])));

        $this->merchantServiceMock->shouldReceive('create')->andReturn((new MerchantEntity())->build($content['merchantData'])->toArrayPublic());

        $this->merchantServiceMock->shouldReceive('storeRelevantPreSignUpSourceInfoForBanking')->andReturn(null);

        $this->coreMock->shouldReceive('attach')->andReturn(((new Core())->create($content['userData']))->toArrayPublic());

        $orgRepoMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgRepoMock);

        $orgRepoMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $response = $this->userService->upgradeUserToMerchant($content['input']);

        $this->assertEquals($expected['name'], $response['name']);

        $this->assertEquals($expected['email'], $response['email']);
    }

    //public function testUpdateContactMobile()
    //{
    //    $content = [
    //        'input' => [
    //            'user_id'               => '100002Razorpay',
    //            'contact_mobile'        => '9999999999',
    //        ],
    //        'userData' => [
    //            'id'                    => '100002Razorpay',
    //            'name'                  => 'dummy',
    //            'email'                 => 'dummy@example.com',
    //            'password'              => 'blahblah123',
    //            'password_confirmation' => 'blahblah123',
    //            'contact_mobile'        => '9999999999',
    //            'confirm_token'         => 'hello123',
    //            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
    //        ],
    //    ];
    //
    //    $this->userValidator->shouldReceive('validateInput')->andReturn([]);
    //
    //    $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);
    //
    //    $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);
    //
    //    $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);
    //
    //    $this->userRepoMock->shouldReceive('findOrFailPublic')->andReturn($this->userEntityMock);
    //
    //    $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');
    //
    //    $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');
    //
    //    $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);
    //
    //    $this->coreMock->shouldReceive('canMerchantUpdateUserDetails')->andReturn([]);
    //
    //    $this->userEntityMock->shouldReceive('setContactMobile')->andReturn([]);
    //
    //    $this->userEntityMock->shouldReceive('setContactMobileVerified')->andReturn([]);
    //
    //    $response = $this->userService->updateContactMobile($content['input']);
    //
    //    $this->assertEquals($this->userEntityMock, $response);
    //}

    public function testOAuthSignup()
    {
        $content = [
            'input' => [
                'user_id'               => '100002Razorpay',
                'business_name'         => 'dummy-business',
                'email'                 => 'dummy@example.com',
            ],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
            'merchantData' => [
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'org_id'                => 'org100razorpay',
                'signup_source'         => 'banking',
            ],
        ];

        Mail::fake();

        $orgRepoMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $mailMock = Mockery::mock('RZP\Mail');

        $this->mockRedis();

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgRepoMock);

        $this->merchantRepoMock->shouldReceive('getByMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isProductBanking')->andReturn(true);

        $this->merchantServiceMock->shouldReceive('create')->andReturn((new MerchantEntity())->build($content['merchantData'])->toArrayPublic());

        $this->merchantServiceMock->shouldReceive('storeRelevantPreSignUpSourceInfoForBanking')->andReturn(null);

        $this->coreMock->shouldReceive('attach')->andReturn(((new Core())->create($content['userData']))->toArrayPublic());

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn(((new Core())->create($content['userData'])));

        $this->coreMock->shouldReceive('checkIfMobileAlreadyExists')->withAnyArgs()->andReturn(false);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn((new MerchantEntity())->build($content['merchantData']));

        $orgRepoMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgRepoMock);

        $orgRepoMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $this->m2mReferralEntityMock = Mockery::mock('RZP\Models\Merchant\M2MReferral\Entity');

        $this->repoMock->shouldReceive('driver')->with('m2m_referral')->andReturn($this->m2mReferralEntityMock);

        $this->m2mReferralServiceMock->shouldReceive('extractFriendBuyParams')->andReturn([]);

        $this->m2mReferralServiceMock->shouldReceive('sendSignUpEventIfApplicable')->andReturn(true);

        $response = $this->userService->register($content['userData']);

        $this->assertEquals($content['userData']['name'], $response['name']);

        $this->assertEquals($content['userData']['email'], $response['email']);
    }

    public function testOAuthLogin()
    {
        $content = [
            'userData' => [
                'email'                 => 'dummy@example.com',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                'app'                   => '',
                'oauth_provider'        => '["google"]',
                'id_token'              => 'valid id token',
            ],
            'userDetails' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
            'merchantData' => [
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'org_id'                => 'org100razorpay',
                'signup_source'         => 'banking',
            ],
        ];

        $this->coreMock->shouldReceive('getUserEntity')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('getUserFromEmailOrFail')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getOauthProvider')->andReturn('google');

        $this->coreMock->shouldReceive('saveOauthProvider')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getConfirmedAttribute')->andReturn(true);

        $this->userEntityMock->shouldReceive('isSecondFactorAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('isSecondFactorAuthEnforced')->andReturn(false);

        $this->userEntityMock->shouldReceive('isOrgEnforcedSecondFactorAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $this->coreMock->shouldReceive('get')->andReturn(['id' => '100002Razorpay']);

        $response = $this->userService->oAuthLogin($content['userData']);

        $this->assertEquals([Constants::INVALIDATE_SESSIONS => false, 'id' => '100002Razorpay'], $response);
    }

    public function testVerifyUserThroughEmail()
    {
        $content = [
            'otp'           => '0007',
            'token'         => 'sadscscs',
        ];

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->userEntityMock->shouldReceive('getContactMobile')->andReturn('9999999999');

        $response = $this->userService->verifyUserThroughEmail($content);

        $this->assertNotNull($response);
    }

    public function testVerifyUserThroughMode()
    {
        $content = [
            'otp'           => '0007',
            'action'        => 'second_factor_auth',
            'token'         => 'sadscscs',
        ];

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->userEntityMock->shouldReceive('getContactMobile')->andReturn('9999999999');

        $response = $this->userService->verifyUserThroughMode($content);

        $this->assertNotNull($response);
    }

    public function testAccountUnlock()
    {
        $expected = [
            'account_locked'          => false,
            'user_id'                 => '100003Razorpay',
        ];

        $userEntityMockTmp = Mockery::mock('RZP\Models\User\Entity');

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->andReturn($userEntityMockTmp);

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $userEntityMockTmp->shouldReceive('getId')->andReturn('100003Razorpay');

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantEntityMock);

        $this->coreMock->shouldReceive('canMerchantUpdateUserDetails')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $userEntityMockTmp->shouldReceive('setWrong2faAttempts')->andReturn([]);

        $userEntityMockTmp->shouldReceive('setAccountLocked')->andReturn([]);

        $this->repoMock->shouldReceive('saveOrFail')->andReturn([]);

        $userEntityMockTmp->shouldReceive('isAccountLocked')->andReturn(false);

        $response = $this->userService->accountLockUnlock('100002Razorpay', 'unlock');

        $this->assertEquals($expected, $response);
    }

    public function testAccountLock()
    {
        $expected = [
            'account_locked'          => true,
            'user_id'                 => '100003Razorpay',
        ];

        $userEntityMockTmp = Mockery::mock('RZP\Models\User\Entity');

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->andReturn($userEntityMockTmp);

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(true);

        $this->basicAuthMock->shouldReceive('getAdmin')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $userEntityMockTmp->shouldReceive('getId')->andReturn('100003Razorpay');

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantEntityMock);

        $this->coreMock->shouldReceive('canMerchantUpdateUserDetails')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $userEntityMockTmp->shouldReceive('setWrong2faAttempts')->andReturn([]);

        $userEntityMockTmp->shouldReceive('setAccountLocked')->andReturn([]);

        $this->repoMock->shouldReceive('saveOrFail')->andReturn([]);

        $userEntityMockTmp->shouldReceive('isAccountLocked')->andReturn(true);

        $response = $this->userService->accountLockUnlock('100002Razorpay', 'lock');

        $this->assertEquals($expected, $response);
    }

    public function testGetUserForMerchant()
    {
        $content = [
            'id'                    => '100002Razorpay',
            'name'                  => 'yummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'yummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->mockRedis();

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantRepoMock->shouldReceive('getMerchantUserMapping')->andReturn((new Core())->create($content));

        $response = $this->userService->getUserForMerchant($content['id']);

        $this->assertEquals($response, $expected);
    }

    public function testChangePasswordByToken(){

        $input=[
            'token'=>'BUIj3m2Nx2VvVj',
            'email'                 => 'dummy@example.com',
            'password'              => '123456xx',
            'password_confirmation' => '123456xx',

        ];

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->coreMock->shouldReceive('getUserEntity')->andReturn($this->userEntityMock);

        $userRepoMock = Mockery::mock('RZP\Models\User\Repository');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($userRepoMock);

        $userRepoMock->shouldReceive('getUserFromEmail')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->app->instance('repo', $this->repoMock);

        $this->userEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $expiry = Carbon::now()->addMinutes(4)->getTimestamp();

        $this->userEntityMock->shouldReceive('getPasswordResetExpiry')->andReturn($expiry);

        $this->userEntityMock->shouldReceive('getPasswordResetToken')->andReturn($input['token']);

        $this->userEntityMock->shouldReceive('getId')->andReturn('20000000000002');

        $this->userEntityMock->shouldReceive('getMerchantEntity')->andReturn(null);

        $this->repoMock->shouldReceive('saveOrFail')->andReturn([]);

        $this->userValidator->shouldReceive('validatePasswordIsNotSameAsLastThree')->andReturn([]);

        $this->userEntityMock->shouldReceive('setAttribute')->andReturn([]);

        $this->userEntityMock->shouldReceive('fill')->andReturn([]);



        $orgData = [
            'id'            => 'org_100000razorpay',
            'display_name'  => 'Razorpay',
            'business_name' => 'Razorpay Software Pvt Ltd',
            'email'         => 'admin@razorpay.com',
            'from_email' => 'sender@razorpay.com',
            'display_name' => 'RZP',
        ];

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn($orgData);

        $orgMock->shouldReceive('isMerchant2FaEnabled')->withAnyArgs()->andReturn(false);

        Mail::fake();

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $response = $this->userService->changePasswordByToken($input);

        $expected = ['success' => true, 'user_id' => '20000000000002'];

        $this->assertEquals($expected, $response);

    }

    public function testGet()
    {
        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(true);

        $userRepoMock = Mockery::mock('RZP\Models\User\Repository');

        $merchantUserRepoMock = Mockery::mock('RZP\Models\Merchant\MerchantUser');

        $deviceDetailMock = Mockery::mock('RZP\Models\DeviceDetail\Repository');

        $deviceDetailMock->shouldReceive('fetchByUserId')->andReturn('');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($userRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant_user')->andReturn($merchantUserRepoMock);

        $this->repoMock->shouldReceive('driver')->with('user_device_detail')->andReturn($deviceDetailMock);

        $merchantUserRepoMock->shouldReceive('fetchBankingSignUpTimeStamp')->andReturn(21323);

        $userData = [
            'id'                    => '100002Razorpay',
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $userRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn($userData);

        $merchantEntityMock = Mockery::mock('RZP\Models\User\Entity')->makePartial();

        $this->merchantEntityMock->shouldReceive('users')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('where')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('first')->andReturn($userData);

        $queryBuilderMock = Mockery::mock('\Illuminate\Database\Query\Builder');

        $queryBuilderMock->shouldReceive('where')->andReturn($merchantEntityMock);

        $this->userEntityMock->shouldReceive('merchant')->andReturn($queryBuilderMock);

        $this->userEntityMock->shouldReceive('getAllSettings')->andReturn([]);

        $merchantUnique = [
            'id'    => '1X4hRFHFx4UiXt',
        ];

        $merchantEntityMock->shouldReceive('callOnEveryItem')->andReturn($merchantUnique);

        $this->coreMock->shouldReceive('getUnifiedMerchants')->andReturn($merchantUnique);

        $this->coreMock->shouldReceive('appendBankingSpecificDetails')->andReturn($merchantUnique);

        $invitation = Mockery::mock('RZP\Models\Invitation\Entity');

        $invitation->shouldReceive('callOnEveryItem')->andReturn([]);

        $this->userEntityMock->shouldReceive('invitation')->andReturn($invitation);

        $expectedResponse = [
            'id'                    => '100002Razorpay',
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'merchants'             => $merchantUnique,
            'invitations'           => [],
            'settings'              => [],
        ];

        $response = $this->userService->get('100002Razorpay');

        $this->assertEquals($expectedResponse, $response);

    }

    public function testAddProductSwitchRole()
    {
        $merchantMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($merchantMock);

        $mappingData = [
            'user_id'     => '100002Razorpay',
            'merchant_id' => '1X4hRFHFx4UiXt',
            'role'        => 'owner',
            'product'     => 'banking'
        ];



        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $merchantMock->shouldReceive('getMerchantUserMapping')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->coreMock->shouldReceive('attach')->andReturn([]);

        $response = $this->userService->addProductSwitchRole('banking');

        $this->assertEquals($this->userEntityMock, $response);

    }

    public function testChange2faSetting()
    {
        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userEntityMock->shouldReceive('isSecondFactorAuthEnforced')->andReturn(false);

        $this->userEntityMock->shouldReceive('isSecondFactorAuthSetup')->andReturn(true);

        $this->userEntityMock->shouldReceive('isOrgEnforcedSecondFactorAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('isContactMobileVerified')->andReturn(true);

        $segmentMock = Mockery::mock('RZP\Services\Segment');

        $segmentMock->shouldReceive('pushIdentifyAndTrackEvent')->andReturn([]);

        $this->app->instance('segment-analytics', $segmentMock);

        $input = [
            'second_factor_auth' => true,
        ];

        $response = $this->userService->change2faSetting($input);

        $this->assertEquals($input, $response);
    }

    public function testSendConfirmationMailOnConfirm()
    {
        $this->userEntityMock->shouldReceive('getConfirmedAttribute')->andReturn(true);

        $response = $this->userService->sendConfirmationMail($this->userEntityMock);

        $this->assertEquals(['confirm' => true], $response);
    }

    public function testSendConfirmationMailOnSuccess()
    {
        Mail::fake();

        $orgData = [
            'id' => 'org_100000razorpay',
            'display_name' => 'Razorpay',
            'business_name' => 'Razorpay Software Pvt Ltd',
            'email' => 'admin@razorpay.com',
        ];

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->userEntityMock->shouldReceive('getConfirmedAttribute')->andReturn(false);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $this->userEntityMock->shouldReceive('getConfirmToken')->andReturn([]);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn($orgData);

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $response = $this->userService->sendConfirmationMail($this->userEntityMock);

        $this->assertEquals(['success' => true], $response);
    }

    public function testEditContactMobile()
    {
        Mail::fake();

        $input = [
            Entity::OTP_AUTH_TOKEN => '1037849273938',
            Entity::CONTACT_MOBILE => '9177278079',
        ];

        $this->userValidator->shouldReceive('validateInput')->withAnyArgs()->andReturn([]);

        $this->basicAuthMock->shouldReceive('isProductBanking')->withAnyArgs()->andReturn(true);

        $this->userEntityMock->shouldReceive('getRestricted')->withAnyArgs()->andReturn(false);

        $this->merchantEntityMock->shouldReceive('isRazorpayOrgId')->withAnyArgs()->andReturn(true);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($this->orgRepoMock);

        $org = Mockery::mock('\RZP\Models\Admin\Org\Entity');

        $org->shouldReceive('getMainLogo')->andReturn('razorpay.png');

        $org->shouldReceive('getCheckoutLogo')->andReturn('razorpay.png');

        $org->shouldReceive('getDisplayName')->andReturn('razorpay');

        $this->orgRepoMock->shouldReceive('find')->withAnyArgs()->andReturn($org);

        //token service
        $token = Mockery::mock('\RZP\Services\TokenService', [$this->app]);

        $token->shouldReceive('verify')->andReturn([]);

        $this->app->instance('token_service', $token);

        //module mock
        $module = Mockery::mock('\RZP\Modules\Manager', [$this->app]);

        $module->shouldReceive('sendOtp')->andReturn([]);

        $secondFactor = Mockery::mock('\RZP\Mail\Merchant\SecondFactorAuth');

        $sms = Mockery::mock('\RZP\Modules\SecondFactorAuth\SmsOtpAuth');

        $sms->shouldReceive('sendOtp')->andReturn(true);

        $secondFactor->shouldReceive('make')->andReturn($sms);

        $module->shouldReceive('driver')->with('secondFactorAuth')->andReturn($secondFactor);

        $this->app->instance('module', $module);

        $expiry = Carbon::now()->addMinutes(4)->getTimestamp();

        $this->userEntityMock->shouldReceive('getUpdatedAt')->withAnyArgs()->andReturn($expiry);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getName')->andReturn('RZP');

        $response = $this->userService->editContactMobile($input);

        $this->assertNotNull($response);

    }

    public function testEditContactMobileWithException()
    {
        $this->expectException(BadRequestException::class);

        $input = [
            Entity::OTP_AUTH_TOKEN => '1037849273938',
            Entity::CONTACT_MOBILE => '9177278079',
        ];

        $this->userValidator->shouldReceive('validateInput')->withAnyArgs()->andReturn([]);

        $this->basicAuthMock->shouldReceive('isProductBanking')->withAnyArgs()->andReturn(false);

        $this->userEntityMock->shouldReceive('isSecondFactorAuthSetup')->withAnyArgs()->andReturn(true);

        $this->userService->editContactMobile($input);
    }

    public function testResetUserPassword()
    {
        $input = [
            Entity::PASSWORD              => 'razorpay1234',
            Entity::PASSWORD_CONFIRMATION => 'razorpay1234',
        ];

        $admin = Mockery::mock('\RZP\Models\Admin\Admin\Entity');

        $admin->shouldReceive('canSeeAllMerchants')->andReturn(true);

        $admin->shouldReceive('getOrgId')->andReturn('org_100000000000');

        $this->merchantEntityMock->shouldReceive('getOrgId')->andReturn('org_100000000000');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userValidator->shouldReceive('validateMerchantUserRelation')->withAnyArgs()->andReturn([]);

        $this->userValidator->shouldReceive('validateInput')->withAnyArgs()->andReturn([]);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('edit')->withAnyArgs()->andReturn([]);

        $this->basicAuthMock->shouldReceive('getAdmin')->withAnyArgs()->andReturn($admin);

        $this->basicAuthMock->shouldReceive('getMerchant')->withAnyArgs()->andReturn($this->merchantEntityMock);

        $response = $this->userService->resetUserPassword('100002Razorpay', $input);

        $this->assertEquals(['success' => true], $response);
    }

    public function testVerifyEmailWithOtp()
    {
        $input = [
            Entity::OTP            => '0007',
            Entity::TOKEN          => '43713134',
            Entity::CONTACT_MOBILE => '9177278079',
            Entity::CONFIRM_TOKEN  => false,
        ];

        $diagMock = Mockery::mock('RZP\Services\DiagClient');

        $diagMock->shouldReceive('trackOnboardingEvent')->andReturn([]);

        $this->app->instance('diag', $diagMock);

        $this->userEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('getConfirmedAttribute')->withAnyArgs()->andReturn(false);

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateVerifyEmailWithOtpOperation')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn(['name'  => 'rzp', 'email' => 'rzp@gmail.com',]);

        $this->coreMock->shouldReceive('subscribeToMailingList')->andReturn([]);

        $response = $this->userService->verifyEmailWithOtp($input);

        $this->assertNotNull($response);
    }

    public function testVerifyContactWithOtp()
    {
        $input = [
            Entity::OTP            => '0007',
            Entity::TOKEN          => '43713134',
            Entity::CONTACT_MOBILE => '9177278079',
        ];

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateVerifyContactWithOtpOperation')->withAnyArgs()->andReturn(false);

        $this->userEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $this->userValidator->shouldReceive('setContactMobileVerified')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getEmail')->andReturn('abc@sc.com');

        $response = $this->userService->verifyContactWithOtp($input);

        $this->assertNotNull($response);

    }

    public function testSendOtpWithContact()
    {
        $input = [
            Entity::TOKEN          => '43713134',
            Entity::CONTACT_MOBILE => '9177278079',
            Entity::ACTION         => 'bureau_verify',
        ];

        $ravenMock = Mockery::mock('RZP\Services\Mock\Raven');

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $ravenMock->shouldReceive('sendSms')->andReturn(['sms_id' => '10000000000sms']);

        $this->app->instance('raven', $ravenMock);

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $response = $this->userService->sendOtpWithContact($input);

        $this->assertEquals($response[Entity::TOKEN], $input[Entity::TOKEN]);
    }

    public function testSendOtp()
    {
        Mail::fake();

        $input = [
            Entity::MEDIUM        => 'sms',
            Entity::ACTION        => 'verify_contact',
            Entity::TOKEN         => '7643816831',
        ];

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $ravenMock = Mockery::mock('RZP\Services\Mock\Raven');

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $ravenMock->shouldReceive('sendSms')->andReturn(['sms_id' => '10000000000sms']);

        $this->app->instance('raven', $ravenMock);

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateSendOtpOperation')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $response = $this->userService->sendOtp($input);

        $this->assertEquals($response[Entity::TOKEN], $input[Entity::TOKEN]);

        $input = [
            Entity::MEDIUM        => 'email',
            Entity::ACTION        => 'verify_contact',
            Entity::TOKEN         => '7643816831',
        ];

        $this->userEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $response = $this->userService->sendOtp($input);

        $this->assertEquals($response[Entity::TOKEN], $input[Entity::TOKEN]);
    }

    public function testSendOtpForScanAndPay()
    {
        $input = [
            Entity::MEDIUM        => 'sms',
            Entity::ACTION        => 'create_composite_payout_with_otp',
            Entity::TOKEN         => '7643816831',
            PayoutEntity::AMOUNT    => 100000,
            PayoutEntity::PURPOSE   => "refund",
            PayoutEntity::ACCOUNT_NUMBER => "3434605717969098",
            "vpa"                   => "vivek@okhdfcbank",
        ];

        $ravenMock = Mockery::mock('RZP\Services\Mock\Raven');

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $ravenMock->shouldReceive('sendSms')
            ->withArgs(
                function($input) {
                    if ($input['template'] != 'sms.user.create_payout')
                    {
                        return false;
                    }

                    if ($input['source'] != 'api.user.create_payout')
                    {
                        return false;
                    }

                    return true;
                }
            )
            ->andReturn(
                ['sms_id' => '10000000000sms']
            );

        $this->app->instance('raven', $ravenMock);

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateSendOtpOperation')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $response = $this->userService->sendOtp($input);

        $this->assertEquals($response[Entity::TOKEN], $input[Entity::TOKEN]);

        $input = [
            Entity::MEDIUM        => 'email',
            Entity::ACTION        => 'create_composite_payout_with_otp',
            Entity::TOKEN         => '7643816831',
            PayoutEntity::AMOUNT    => 100000,
            PayoutEntity::PURPOSE   => "refund",
            PayoutEntity::ACCOUNT_NUMBER => "3434605717969098",
            "vpa"                   => "vivek@okhdfcbank",
        ];

        Mail::fake();

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $response = $this->userService->sendOtp($input);

        $this->assertEquals($response[Entity::TOKEN], $input[Entity::TOKEN]);
    }

    public function testSendOtpVerifyUser()
    {
        Mail::fake();

        $input = [
            Entity::MEDIUM        => 'sms',
            Entity::ACTION        => 'verify_user',
            Entity::TOKEN         => '7643816831',
        ];

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $ravenMock = Mockery::mock('RZP\Services\Mock\Raven');

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $ravenMock->shouldReceive('sendSms')->andReturn(['sms_id' => '10000000000sms']);

        $this->app->instance('raven', $ravenMock);

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateSendOtpOperation')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $response = $this->userService->sendOtp($input);

        $this->assertEquals($response[Entity::TOKEN], $input[Entity::TOKEN]);

        $input = [
            Entity::MEDIUM        => 'email',
            Entity::ACTION        => 'verify_user',
            Entity::TOKEN         => '7643816831',
        ];

        $this->userEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $response = $this->userService->sendOtp($input);

        $this->assertEquals($response[Entity::TOKEN], $input[Entity::TOKEN]);
    }

    public function testConfirm()
    {
        $content  = [
            'merchantData' => [
                'merchant_id' => '1cXSLlUU8V9sXl',
                'product'     => 'banking',
                'role'        => 'manager',
                'action'      => 'edit',],
            'userData'     => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ];

        $diagMock = Mockery::mock('RZP\Diag\EventCode');

        $this->repoMock->shouldReceive('driver')->with('diag')->andReturn($diagMock);

        $diagMock->shouldReceive('trackOnboardingEvent')->withAnyArgs()->andReturn([]);

        $this->app->instance('diag', $diagMock);

        $userRepoMock = Mockery::mock('RZP\Models\User\Repository');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($userRepoMock);

        $userRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('setAttribute')->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn($content['userData']);

        $this->userEntityMock->shouldReceive('getMerchantId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getEntity')->andReturn($this->merchantEntityMock);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->userEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $response = $this->userService->confirm('100002Razorpay');

        $this->assertEquals($content['userData'], $response);
    }

    public function testResendVerificationMail()
    {
        $this->basicAuthMock->shouldReceive('getDashboardHeaders')->andReturn(['user_id' => '100002Razorpay']);

        $userRepoMock = Mockery::mock('RZP\Models\User\Repository');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($userRepoMock);

        $userRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getConfirmedAttribute')->andReturn(true);

        $this->userEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $this->coreMock->shouldReceive('trackOnboardingEvent')->andReturn([]);

        $response = $this->userService->resendVerificationMail();

        $this->assertEquals(['confirm' => true], $response);
    }

    public function testGetTokenWithExpiry()
    {
        $userId = '100002Razorpay';

        $expiry = 86400;

        $token  = '7SbedIkSCXZmc2Fmj6Pf80RZfn1q4jTxigb1bZJ03vCnGJDUh2';

        $this->coreMock->shouldReceive('generateToken')->andReturn($token);

        $userRepoMock = Mockery::mock('RZP\Models\User\Repository');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($userRepoMock);

        $userRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('setAttribute')->andReturn([]);

        $response = $this->userService->getTokenWithExpiry($userId, $expiry);

        $this->assertEquals($token, $response);
    }

    public function testChangePassword()
    {
        $input=[
            'password'              => '123456xx',
            'password_confirmation' => '123456xx',
            'old_password'          => '3234234g',
        ];

        $orgData = [
            'id'            => 'org_100000razorpay',
            'display_name'  => 'Razorpay',
            'business_name' => 'Razorpay Software Pvt Ltd',
            'email'         => 'admin@razorpay.com',
            'from_email' => 'sender@razorpay.com',
            'display_name' => 'RZP',
        ];

        Mail::fake();

        $mailMock = Mockery::mock('RZP\Mail');

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userEntityMock->shouldReceive('getId')->andReturn('20000000000002');

        $this->userEntityMock->shouldReceive('getMerchantEntity')->andReturn(null);

        $this->userValidator->shouldReceive('validatePasswordIsNotSameAsLastThree')->andReturn([]);

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn($orgData);

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn([]);

        $response = $this->userService->changePassword($input);

        $this->assertEquals([], $response);
    }

    public function testLogin()
    {
        $content = [
            'userData' => [
                'email'                 => 'dummy@example.com',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                'app'                   => '',
                'password'              => 'blahblah123',
            ],
            'userDetails' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
            'merchantData' => [
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'org_id'                => 'org100razorpay',
                'signup_source'         => 'banking',
            ],
        ];

        $expected = [
            'id' => '100002Razorpay',
            'otp_auth_token' => '100002Razorpay.1639136024'
        ];

        $token = Mockery::mock('\RZP\Services\TokenService', [$this->app]);

        $token->shouldReceive('generate')->andReturn('100002Razorpay.1639136024');

        $this->app->instance('token_service', $token);

        $hash = (new BcryptHasher())->make('blahblah123');

        $this->coreMock->shouldReceive('getUserEntity')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userValidator->shouldReceive('isCaptchaDisabled')->andReturn(true);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('getUserFromEmailOrFail')->andReturn($this->userEntityMock);

        $this->userRepoMock->shouldReceive('findByEmail')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getPassword')->andReturn($hash);

        $this->userEntityMock->shouldReceive('getOauthProvider')->andReturn('google');

        $this->coreMock->shouldReceive('saveOauthProvider')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getConfirmedAttribute')->andReturn(true);

        $this->userEntityMock->shouldReceive('isSecondFactorAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('isSecondFactorAuthEnforced')->andReturn(false);

        $this->userEntityMock->shouldReceive('isOrgEnforcedSecondFactorAuth')->andReturn(false);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $this->coreMock->shouldReceive('get')->andReturn(['id' => '100002Razorpay']);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->with('100002Razorpay')->andReturn(((new Core())->create($content['userDetails'])));

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn((new MerchantEntity())->build($content['merchantData']));

        $response = $this->userService->login($content['userData']);

        $this->assertEquals($expected, $response);
    }

    public function testCheckUserAccess()
    {
        $content = [
            'merchant_id'           => '1cXSLlUU8V9sXl',
        ];

        $merchantData = [
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'org_id'                => 'org100razorpay',
            'signup_source'         => 'banking',
        ];

        $this->coreMock->shouldReceive('checkAccessForMerchant')->andReturn([]);

        $response = $this->userService->checkUserAccess($content);

        $this->assertEquals([], $response);
    }

    public function testSetup2faContactMobile()
    {
        $content = [
            'contact_mobile'        => '9999999999',
        ];

        $this->coreMock->shouldReceive('checkIfUserCanHitSetup2faRoute')->andReturn([]);

        $module = Mockery::mock('\RZP\Modules\Manager', [$this->app]);

        $module->shouldReceive('sendOtp')->andReturn([]);

        $secondFactor = Mockery::mock('\RZP\Mail\Merchant\SecondFactorAuth');

        $sms = Mockery::mock('\RZP\Modules\SecondFactorAuth\SmsOtpAuth');

        $sms->shouldReceive('sendOtp')->andReturn(true);

        $secondFactor->shouldReceive('make')->andReturn($sms);

        $module->shouldReceive('driver')->with('secondFactorAuth')->andReturn($secondFactor);

        $this->app->instance('module', $module);

        $response = $this->userService->setup2faContactMobile($content);

        $this->assertEquals([], $response);
    }

    public function testResendOtp()
    {
        $this->userEntityMock->shouldReceive('isAccountLocked')->andReturn(false );

        $module = Mockery::mock('\RZP\Modules\Manager', [$this->app]);

        $module->shouldReceive('sendOtp')->andReturn([]);

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('get2FaAuthMode')->withAnyArgs()->andReturn('sms');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->app->instance('module', $module);

        $response = $this->userService->resendOtp();

        $this->assertEquals(null, $response);
    }

    public function testSend2faOtp()
    {
        $this->userEntityMock->shouldReceive('isSecondFactorAuthSetup')->andReturn(true);

        $this->userEntityMock->shouldReceive('isAccountLocked')->andReturn(false);

        $module = Mockery::mock('\RZP\Modules\Manager', [$this->app]);

        $module->shouldReceive('sendOtp')->andReturn([]);

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('get2FaAuthMode')->withAnyArgs()->andReturn('sms');

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->app->instance('module', $module);

        $response = $this->userService->send2faOtp();

        $this->assertEquals([], $response);
    }

    public function testConfirm1()
    {
        $content = [
            'input' => [
                'user_id'               => '100002Razorpay',
                'business_name'         => 'dummy-business',
                'email'                 => 'dummy@example.com',
            ],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
            'merchantData' => [
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'org_id'                => 'org100razorpay',
                'signup_source'         => 'banking',
            ],
        ];

        $expected = [
            'name' => 'dummy',
            'id' => '100002Razorpay',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => true,
            'email_verified' => true
        ];

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->andReturn(((new Core())->create($content['userData'])));

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $this->merchantEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $response = $this->userService->confirm('100002Razorpay');

        $this->assertEquals($response , $expected);
    }

    public function testSetup2faVerifyMobileOnLogin()
    {
        $userData = [
            'id'                    => '100002Razorpay',
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'password'              => '12345',
            'password_confirmation' => '12345',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'otp'                   => '0007',
        ];

        $hash = (new BcryptHasher())->make($userData['password']);

        $this->coreMock->shouldReceive('getUserEntity')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userEntityMock->shouldReceive('getPassword')->andReturn($hash);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('findByEmail')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getPassword')->andReturn($userData['password']);

        $this->userEntityMock->shouldReceive('isSecondFactorAuth')->andReturn(true);

        $this->userEntityMock->shouldReceive('isSecondFactorAuthEnforced')->andReturn(true);

        $this->userEntityMock->shouldReceive('isAccountLocked')->andReturn(false);

        $this->userEntityMock->shouldReceive('getRestricted')->andReturn(false);

        $this->userEntityMock->shouldReceive('isSecondFactorAuthSetup')->andReturn(false);

        $this->userEntityMock->shouldReceive('getId')->andReturn($userData['id']);

        $module = Mockery::mock('\RZP\Modules\Manager', [$this->app]);

        $module->shouldReceive('sendOtp')->andReturn([]);

        $secondFactor = Mockery::mock('\RZP\Mail\Merchant\SecondFactorAuth');

        $sms = Mockery::mock('\RZP\Modules\SecondFactorAuth\SmsOtpAuth');

        $sms->shouldReceive('is2faCredentialValid')->andReturn(true);

        $secondFactor->shouldReceive('make')->andReturn($sms);

        $module->shouldReceive('driver')->with('secondFactorAuth')->andReturn($secondFactor);

        $this->app->instance('module', $module);

        $this->userEntityMock->shouldReceive('setAttribute')->andReturn([]);

        $this->coreMock->shouldReceive('get')->andReturn($userData);

        $response = $this->userService->setup2faVerifyMobileOnLogin($userData);

        $this->assertEquals($userData, $response);
    }

    public function testConfirmUserByData()
    {
        $content = [
            'input' => [
                'email'                 => 'dummy@example.com',
                'confirm_token'         => 'hello123',],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            ],
        ];

        $expected = [
            'id' => '100002Razorpay',
            'name' => 'dummy',
            'email' => 'dummy@example.com',
            'contact_mobile' => '9999999999',
            'contact_mobile_verified' => false,
            'second_factor_auth_enforced' => false,
            'second_factor_auth_setup' => false,
            'org_enforced_second_factor_auth' => false,
            'restricted' => false,
            'confirmed' => false,
            'email_verified' => false,
        ];

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $userRepoMock = Mockery::mock('RZP\Models\User\Repository');

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($userRepoMock);

        $userRepoMock->shouldReceive('findByToken')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->app->instance('repo', $this->repoMock);

        $this->coreMock->shouldReceive('subscribeToMailingList')->andReturn([]);

        $this->coreMock->shouldReceive('confirm')->andReturn(((new Core())->create($content['userData'])));

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $response = $this->userService->confirmUserByData($content['input']);

        $this->assertEquals($expected , $response);
    }

    public function testPostResetPassword()
    {
        $orgData = [
            'id'            => 'org_100000razorpay',
            'display_name'  => 'Razorpay',
            'business_name' => 'Razorpay Software Pvt Ltd',
            'email'         => 'admin@razorpay.com',
            'from_email' => 'sender@razorpay.com',
            'display_name' => 'RZP',
        ];

        $userData = [
            'id'                    => '100002Razorpay',
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $subMerchantUser = $this->userEntityMock;

        $subMerchant = $this->merchantEntityMock;

        $subMerchant->shouldReceive('isLinkedAccount')->andReturn(false);

        $createdNew = true;

        Mail::fake();

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($subMerchantUser);

        $subMerchantUser->shouldReceive('getUserFromEmail')->andReturn($subMerchantUser);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn($orgData);

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $response = $this->userService->sendAccountLinkedCommunicationEmail($subMerchantUser, $subMerchant, $createdNew);

        $expected = ['success' => true];

        $this->assertEquals($expected, $response);
    }

    public function testPostAccountMappedEmail()
    {
        $content = [
            'merchantData' => [
                'merchant_id'           => '1cXSLlUU8V9sXl',
                'product'               => 'banking',
                'role'                  => 'manager',
                'action'                => 'edit',],
            'userData' => [
                'id'                    => '100002Razorpay',
                'name'                  => 'dummy',
                'email'                 => 'dummy@example.com',
                'password'              => 'blahblah123',
                'password_confirmation' => 'blahblah123',
                'contact_mobile'        => '9999999999',
                'confirm_token'         => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',],
            'orgData' => [
                'id'            => 'org_100000razorpay',
                'display_name'  => 'Razorpay',
                'business_name' => 'Razorpay Software Pvt Ltd',
                'email'         => 'admin@razorpay.com',
                'from_email' => 'sender@razorpay.com',
                'display_name' => 'RZP',],
        ];

        $subMerchantUser = $this->userEntityMock;

        $subMerchant = $this->merchantEntityMock;

        $subMerchant->shouldReceive('isLinkedAccount')->andReturn(false);

        $subMerchant->shouldReceive('isRazorpayOrgId')->withAnyArgs()->andReturn(true);

        $createdNew = false;

        Mail::fake();

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $org = Mockery::mock('\RZP\Models\Admin\Org\Entity');

        $org->shouldReceive('getMainLogo')->andReturn('razorpay.png');

        $org->shouldReceive('getCheckoutLogo')->andReturn('razorpay.png');

        $org->shouldReceive('getDisplayName')->andReturn('razorpay');

        $this->orgRepoMock->shouldReceive('find')->withAnyArgs()->andReturn($org);

        $orgMock->shouldReceive('find')->withAnyArgs(Org::RZP_ORG)->andReturn($org);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn($content['orgData']);

        $subMerchant->shouldReceive('toArrayPublic')->andReturn($content['merchantData']);

        $subMerchantUser->shouldReceive('toArrayPublic')->andReturn($content['userData']);

        $mailMock = Mockery::mock('RZP\Mail');

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $response = $this->userService->sendAccountLinkedCommunicationEmail($subMerchantUser, $subMerchant, $createdNew);

        $expected = ['success' => true];

        $this->assertEquals($expected, $response);
    }

    public function testResendVerificationOtp()
    {
        $input = [
            Entity::TOKEN           => '65431731',
        ];

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $this->userValidator->shouldReceive('validateResendEmailWithOtpOperation')->withAnyArgs()->andReturn([]);

        $this->basicAuthMock->shouldReceive('getDashboardHeaders')->withAnyArgs()->andReturn(['user_id' => '100000000']);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->userEntityMock);

        $response = $this->userService->resendVerificationOtp($input);

        $this->assertEquals(['confirm' => true], $response);

        $this->userEntityMock->shouldReceive('getConfirmedAttribute')->withAnyArgs()->andReturn(false);

        $this->userEntityMock->shouldReceive('getEmail')->withAnyArgs()->andReturn('rzp@razorpay.com');

        //mocking here since sendOtp test is already covered.

        $this->coreMock->shouldReceive('sendOtp')->withAnyArgs()->andReturn(['token' => '0007']);

        $this->merchantEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $diagMock = Mockery::mock('RZP\Services\DiagClient');

        $diagMock->shouldReceive('trackOnboardingEvent')->andReturn([]);

        $this->app->instance('diag', $diagMock);

        $response = $this->userService->resendVerificationOtp($input);

        $this->assertEquals(['token' => '0007'], $response);
    }

    public function testVerifyUserSecondFactorAuthLockedException()
    {
        $input = [
            'otp' => '0007',
        ];

        $this->expectException(BadRequestException::class);

        $this->coreMock->shouldReceive('getUserEntity')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getValidator')->withAnyArgs()->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->withAnyArgs()->andReturn([]);

        $this->userEntityMock->shouldReceive('isAccountLocked')->withAnyArgs()->andReturn(true);

        $this->userEntityMock->shouldReceive('getRestrictedAttribute')->withAnyArgs()->andReturn(true);

        $this->userEntityMock->shouldReceive('isOwner')->withAnyArgs()->andReturn(true);

        $this->userService->verifyUserSecondFactorAuth($input);
    }

    public function testVerifyUserSecondFactorAuth()
    {
        $input = [
            'otp' => '0007',
        ];

        $expected = [
            'id'                    => '100002Razorpay',
            'name'                  => 'dummy',
            'email'                 => 'dummy@example.com',
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '9999999999',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'merchants'             => '',
            'invitations'           => [],
            'settings'              => [],
            'otp_auth_token'        => '100002Razorpay.1639136024',
        ];

        $this->userEntityMock->shouldReceive('isAccountLocked')->withAnyArgs()->andReturn(false);

        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $module = Mockery::mock('\RZP\Modules\Manager', [$this->app]);

        $secondFactor = Mockery::mock('\RZP\Mail\Merchant\SecondFactorAuth');

        $sms = Mockery::mock('\RZP\Modules\SecondFactorAuth\SmsOtpAuth');

        $sms->shouldReceive('is2faCredentialValid')->withAnyArgs()->andReturn(true);

        $secondFactor->shouldReceive('make')->andReturn($sms);

        $module->shouldReceive('driver')->with('secondFactorAuth')->andReturn($secondFactor);

        $this->app->instance('module', $module);

        //mocking here since get test is already covered.

        $this->coreMock->shouldReceive('get')->withAnyArgs()->andReturn($expected);

        $this->userEntityMock->shouldReceive('getWrong2faAttempts')->withAnyArgs()->andReturn(5);

        $this->userEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $token = Mockery::mock('\RZP\Services\TokenService', [$this->app]);

        $token->shouldReceive('generate')->andReturn('100002Razorpay.1639136024');

        $this->app->instance('token_service', $token);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('get2FaAuthMode')->withAnyArgs()->andReturn('sms');

        $response = $this->userService->verifyUserSecondFactorAuth($input);

        $this->assertEquals($expected, $response);
    }

    public function testVerifyUserSecondFactorAuthIncorrectOtpException()
    {
        $input = [
            'otp' => '1244',
        ];

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP);

        $this->userEntityMock->shouldReceive('isAccountLocked')->withAnyArgs()->andReturn(false);

        $module = Mockery::mock('\RZP\Modules\Manager', [$this->app]);

        $secondFactor = Mockery::mock('\RZP\Mail\Merchant\SecondFactorAuth');

        $sms = Mockery::mock('\RZP\Modules\SecondFactorAuth\SmsOtpAuth');

        $sms->shouldReceive('is2faCredentialValid')->withAnyArgs()->andReturn(false);

        $secondFactor->shouldReceive('make')->andReturn($sms);

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('get2FaAuthMode')->withAnyArgs()->andReturn('sms');

        $orgMock->shouldReceive('getMerchantMaxWrong2FaAttempts')->withAnyArgs()->andReturn(9);

        $module->shouldReceive('driver')->with('secondFactorAuth')->andReturn($secondFactor);

        $this->app->instance('module', $module);

        $this->userEntityMock->shouldReceive('getWrong2faAttempts')->withAnyArgs()->andReturn(15);

        $this->userEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('getRestrictedAttribute')->withAnyArgs()->andReturn(true);

        $this->userEntityMock->shouldReceive('isAccountLocked')->andReturn(true);

        $this->userService->verifyUserSecondFactorAuth($input);
    }

    public function testGetUserFromEmail()
    {
        $content = [
            'email'             => 'dummy@example.com'
        ];

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->userRepoMock->shouldReceive('getUserFromEmail')->andReturn($this->userEntityMock);

        $response = $this->coreMock->getUserFromEmail($content);

        $this->assertEquals($this->userEntityMock, $response);
    }

    public function testAppendBankingSpecificDetails()
    {
        $content = [
            'id'                    => '1cXSLlUU8V9sXl',
            'banking_role'          => 'admin',
        ];

        $expected = [
            'id'                => '1cXSLlUU8V9sXl',
            'banking_role'      => 'admin',
            'banking_activated_at'  => 'dummuy date',
            'banking_balance'       => '24334',
        ];

        $r = new \ReflectionMethod('RZP\Models\User\Core', 'appendBankingSpecificDetails');

        $r->setAccessible(true);

        $balanceRepoMock = Mockery::mock('RZP\Models\Merchant\Balance\Repository');

        $balanceEntityMock = Mockery::mock('RZP\Models\Merchant\Balance\Entity');

        $balanceEntityMock->shouldReceive('toArray')->withAnyArgs()->andReturn(['items' => []]);


        $bankingAccountRepoMock = Mockery::mock('RZP\Models\BankingAccount\Repository');

        $merchantAttributeRepoMock = Mockery::mock('RZP\Models\Merchant\Attribute\Repository');

        $merchantAttributeEntityMock = Mockery::mock('RZP\Models\Merchant\Attribute\Entity');

        $merchantAttributeEntityMock->shouldReceive('toArrayPublic')->withAnyArgs()->andReturn(['items' => []]);

        $bankingAccountEntityMock = Mockery::mock('RZP\Models\BankingAccount\Entity')->makePartial();

        $creditBalanceRepoMock = Mockery::mock('RZP\Models\Merchant\Credits\Balance\Repository');

        $creditBalanceEntityMock = Mockery::mock('RZP\Models\Merchant\Credits\Balance\Entity')->makePartial();

        $bankingAccountEntityMock->shouldReceive('usingNewStates')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isStrictPrivateAuth')->andReturn(true);

        $this->basicAuthMock->shouldReceive('isAdminAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isPublicAuth')->andReturn(false);

        $this->basicAuthMock->shouldReceive('isProxyAuth')->andReturn(false);

        $merchantUserRepoMock = Mockery::mock('RZP\Models\Merchant\MerchantUser') ;

        $this->repoMock->shouldReceive('driver')->with('merchant_user')->andReturn($merchantUserRepoMock);

        $this->repoMock->shouldReceive('driver')->with('balance')->andReturn($balanceRepoMock);

        $this->repoMock->shouldReceive('driver')->with('banking_account')->andReturn($bankingAccountRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant_attribute')->andReturn($merchantAttributeRepoMock);

        $this->repoMock->shouldReceive('driver')->with('credit_balance')->andReturn($creditBalanceRepoMock);

        $merchantUserRepoMock->shouldReceive('fetchBankingSignUpTimeStampOfOwner')->andReturn(232434);

        $this->coreMock->shouldReceive('getBulkPayoutsUserType')->andReturn('existing_bulk_user_rupees');

        $balanceRepoMock->shouldReceive('getMerchantBalanceByTypeAndAccountType')->andReturn($balanceEntityMock);

        $balanceRepoMock->shouldReceive('getMerchantBalancesByTypeAndAccountTypeAndBalanceIds')->andReturn(new PublicCollection($balanceEntityMock));

        $bankingAccountRepoMock->shouldReceive('getBankingAccountsWithBalance')->andReturn([$bankingAccountEntityMock]);

        $merchantAttributeRepoMock->shouldReceive('getKeyValues')->andReturn($merchantAttributeEntityMock);

        $creditBalanceRepoMock->shouldReceive('getMerchantCreditBalanceByProduct')->andReturn([$creditBalanceEntityMock]);

        $bankingAccountRepoMock->shouldReceive('getActivatedBankingAccountFromBalanceId')->andReturn($bankingAccountEntityMock);

        $balanceEntityMock->shouldReceive('getId')->andReturn('1cXSLlUU8V9sXl');

        $balanceEntityMock->shouldReceive('getCreatedAt')->andReturn('dummuy date');

        $balanceEntityMock->shouldReceive('only')->andReturn('24334');

        $balanceEntityMock->shouldReceive('getAccountType')->andReturn('shared');

        $this->coreMock->shouldReceive('fetchBankingCreditBalances')->andReturn('24334');

        $response = $r->invoke($this->coreMock,[$content], '1cXSLlUU8V9sXl');

        $this->assertEquals($expected['id'], $response[0]['id']);

        $this->assertEquals($expected['banking_role'], $response[0]['banking_role']);

        $this->assertEquals($expected['banking_activated_at'], $response[0]['banking_activated_at']);

        $this->assertEquals($expected['banking_balance'], $response[0]['banking_balance']);
    }

    public function testAttach()
    {
        $content = [
            'role'              => 'finance',
            'product'           => 'banking',
            'merchant_id'       => '1cXSLlUU8V9sXl',
        ];

        $r = new \ReflectionMethod('RZP\Models\User\Core', 'attach');

        $r->setAccessible(true);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn($this->merchantEntityMock);

        $merchantUserMapping = Mockery::mock('RZP\Models\Merchant\MerchantUser\Entity');

        $this->merchantRepoMock->shouldReceive('getMerchantUserMapping')->andReturn($merchantUserMapping);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->coreMock->shouldReceive('restrictUserToOneRolePerMerchantAndProduct')->andReturn(false);

        $this->repoMock->shouldReceive('attach')->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $response = $r->invoke($this->coreMock,$this->userEntityMock, $content);

        $this->assertEquals([], $response);
    }

    public function testDetach()
    {
        $content = [
            'role'              => 'finance',
            'product'           => 'banking',
            'merchant_id'       => '1cXSLlUU8V9sXl',
        ];

        $r = new \ReflectionMethod('RZP\Models\User\Core', 'detach');

        $r->setAccessible(true);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn($this->merchantEntityMock);

        $this->repoMock->shouldReceive('detach')->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $response = $r->invoke($this->coreMock,$this->userEntityMock, $content);

        $this->assertEquals([], $response);
    }

    public function testUpdate()
    {
        $content = [
            'role'              => 'finance',
            'product'           => 'banking',
            'merchant_id'       => '1cXSLlUU8V9sXl',
        ];

        $r = new \ReflectionMethod('RZP\Models\User\Core', 'update');

        $r->setAccessible(true);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn($this->merchantEntityMock);

        $this->repoMock->shouldReceive('sync')->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $response = $r->invoke($this->coreMock,$this->userEntityMock, $content);

        $this->assertEquals([], $response);
    }

    public function testRestrictUserToOneRolePerMerchantAndProduct()
    {
        $r = new \ReflectionMethod('RZP\Models\User\Core', 'restrictUserToOneRolePerMerchantAndProduct');

        $r->setAccessible(true);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $response = $r->invoke($this->coreMock,$this->userEntityMock);

        $this->assertEquals(true, $response);
    }

    public function testDetachAndAttachMerchantUser()
    {
        $r = new \ReflectionMethod('RZP\Models\User\Core', 'detachAndAttachMerchantUser');

        $r->setAccessible(true);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('getValidator')->andReturn($this->userValidator);

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn($this->merchantEntityMock);

        $this->repoMock->shouldReceive('detach')->andReturn([]);

        $merchantUserMapping = Mockery::mock('RZP\Models\Merchant\MerchantUser\Entity');

        $this->merchantRepoMock->shouldReceive('getMerchantUserMapping')->andReturn($merchantUserMapping);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->coreMock->shouldReceive('restrictUserToOneRolePerMerchantAndProduct')->andReturn(false);

        $this->repoMock->shouldReceive('attach')->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $response = $r->invoke($this->coreMock,$this->userEntityMock,'1cXSLlUU8V9sXl', 'finance', 'banking');

        $this->assertEquals($this->userEntityMock, $response);
    }

    public function testValidateSelfUser()
    {
        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $r = $this->getReflectionObj('RZP\Models\User\Validator', 'validateSelfUser');

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_SELF_USER);

        $r->invoke($this->userValidator, ['user_id' => '100002Razorpay']);
    }

    public function testValidateCaptchaWithAllFailures()
    {
        $this->expectException(\WpOrg\Requests\Exception::class);

        $validatorMock = $this->getMockBuilder(Validator::class)
            ->setMethods(['makeRequestAndGetCaptchaVerificationResponse'])
            ->getMock();

        $validatorMock->method('makeRequestAndGetCaptchaVerificationResponse')
            ->will($this->onConsecutiveCalls($this->throwException(new \WpOrg\Requests\Exception('Error while verifying captcha', 'operation timed out')),
                                             $this->throwException(new \WpOrg\Requests\Exception('Error while verifying captcha', 'operation timed out')),
                                             $this->throwException(new \WpOrg\Requests\Exception('Error while verifying captcha', 'operation timed out'))));

        $validatorMockReflectionObj = new \ReflectionObject($validatorMock);

        $method = $validatorMockReflectionObj->getMethod('getCaptchaVerificationResponse');

        $method->setAccessible(true);

        $method->invoke($validatorMock, "url");
    }

    public function testValidateCaptchaWithTwoFailuresOneSuccess()
    {
        $successResponse = ["success => true"];

        $validatorMock = $this->getMockBuilder(Validator::class)
            ->setMethods(['makeRequestAndGetCaptchaVerificationResponse'])
            ->getMock();

        $validatorMock->method('makeRequestAndGetCaptchaVerificationResponse')
            ->will($this->onConsecutiveCalls($this->throwException(new \WpOrg\Requests\Exception('Error while verifying captcha', 'operation timed out')),
                                             $this->throwException(new \WpOrg\Requests\Exception('Error while verifying captcha', 'operation timed out')),
                                             $successResponse));

        $validatorMockReflectionObj = new \ReflectionObject($validatorMock);

        $method = $validatorMockReflectionObj->getMethod('getCaptchaVerificationResponse');

        $method->setAccessible(true);

        $response = $method->invoke($validatorMock, "url");

        $this->assertEquals($successResponse, $response);
    }

    public function testValidateCaptchaWithOneFailuresOneSuccess()
    {
        $successResponse = ["success => true"];

        $validatorMock = $this->getMockBuilder(Validator::class)
            ->setMethods(['makeRequestAndGetCaptchaVerificationResponse'])
            ->getMock();

        $validatorMock->method('makeRequestAndGetCaptchaVerificationResponse')
            ->will($this->onConsecutiveCalls($this->throwException(new \WpOrg\Requests\Exception('Error while verifying captcha', 'operation timed out')),
                                             $successResponse));

        $validatorMockReflectionObj = new \ReflectionObject($validatorMock);

        $method = $validatorMockReflectionObj->getMethod('getCaptchaVerificationResponse');

        $method->setAccessible(true);

        $response = $method->invoke($validatorMock, "url");

        $this->assertEquals($successResponse, $response);
    }

    public function testRavenRequestWithTwoFailures()
    {
        $this->expectException(ServerErrorException::class);

        $validatorMock = $this->getMockBuilder(RavenService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getRavenRequestResponse'])
            ->getMock();

        $validatorMock->method('getRavenRequestResponse')
            ->will($this->onConsecutiveCalls($this->throwException(new \WpOrg\Requests\Exception('Error while sending request to raven', 'operation timed out')),
                $this->throwException(new \WpOrg\Requests\Exception('Error while sending request to raven', 'operation timed out'))));

        $validatorMockReflectionObj = new \ReflectionObject($validatorMock);

        $method = $validatorMockReflectionObj->getMethod('sendRavenRequest');

        $method->setAccessible(true);

        $method->invoke($validatorMock, []);
    }

    public function testRavenRequestWithOneFailureOneSuccess()
    {
        $successResponse = ["success => true"];

        $validatorMock = $this->getMockBuilder(RavenService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getRavenRequestResponse'])
            ->getMock();

        $validatorMock->method('getRavenRequestResponse')
            ->will($this->onConsecutiveCalls($this->throwException(new \WpOrg\Requests\Exception('Error while sending request to raven', 'operation timed out')),
                $successResponse));

        $validatorMockReflectionObj = new \ReflectionObject($validatorMock);

        $method = $validatorMockReflectionObj->getMethod('sendRavenRequest');

        $method->setAccessible(true);

        $response = $method->invoke($validatorMock, []);

        $this->assertEquals($successResponse, $response);
    }

    public function testRavenGenerateOTPRequestOnFailureResponse()
    {
        $successResponse = ["success => true"];

        $validatorMock = $this->getMockBuilder(RavenService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $validatorMock->method('sendRequest')
            ->will($this->onConsecutiveCalls($successResponse));

        $validatorMockReflectionObj = new \ReflectionObject($validatorMock);

        $method = $validatorMockReflectionObj->getMethod('generateOtp');

        $method->setAccessible(true);

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage("Something went wrong, please try again after sometime.");

        $method->invoke($validatorMock, [], false);
    }

    public function testRavenGenerateOTPRequestOnSuccessResponse()
    {
        $successResponse = [
            "success => true",
            "otp" => "0007",
        ];

        $validatorMock = $this->getMockBuilder(RavenService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $validatorMock->method('sendRequest')
            ->will($this->onConsecutiveCalls($successResponse));

        $validatorMockReflectionObj = new \ReflectionObject($validatorMock);

        $method = $validatorMockReflectionObj->getMethod('generateOtp');

        $method->setAccessible(true);

        $response = $method->invoke($validatorMock, [], false);

        $this->assertEquals($successResponse, $response);
    }

    public function testValidateMerchantUserRelation()
    {
        $validator = new Validator();

        $this->merchantEntityMock->shouldReceive('users')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('get')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('getIds')->andReturn(['100002Razorpay']);

        $response = $validator->validateMerchantUserRelation($this->merchantEntityMock, $this->userEntityMock);

        $this->assertNull($response);
    }

    public function testValidateMerchantUserRelationException()
    {
        $validator = new Validator();

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT);

        $this->merchantEntityMock->shouldReceive('users')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('get')->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('getIds')->andReturn(['100002Razorpay']);

        $validator->validateMerchantUserRelation($this->merchantEntityMock, $this->userEntityMock);
    }

    public function testSaveOauthProviderForCurrentOAuthNull()
    {
        $core = new Core();

        $this->userEntityMock->shouldReceive('getOauthProvider')->andReturn(null);

        $response = $core->saveOauthProvider($this->userEntityMock, "[\"google\"]");

        $this->userEntityMock->shouldHaveReceived('setOauthProvider', ["[\"google\"]"]);

        $this->assertNotNull($response);
    }

    public function testSaveOauthProviderForCurrentOAuthExists()
    {
        $core = new Core();

        $this->userEntityMock->shouldReceive('getOauthProvider')->andReturn("[\"facebook\"]");

        $response = $core->saveOauthProvider($this->userEntityMock, "[\"google\"]");

        $this->userEntityMock->shouldHaveReceived('setOauthProvider', null);

        $this->assertNotNull($response);
    }

    public function testDummy()
    {
        $input = [
            'helloWorld'
        ];

        $request = Mockery::mock('Illuminate\Http\Request')->makePartial();

        $this->app->instance('request', $request);

        $request->shouldReceive('cookie')->withAnyArgs()->andReturn(true);

        $this->m2mReferralEntityMock = Mockery::mock('RZP\Models\Merchant\M2MReferral\Entity');

        $this->repoMock->shouldReceive('driver')->with('m2m_referral')->andReturn($this->m2mReferralEntityMock);

        $response = $this->userService->addUtmParameters($input);

        $this->assertNotNull($input);
    }


    public function testNotifyUserAboutAccountLocked()
    {
        Mail::fake();

        $mailMock = Mockery::mock('RZP\Mail');

        $orgMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $orgEntityMock = Mockery::mock('RZP\Models\Admin\Org\Entity');

        $mailMock->shouldReceive('send')->withAnyArgs()->andReturn([]);

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgMock);

        $orgMock->shouldReceive('findByPublicId')->withAnyArgs()->andReturn($orgMock);

        $orgMock->shouldReceive('isFeatureEnabled')->withAnyArgs()->andReturn(true);

        $r = new \ReflectionMethod('RZP\Models\User\Core', 'notifyUserAboutAccountLocked');

        $r->setAccessible(true);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->userEntityMock->shouldReceive('getEmail')->andReturn('dummy@example.com');

        $this->userEntityMock->shouldReceive('getName')->andReturn('dummy');

        $response = $r->invoke($this->coreMock,$this->userEntityMock);

        $this->assertEquals(null, $response);

    }

    public function testSendOtpForSecondFactorAuthOnLogin()
    {
        $r = new \ReflectionMethod('RZP\Models\User\Core', 'sendOtpForSecondFactorAuthOnLogin');

        $r->setAccessible(true);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->coreMock->shouldReceive('send2faOtp')->andReturn([]);

        $this->expectException(BadRequestException::class);

        $r->invoke($this->coreMock,$this->userEntityMock);
    }

    public function testPostLinkedAccountAccessEmail()
    {
        $expected = ['success' => true];

        $r = new \ReflectionMethod('RZP\Models\User\Service', 'postLinkedAccountAccessEmail');

        $r->setAccessible(true);

        Mail::fake();

        $mailMock = Mockery::mock('RZP\Mail');

        $orgRepoMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        $orgEntityMock = Mockery::mock('RZP\Models\Admin\Org\Entity');

        $this->repoMock->shouldReceive('driver')->with('org')->andReturn($orgRepoMock);

        $this->repoMock->shouldReceive('driver')->with('user')->andReturn($this->userRepoMock);

        $this->m2mReferralEntityMock = Mockery::mock('RZP\Models\Merchant\M2MReferral\Entity');

        $this->repoMock->shouldReceive('driver')->with('m2m_referral')->andReturn($this->m2mReferralEntityMock);

        $orgRepoMock->shouldReceive('findByPublicId')->andReturn($orgEntityMock);

        $orgEntityMock->shouldReceive('toArrayPublic')->andReturn([]);

        $this->userEntityMock->shouldReceive('toArrayPublic')->andReturn(['id'  => '100002Razorpay']);

        $mailMock->shouldReceive('queue')->withAnyArgs()->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getAttribute')->withAnyArgs()->andReturn($this->merchantEntityMock);

        $this->merchantEntityMock->shouldReceive('getName')->andReturn('parentName');

        $this->userRepoMock->shouldReceive('findOrFailPublic')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('setAttribute')->andReturn([]);

        $response = $r->invoke($this->userService,$this->userEntityMock, $this->merchantEntityMock);

        $this->assertEquals($expected, $response);
    }

    public function testSyncMerchantUserOnProducts()
    {
        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->merchantRepoMock->shouldReceive('getMerchantUserMapping')->andReturn();

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $this->merchantDetailRepoMock = Mockery::mock('RZP\Models\Merchant\Detail\Repository');

        $this->repoMock->shouldReceive('driver')->with('merchant_detail')->andReturn($this->merchantDetailRepoMock);

        $this->merchantDetailRepoMock->shouldReceive('getBusinessType')->andReturn('proprietorship');

        $this->merchantDetailRepoMock->shouldReceive('getByMerchantId')->andReturn($this->merchantDetailRepoMock);

        $this->merchantRepoMock->shouldReceive('getMerchantOrg')->andReturn('100000razorpay');

        $response = $this->userService->syncMerchantUserOnProducts('10000000000');

        $this->assertEquals(null , $response);
    }

    public function testAddUtmParameters()
    {
        // Encode the data.
        $json = json_encode(
            array(
                'attributions' => array(
                    'attribute1',
                    'attribute2'
                )
            )
        );

        $request = Mockery::mock('Illuminate\Http\Request')->makePartial();

        $this->app->instance('request', $request);

        $request->shouldReceive('cookie')->withAnyArgs()->andReturn($json);

        $data = [
        ];

        $this->userService->addUtmParameters($data);

        $this->assertArrayHasKey('cta', $data);
    }

    public function testAddProductSwitchRole1()
    {
        $merchantMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($merchantMock);

        $this->userEntityMock->shouldReceive('getId')->andReturn('100002Razorpay');

        $merchantMock->shouldReceive('getMerchantUserMapping')->withAnyArgs()->andReturn();

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->coreMock->shouldReceive('attach')->andReturn([]);

        $this->coreMock->shouldReceive('updateUserMerchantMapping')->andReturn($this->userEntityMock);

        $response = $this->userService->addProductSwitchRole('banking');

        $this->assertEquals($this->userEntityMock, $response);
    }

    public function testGetUnifiedMerchants()
    {
        $merchants = [
            '100002Razorpay' => ['id' => '100002Razorpay', 'role' => 'owner', 'product' => 'banking'],
        ];

        $r = $this->getReflectionObj('RZP\Models\User\Core', 'getUnifiedMerchants');

        $response = $r->invoke($this->coreMock, $merchants);

        $this->assertEquals($response[0]['banking_role'], 'owner');
    }

    public function testSendOtpViaSmsAndEmail()
    {
        $this->coreMock->shouldReceive('generateOtpFromRaven')->andReturn(['token' => '25521']);

        $response = $this->coreMock->sendOtpViaSmsAndEmail([], $this->merchantEntityMock, $this->userEntityMock);

        $this->assertEquals($response['token'], '25521');
    }

    public function testCheckAccessForMerchant()
    {
        $this->userEntityMock->shouldReceive('belongsToMany')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('withPivot')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('where')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('get')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('callOnEveryItem')->withAnyArgs()->andReturn(['100002Razorpay' => ['product' => 'banking']]);

        //already covered as independent functions

        $this->coreMock->shouldReceive('getUnifiedMerchants')->withAnyArgs()->andReturn([]);

        //already covered as independent functions

        $this->coreMock->shouldReceive('appendBankingSpecificDetails')->andReturn(['100000Razorpay']);

        $response = $this->coreMock->checkAccessForMerchant($this->userEntityMock, '100002Razorpay', 'banking');

        $this->assertEquals(['access' => true, 'merchant' => '100000Razorpay'], $response);
    }

    public function testCheckAccessForMerchantException()
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_ID);

        $this->userEntityMock->shouldReceive('belongsToMany')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('withPivot')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('where')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('get')->withAnyArgs()->andReturn($this->userEntityMock);

        $this->userEntityMock->shouldReceive('callOnEveryItem')->withAnyArgs()->andReturn([]);

        $this->userEntityMock->shouldReceive('getId')->withAnyArgs()->andReturn(['100000Razorpay']);

        $this->coreMock->checkAccessForMerchant($this->userEntityMock, '100002Razorpay', 'banking');
    }

    public function testOptInForWhatsappException()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('User does not have a mobile number associated with the account');

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $this->userService->optInForWhatsapp(['source' => 'api.merchant.onboarding', 'send_welcome_message' => true]);
    }

    public function testOptInForWhatsapp()
    {
        $this->userEntityMock->shouldReceive('getContactMobile')->andReturn('9876543210');

        $this->userValidator->shouldReceive('validateInput')->andReturn([]);

        $response = $this->userService->optInForWhatsapp(['source' => 'api.merchant.onboarding', 'send_welcome_message' => true]);

        $this->assertNotNull($response);
    }

    public function getReflectionObj($class, $methodName) : \ReflectionMethod
    {
        $r = new \ReflectionMethod($class, $methodName);

        $r->setAccessible(true);

        return $r;
    }

    public function createTestDependencyMocks()
    {
        // User Entity Mocking
        $this->userEntityMock = Mockery::mock('RZP\Models\User\Entity')->makePartial()->shouldAllowMockingProtectedMethods();

        // Merchant Mocking
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        // Device Entity Mocking
        $this->deviceEntityMock = Mockery::mock('RZP\Models\Device\Entity');

        // User Repo Mocking
        $this->userRepoMock = Mockery::mock('RZP\Models\User\Repository');

        // Merchant Repo mocking
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        // Merchant Service Mocking
        $this->merchantServiceMock = Mockery::mock('RZP\Models\Merchant\Service');

        // M2M Referral Service Mocking
        $this->m2mReferralServiceMock = Mockery::mock('RZP\Models\Merchant\M2MReferral\Service');

        // Org Repo Mocking
        $this->orgRepoMock = Mockery::mock('RZP\Models\Admin\Org\Repository');

        // BasicAuth mocking
//        $this->basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth');

        $this->basicAuthMock->shouldReceive('getRequestOriginProduct')->andReturn('banking');

        $this->basicAuthMock->shouldReceive('getMerchant')->andReturn($this->merchantEntityMock);

        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('getOrgId')->andReturn('org_100000razorpay');

        $this->basicAuthMock->shouldReceive('getOrgHostName')->andReturn('Razorpay');

        $this->basicAuthMock->shouldReceive('getMode')->andReturn('test');

        $this->basicAuthMock->shouldReceive('getProduct')->andReturn('banking');

        $this->basicAuthMock->shouldReceive('getMerchantId')->andReturn('10000000000');

        $this->basicAuthMock->shouldReceive('getDevice')->andReturn($this->deviceEntityMock);

//        $this->app->instance('basicauth', $this->basicAuthMock);

        $this->hubspotMock->shouldReceive('trackConfirmEmailEvent')->andReturn([]);

        // RepositoryManager Mocking
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();

        $this->repoMock->shouldReceive('transactionOnLiveAndTest')->andReturn([]);

        $this->app->instance('repo', $this->repoMock);

        $this->repoMock->shouldReceive('saveOrFail')->andReturn([]);

        // Core Mocking Partial
        $this->coreMock = Mockery::mock('RZP\Models\User\Core', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();;

        // User Validator mocking
        $this->userValidator = Mockery::mock('RZP\Models\User\Validator');

        $this->diagClientMock->shouldReceive('trackOnboardingEvent')->andReturn([]);

        $this->hubspotMock->shouldReceive('trackSignupEvent')->andReturn([]);

        $this->hubspotMock->shouldReceive('trackHubspotEvent')->andReturn([]);
    }

    public function createMockedUserEntity(array $input = []): Entity
    {
        $content = [
            'getUserId'        => '100002Razorpay',
            'getName'          => 'dummy',
            'getEmail'         => 'dummy@example.com',
            'getPassword'      => 'blahblah123',
            'getContactMobile' => '9999999999',
            'getConfirmToken'  => 'hello123',
        ];

        return $this->createConfiguredMock(Entity::class, array_merge($content, $input));
    }

    private function getDummyUser($index)
    {
        $email = 'dummy'.$index.'User@example.com';

        $userData = [
            'name'                  => 'dummy',
            'email'                 => $email,
            'password'              => 'blahblah123',
            'password_confirmation' => 'blahblah123',
            'contact_mobile'        => '8888888888',
            'confirm_token'         => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        return ((new Core())->create($userData));
    }

    private function getDummyMerchant($index)
    {
        $email = 'dummy'.$index.'Merchant@example.com';

        $merchantData = [
            'name'                  => 'dummy',
            'email'                 => $email,
            'org_id'                => 'org100razorpay',
            'signup_source'         => 'banking',
        ];

        return (new MerchantEntity())->build($merchantData);
    }

    public function testGetContextFromActionForPayoutLinkCreation()
    {
        $input = [
            'action'            => 'create_payout_link',
            'amount'            => 100,
            'purpose'           => 'refund',
            'account_number'    => '4564563559247998',
            'contact'           => [
                'contact'   => '9999999999',
                'email'     => 'test@razorpay.com',
            ]
        ];

        $user = $this->getDummyUser(1);

        $merchant = $this->getDummyMerchant(1);

        $token = Entity::generateUniqueId();

        $r = $this->getReflectionObj('RZP\Models\User\Core', 'getContextFromAction');

        $response = $r->invoke($this->coreMock, $merchant, $user, $input, $token);

        $expectedContext = sprintf('%s:%s:%s:%s:%s:%s:%s',
                           $merchant->getId(),
                           $user->getId(),
                           Constants::CREATE_PAYOUT_LINK,
                           $input[Constants::ACCOUNT_NUMBER],
                           $token,
                           100,
                           "9999999999");

        $this->assertEquals(hash('sha3-512', $expectedContext), $response);
    }

    public function testGetContextFromActionForPayoutLinkCreationContactNumberKeyMissing()
    {
        $input = [
            'action'            => 'create_payout_link',
            'amount'            => 100,
            'purpose'           => 'refund',
            'account_number'    => '4564563559247998',
            'contact'           => [
                'email'     => 'test@razorpay.com',
            ]
        ];

        $user = $this->getDummyUser(2);

        $merchant = $this->getDummyMerchant(2);

        $token = Entity::generateUniqueId();

        $r = $this->getReflectionObj('RZP\Models\User\Core', 'getContextFromAction');

        $response = $r->invoke($this->coreMock, $merchant, $user, $input, $token);

        $expectedContext = sprintf('%s:%s:%s:%s:%s:%s:%s',
                                   $merchant->getId(),
                                   $user->getId(),
                                   Constants::CREATE_PAYOUT_LINK,
                                   $input[Constants::ACCOUNT_NUMBER],
                                   $token,
                                   100,
                                   "test@razorpay.com");

        $this->assertEquals(hash('sha3-512', $expectedContext), $response);
    }

    public function testGetContextFromActionForPayoutLinkCreationContactNumberKeyPresentButMissingValue()
    {
        $input = [
            'action'            => 'create_payout_link',
            'amount'            => 100,
            'purpose'           => 'refund',
            'account_number'    => '4564563559247998',
            'contact'           => [
                'contact'   => '',
                'email'     => 'test@razorpay.com',
            ]
        ];

        $user = $this->getDummyUser(3);

        $merchant = $this->getDummyMerchant(3);

        $token = Entity::generateUniqueId();

        $r = $this->getReflectionObj('RZP\Models\User\Core', 'getContextFromAction');

        $response = $r->invoke($this->coreMock, $merchant, $user, $input, $token);

        $expectedContext = sprintf('%s:%s:%s:%s:%s:%s:%s',
                                   $merchant->getId(),
                                   $user->getId(),
                                   Constants::CREATE_PAYOUT_LINK,
                                   $input[Constants::ACCOUNT_NUMBER],
                                   $token,
                                   100,
                                   "test@razorpay.com");

        $this->assertEquals(hash('sha3-512', $expectedContext), $response);
    }

    public function testGetContextFromActionForPayoutLinkCreationNoContactInRequest()
    {
        $input = [
            'action'            => 'create_payout_link',
            'amount'            => 100,
            'purpose'           => 'refund',
            'account_number'    => '4564563559247998'
        ];

        $user = $this->getDummyUser(3);

        $merchant = $this->getDummyMerchant(3);

        $token = Entity::generateUniqueId();

        $r = $this->getReflectionObj('RZP\Models\User\Core', 'getContextFromAction');

        $response = $r->invoke($this->coreMock, $merchant, $user, $input, $token);

        $expectedContext = sprintf('%s:%s:%s:%s',
                                   $merchant->getId(),
                                   $user->getId(),
                                   Constants::CREATE_PAYOUT_LINK,
                                   $token);

        $this->assertEquals($expectedContext, $response);
    }
}
