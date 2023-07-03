<?php

namespace RZP\Models\Merchant\Referral;

use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Constants\HyperTrace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\CapitalSubmerchantUtility;

class Core extends Base\Core
{
    const NAME_LENGTH = 9;
    const EASY_ONBOARDING_TYPE_PARAM = "eo";

    /**
     * Elfin: Url shortening service
     */
    protected $elfin;

    public function __construct()
    {
        parent::__construct();

        $this->elfin = $this->app['elfin'];
    }

    /**
     * @return array[]
     */
    protected function getReferralConfig(): array
    {
        return [
            Product::PRIMARY => [
                "url"    => $this->config['applications.dashboard.url'] . 'signup',
                "params" => [
                    "referral_code" => null,
                ]
            ],
            Product::BANKING => [
                "url"    => $this->config['applications.banking_service_url'] . '/auth/signup',
                "params" => [
                    "referral_code" => null,
                ]
            ],
        ];

    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws BadRequestException
     */
    public function fetchMerchantReferral(Merchant\Entity $merchant)
    {
        $referrals = $this->repo->referrals->getReferralByMerchantId($merchant->getId());

        $referralsMap = [];

        for ($i = 0; $i < count($referrals); $i++){

            $referral = $referrals[$i];

            $referralsMap[$referral->getProduct()] = $referral->toArrayPublic();;
        }

        $this->trace->info(
            TraceCode::MERCHANT_REFERRAL_FETCH_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'referral'    => $referrals
            ]);

        if ($referrals === null || count($referrals) == 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_REFERRAL_DOES_NOT_EXIST);
        }

        return $referralsMap;
    }

    /**
     * Return the Referral Entity if ref_code is valid
     *
     * @param $refCode
     *
     * @return Entity|null
     */
    public function fetchReferralByReferralCode($refCode): ?Entity
    {
        $referral = $this->repo->referrals->getReferralByReferralCode($refCode);

        return $referral;
    }

    /**
     * Takes merchant Entity  and creates unique referral from merchant name
     *
     * @param Merchant\Entity $merchant
     *
     * @return String
     */
    public function generateReferralCode(Merchant\Entity $merchant): String
    {
        $merchantName = $merchant->getName();

        $merchantSubname = strtolower(trim(preg_replace('/[^A-Za-z0-9]/', '', $merchantName)));

        $namePrefix = substr($merchantSubname, 0, self::NAME_LENGTH);

        $namePrefixLength = strlen($namePrefix);

        $padding = Entity::ID_LENGTH - $namePrefixLength;

        $paddingText = random_alphanum_string($padding);

        $refCode = $namePrefix . $paddingText;

        if ($this->fetchReferralByReferralCode($refCode) !== null)
        {
            $this->trace->info(
                TraceCode::MERCHANT_REFERRAL_CODE_CREATE_CONFLICT,
                [
                    'merchant_id' => $merchant->getId(),
                    'refCode'     => $refCode
                ]);

            $refCode = (Entity::generateUniqueId());
        }

        return $refCode;
    }

    /**
     * Create Short url for merchant referral
     * & append the url params
     *
     * @param      $dashboardUrl
     * @param null $urlParams
     *
     * @return String
     */
    public function createShortenReferralUrl(string $dashboardUrl, array $urlParams = []): string
    {
        // Adds type label & dashboard path for referral.

        $longUrl = $dashboardUrl . "?";

        $longUrl = $longUrl . http_build_query($urlParams);

        return $this->elfin->shorten($longUrl);
    }

    /**
     * Either creates or fetch merchant referrals
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function createOrFetch(Merchant\Entity $merchant)
    {
        $referrals = $this->repo->referrals->getReferralByMerchantId($merchant->getId());

        $productConfig = $this->getReferralConfig();

        $isExpEnabled = (new CapitalSubmerchantUtility())->isCapitalPartnershipEnabledForPartner($merchant->getId());

        $generateNewCapitalReferralLinkExpEnabled = (new CapitalSubmerchantUtility())->isGenerateNewCapitalReferralLinkExpEnabled($merchant->getId());

        if ($isExpEnabled === true)
        {
            $this->trace->info(
                TraceCode::PARTNER_REFERRAL_LINK_FOR_CAPITAL,
                [
                    "partner_id"                                => $merchant->getId(),
                    "create_new_capital_referral_link_enabled"  => $generateNewCapitalReferralLinkExpEnabled
                ]
            );

            if($generateNewCapitalReferralLinkExpEnabled === true)
            {
                $url = Merchant\Constants::RAZORPAY_LINE_OF_CREDIT_SIGN_UP;
            }
            else
            {
                $url = $this->config['applications.banking_service_url'] . '/auth/signup';
            }

            $productConfig[Product::CAPITAL] = [
                "url"    => $url,
                "params" => [
                    "referral_code" => null,
                    "intent"        => Merchant\Attribute\Type::CAPITAL_LOC_EMI,
                ]
            ];
        }

        $this->addOptionalParams($productConfig, $merchant);

        $referrals = Tracer::inspan(['name' => HyperTrace::CREATE_REFERRAL_CORE], function() use ($referrals, $merchant, $productConfig) {

            if (empty($referrals) === true)
            {
                return $this->create($merchant, $productConfig);
            }
            else
            {
                list($missingReferralConfig, $existingReferrals) = $this->findMissingReferrals($referrals, $productConfig);

                $missingReferrals = $this->create($merchant, $missingReferralConfig);

                return array_merge($existingReferrals, $missingReferrals);
            }
        });

        return $referrals;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $productConfigList
     *
     * @return array
     */
    protected function create(Merchant\Entity $merchant, array $productConfigList): array
    {
        // Calling this before the get call below to avoid calling validator
        // explicitly as build method will call it. The following get is to

        $newReferrals = [];

        foreach ($productConfigList as $product => $productConfig)
        {
            $refCode = Tracer::inspan(['name' => HyperTrace::GENERATE_REFERRAL_CODE], function() use ($merchant) {

                return $this->generateReferralCode($merchant);
            });

            $input[Entity::REF_CODE] = $refCode;

            $productConfig["params"]["referral_code"] = $refCode;

            $shortenUrl = $this->createShortenReferralUrl(
                $productConfig["url"],
                $productConfig["params"]
            );

            $input[Entity::URL] = $shortenUrl;

            $input[Entity::PRODUCT] = $product;

            $newReferral = (new Entity)->build($input);

            $newReferral->merchant()->associate($merchant);

            $this->trace->info(
                TraceCode::MERCHANT_REFERRAL_CREATE_REQUEST,
                [
                    'merchant_id' => $merchant->getId(),
                    'input'       => $input
                ]);

            $this->repo->saveOrFail($newReferral);

            $this->trace->count(
                Metric::MERCHANT_REFERRAL_CREATE_SUCCESS_TOTAL,
                [
                    'product'      => $product,
                    'partner_type' => $merchant->getPartnerType()
                ]
            );

            $newReferrals[$product] = $newReferral->toArrayPublic();
        }

        return $newReferrals;
    }

    /**
     * @param $referrals
     * @param array $productConfig
     * @return array[]
     */
    public function findMissingReferrals($referrals, array $productConfig): array
    {
        $missingReferralConfig = [];

        $existingReferralsMap = [];

        foreach ($referrals as $referral)
        {
            $existingReferralsMap[$referral->getProduct()] = $referral->toArrayPublic();;
        }

        foreach ($productConfig as $product => $config)
        {
            if (array_key_exists($product, $existingReferralsMap) === false)
            {
                $missingReferralConfig[$product] = $config;
            }
        }
        return array($missingReferralConfig, $existingReferralsMap);
    }

    public function fetchPartnerReferral(Merchant\Entity $merchant, string $product)
    {
        $merchantValidator = new Merchant\Validator();

        $merchantValidator->validateIsPartner($merchant);

        $merchantValidator->validateMerchantProduct($product);

        $referrals = $this->repo->referrals->getReferralByMerchantIdAndProduct($merchant->getId(), $product);

        if ($referrals->isEmpty() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_REFERRAL_DOES_NOT_EXIST);
        }

        return $referrals->first();
    }

    /**
     * @param PublicCollection $partners
     *
     * @return void
     * @throws Throwable
     */
    public function regenerate(PublicCollection $partners): void
    {
        $productConfig = $this->getReferralConfig();

        $productConfig[Product::CAPITAL] = [
            "url"    => $this->config['applications.banking_service_url'] . '/auth/signup',
            "params" => [
                "referral_code" => null,
                "intent"        => Merchant\Attribute\Type::CAPITAL_LOC_EMI,
            ]
        ];

        $this->repo->transactionOnLiveAndTest(function() use ($partners, $productConfig) {

            $ids = $partners->pluck(Entity::ID)->toArray();

            $oldReferrals = $this->repo->referrals->getReferralsByMerchantIds($ids);

            foreach ($oldReferrals as $referral)
            {
                $refCode = $referral->getReferralCode();

                $oldUrl = $referral->getReferralLink();

                $generateNewCapitalReferralLinkExpEnabled = (new CapitalSubmerchantUtility())->isGenerateNewCapitalReferralLinkExpEnabled($referral->getMerchantId());

                if($generateNewCapitalReferralLinkExpEnabled)
                {
                    $productConfig[Product::CAPITAL]["url"] = Merchant\Constants::RAZORPAY_LINE_OF_CREDIT_SIGN_UP;
                }

                $productConfig[$referral->getProduct()]["params"]["referral_code"] = $refCode;

                $newShortUrl = $this->createShortenReferralUrl(
                    $productConfig[$referral->getProduct()]["url"],
                    $productConfig[$referral->getProduct()]["params"]
                );

                $referral[Entity::URL] = $newShortUrl;

                $this->repo->saveOrFail($referral);

                $this->trace->info(
                    TraceCode::PARTNER_REFERRAL_LINK_REGENERATE,
                    [
                        'partner_id' => $referral->getMerchantId(),
                        'product'    => $referral->getProduct(),
                        'new_url'    => $referral->getReferralLink(),
                        'old_url'    => $oldUrl,
                    ]
                );

            }
        });
    }

    /**
     * @param array $productConfig
     * @param Merchant\Entity $merchant
     * @return void
     */
    private function addOptionalParams(array &$productConfig, Merchant\Entity $merchant): void
    {

        if ($merchant->getPartnerType() === Merchant\Constants::RESELLER)
        {
            // setting easy type for easy redirection for on-boarding
            $productConfig[Product::PRIMARY]["params"][self::EASY_ONBOARDING_TYPE_PARAM] = "1";
        }

    }
}
