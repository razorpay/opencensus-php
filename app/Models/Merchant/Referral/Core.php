<?php

namespace RZP\Models\Merchant\Referral;

use RZP\Constants\Product;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\HyperTrace;
use RZP\Exception\BadRequestException;

class Core extends Base\Core
{
    const NAME_LENGTH = 9;

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
     *
     * @param $refCode
     * @param $dashboardUrl
     *
     * @return String
     */
    public function createShortenReferralUrl($refCode, $dashboardUrl): String
    {
        // Adds type label & dashboard path for referral.

        $longUrl = $dashboardUrl . "signup?referral_code=";

        $longUrl = $longUrl . $refCode;

        $shortenUrl = $this->elfin->shorten($longUrl);

        return $shortenUrl;
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

        $productConfig = array( Product::PRIMARY => $this->config['applications.dashboard.url'],

                                Product::BANKING => $this->config['applications.banking_service_url'] . '/auth/');


        $referrals = Tracer::inspan(['name' => HyperTrace::CREATE_REFERRAL_CORE], function () use($referrals, $merchant, $productConfig) {

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
     * @param $productConfig
     *
     * @return array
     */
    protected function create(Merchant\Entity $merchant, $productConfig)
    {
        // Calling this before the get call below to avoid calling validator
        // explicitly as build method will call it. The following get is to

        $newReferrals = [];

        foreach($productConfig as $product => $dashboardUrl)
        {
            $refCode = Tracer::inspan(['name' => HyperTrace::GENERATE_REFERRAL_CODE], function () use($merchant) {

                return $this->generateReferralCode($merchant);
            });

            $input[Entity::REF_CODE] = $refCode;

            $shortenUrl = $this->createShortenReferralUrl($refCode, $dashboardUrl);

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

            $this->trace->count(Metric::MERCHANT_REFERRAL_CREATE_SUCCESS_TOTAL,
                               [
                                   'product' => $product,
                                   'partner_type' => $merchant->getPartnerType()
                               ]);

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
}
