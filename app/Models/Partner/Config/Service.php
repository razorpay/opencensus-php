<?php

namespace RZP\Models\Partner\Config;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;

use Razorpay\OAuth\Application as OAuthApp;

class Service extends Base\Service
{
    /**
     * @var OAuthApp\Repository
     */
    private $applicationRepo;

    public function __construct()
    {
        parent::__construct();

        $this->applicationRepo = new OAuthApp\Repository;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function create(array $input) : array
    {
        $application = $this->getApplicationFromInput($input);
        $subMerchant = $this->getSubMerchantFromInput($input);

        $config = (new Core)->create($application, $input, $subMerchant);

        return $config->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @return OAuthApp\Entity
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    protected function getApplicationFromInput(array $input) : OAuthApp\Entity
    {
        $this->validateConfigInput($input);

        $application = null;

        if (empty($input[Constants::APPLICATION_ID]) === false)
        {
            $application = $this->applicationRepo->findOrFailPublic($input[Constants::APPLICATION_ID]);
        }
        else if (empty($input[Constants::PARTNER_ID]) === false)
        {
            $partnerMerchantId = $input[Constants::PARTNER_ID];

            $partnerMerchantId = Account\Entity::verifyIdAndSilentlyStripSign($partnerMerchantId);

            $partnerMerchant = $this->repo->merchant->findOrFailPublic($partnerMerchantId);

            // Block non partners
            (new Merchant\Validator)->validateIsPartner($partnerMerchant);

            // Block pure platform if partner id is sent instead of app id
            if ($partnerMerchant->isNonPurePlatformPartner() === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PARTNER_ID_SENT_FOR_PURE_PLATFORM,
                    Constants::PARTNER_ID,
                    [
                        Constants::PARTNER_ID         => $partnerMerchant->getId(),
                        Merchant\Entity::PARTNER_TYPE => $partnerMerchant->getPartnerType(),
                    ]);
            }

            $application = (new Merchant\Core())->fetchPartnerApplication($partnerMerchant);
        }

        return $application;
    }

    /**
     * @param array $input
     *
     * @return Merchant\Entity|null
     */
    protected function getSubMerchantFromInput(array $input)
    {
        $subMerchant = null;

        if (empty($input[Constants::SUBMERCHANT_ID]) === false)
        {
            $subMerchantId = $input[Constants::SUBMERCHANT_ID];

            $subMerchantId = Account\Entity::verifyIdAndSilentlyStripSign($subMerchantId);

            $subMerchant = $this->repo->merchant->findOrFailPublic($subMerchantId);
        }

        return $subMerchant;
    }

    /**
     * If only the partner_id or application_id is passed in the input,
     * an array of configurations are returned which includes the default application config and
     * all the overridden configs for that partner/application.
     *
     * If the submerchant_id is sent along with partner_id or application id,
     * the relevant submerchant config (overridden config, if available; else default config) is returned.
     *
     * @param array $input
     *
     * @return array|null
     * @throws Exception\BadRequestException
     */
    public function fetch(array $input)
    {
        $application = $this->getApplicationFromInput($input);
        $subMerchant = $this->getSubMerchantFromInput($input);

        $core       = new Core;
        $configData = null;

        if (empty($subMerchant) === true)
        {
            $configs     = $core->fetchAllConfigForApp($application);
            $configData  = $configs->toArrayPublicEmbedded();
        }
        else
        {
            $config      = $core->fetch($application, $subMerchant);
            $configData  = optional($config)->toArrayPublic();
        }

        return $configData;
    }

    public function update(string $id, array $input) : array
    {
        $config = (new Core)->edit($id, $input);

        return $config->toArrayPublic();
    }

    public function fetchConfigByPartner(): array
    {
        (new Merchant\Validator)->validateIsPartner($this->merchant);

        $configs = $this->core()->fetchAllConfigsByPartner($this->merchant);

        return $configs->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function validateConfigInput(array $input)
    {
        // check if both application id and partner id are absent
        if ((empty($input[Constants::APPLICATION_ID]) === true) and
            (empty($input[Constants::PARTNER_ID]) === true))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_APPLICATION_ID_OR_PARTNER_ID_MISSING);
        }

        // check if both application id and partner id are present
        if ((empty($input[Constants::PARTNER_ID]) === false) and (empty($input[Constants::APPLICATION_ID]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_APPLICATION_ID_PARTNER_ID_BOTH_PRESENT,
                null,
                [
                    Constants::APPLICATION_ID => $input[Constants::PARTNER_ID],
                    Constants::PARTNER_ID     => $input[Constants::APPLICATION_ID],
                ]
            );
        }
    }
}
