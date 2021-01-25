<?php

namespace RZP\Models\Merchant\Referral;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
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
     * @return Entity
     * @throws BadRequestException
     */
    public function fetchMerchantReferral(Merchant\Entity $merchant): Entity
    {
        $referral = $this->repo->referrals->getReferralByMerchantId($merchant->getId());

        $this->trace->info(
            TraceCode::MERCHANT_REFERRAL_FETCH_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'referral'    => $referral
            ]);

        if ($referral === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_REFERRAL_DOES_NOT_EXIST);
        }

        return $referral;
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
     *
     * @return String
     */
    public function createShortenReferralUrl($refCode): String
    {
        // Adds type label & dashboard path for referral.

        $dashboardUrl = $this->config['applications.dashboard.url'];

        $longUrl = $dashboardUrl . "/signup?referral_code=";

        $longUrl = $longUrl . $refCode;

        $shortenUrl = $this->elfin->shorten($longUrl);

        return $shortenUrl;
    }

    /**
     * Either creates or fetch merchant referral
     *
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createOrFetch(Merchant\Entity $merchant): Entity
    {
        $referral = $this->repo->referrals->getReferralByMerchantId($merchant->getId());

        if (empty($referral) === true)
        {
            $referral = $this->create($merchant);
        }

        return $referral;
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    protected function create(Merchant\Entity $merchant): Entity
    {
        // Calling this before the get call below to avoid calling validator
        // explicitly as build method will call it. The following get is to

        $refCode = $this->generateReferralCode($merchant);

        $input[Entity::REF_CODE] = $refCode;

        $shortenUrl = $this->createShortenReferralUrl($refCode);

        $input[Entity::URL] = $shortenUrl;

        $newReferral = (new Entity)->build($input);

        $newReferral->merchant()->associate($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_REFERRAL_CREATE_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
                'input'       => $input
            ]);

        $this->repo->saveOrFail($newReferral);

        return $newReferral;
    }
}
