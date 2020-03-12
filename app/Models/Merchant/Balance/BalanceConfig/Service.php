<?php

namespace RZP\Models\Merchant\Balance\BalanceConfig;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Account;
use RZP\Exception\BadRequestValidationFailureException;

class Service extends Base\Service
{
    /**
     * Fetches Balance Configs for this $merchantId
     *
     * @param string $merchantId
     * @return array BalanceConfig
     */
    public function getMerchantBalanceConfigs(): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($this->auth->getMerchantId());

        if (($this->mode === Mode::LIVE) and
            ($merchant->isActivated() === false) and
            (Account::isNodalAccount($merchant->getId()) === false))
        {
            $balanceConfigs = new Base\PublicCollection();

            $balanceConfig[Entity::TYPE]    = 'primary';
            $balanceConfig[Entity::NEGATIVE_LIMIT_AUTO]    = 0;
            $balanceConfig[Entity::NEGATIVE_LIMIT_MANUAL]  = 0;

            $balanceConfigs->add($balanceConfig);

            return $balanceConfigs->toArrayWithItems();
        }

        $balances = $this->repo->balance->getMerchantBalances($merchant->getId());

        $balanceIds = $balances->getIds();

        $balanceConfigs = $this->repo->balance_config->getBalanceConfigsForBalanceIds($balanceIds);

        return $balanceConfigs->toArrayWithItems();
    }

    /**
     * Fetches BalanceConfig by a given $balanceConfigId
     *
     * @param string $balanceConfigId
     */
    public function getBalanceConfig(string $balanceConfigId)
    {
        return $this->repo->balance_config->findOrFail($balanceConfigId);
    }

    /**
     * Create BalanceConfig for a given $merchantId, based on $input parameters
     *
     * @param string $merchantId
     * @param array $input
     * @throws BadRequestValidationFailureException
     */
    public function createBalanceConfig(string $merchantId, array $input): Entity
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return (new Core)->createBalanceConfig($merchant, $input);
    }

    /**
     * Edits an existing BalanceConfig for a given $balanceConfigId,
     * based on given $input parameters
     *
     * @param array $input
     * @param $balanceConfigId
     * @return Entity BalanceConfig
     * @throws BadRequestValidationFailureException
     */
    public function editBalanceConfig(array $input, $balanceConfigId) : Entity
    {
        $balanceConfig = $this->repo->balance_config->findOrFailPublic($balanceConfigId);

        $balanceConfig = (new Core)->editBalanceConfig($balanceConfig, $input);

        return $balanceConfig;
    }
}
