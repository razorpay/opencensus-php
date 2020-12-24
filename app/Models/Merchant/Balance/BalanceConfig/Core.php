<?php

namespace RZP\Models\Merchant\Balance\BalanceConfig;

use Mail;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicEntity;
use RZP\Mail\Merchant\FeatureEnabled;

class Core extends Base\Core
{
    const NEGATIVE_LIMIT_MANUAL                    = 'negative_limit_manual';

    const NEGATIVE_LIMIT_AUTO                      = 'negative_limit_auto';

    const NEGATIVE_TRANSACTION_FLOWS               = 'negative_transaction_flows';

    const DEFAULT_PG_FLOWS                          = ['payment'];

    const NEGATIVE_BALANCE_FEATURE                  = 'negative_balance';

    const NEGATIVE_BALANCE_FEATURE_DISPLAY_NAME     = 'Negative Balance Feature';

    const NEGATIVE_BALANCE_DOCUMENTATION             = [
        'e-mandate'     => 'https://razorpay.com/docs/payment-gateway/balances/negative-balance-emandate/',
        'refund_route'  => 'https://razorpay.com/docs/payment-gateway/balances/negative-balance-route/'
    ];

    /**
     * Add BalanceConfig Entity for given Merchant $merchant
     * for Balance Type $balanceType with input parameter $input
     * @param Merchant\Entity $merchant
     * @param array $input
     * @return \RZP\Models\Merchant\Balance\BalanceConfig\Entity | null
     */
    public function createBalanceConfig(Merchant\Entity $merchant, array $input)
    {
        $this->trace->info(TraceCode::BALANCE_CONFIG_CREATE_REQUEST,
            [
                'merchant_id' => $merchant->getMerchantId(),
                'input'       => $input
            ]
        );

        $balance = $merchant->getBalanceByTypeOrFail($input[Entity::TYPE]);

        $balanceConfig = $this->validateAndCreateBalanceConfig($merchant, $balance, $input);

        return $balanceConfig;
    }

    /**
     * Edit existing BalanceConfig entity identified by $balanceConfig with input parameters $input
     * @param Entity $balanceConfig
     * @param array $input
     * @param string $balanceType
     * @return Entity
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function editBalanceConfig(Entity $balanceConfig, array $input)
    {
        $this->trace->info(TraceCode::BALANCE_CONFIG_EDIT_REQUEST,
            [
                'balance_config_id' => $balanceConfig->getId(),
                'input'             => $input
            ]
        );

        (new Validator)->validateEditBalanceConfig($input, $balanceConfig);

        $balanceConfig = (new Balance\BalanceConfig\Core)->edit($input, $balanceConfig);

        $this->trace->info(TraceCode::BALANCE_CONFIG_EDIT_SUCCESSFUL,
            [
                'balance_config' => $balanceConfig,
            ]
        );

        return $balanceConfig;
    }

    public function getMaxNegativeAmountAutoForBalanceId(string $balanceId): int
    {
        $balanceConfigs = $this->repo->balance_config->fetch([Entity::BALANCE_ID  => $balanceId]);

        if ($balanceConfigs->count() !== 0)
        {
            return $balanceConfigs->first()->getMaxNegativeLimitAuto();
        }

        return 0;
    }

    public function getMaxNegativeAmountManualForBalanceId(string $balanceId): int
    {
        $balanceConfigs = $this->repo->balance_config->fetch([Entity::BALANCE_ID => $balanceId]);

        if ($balanceConfigs->count() !== 0)
        {
            return $balanceConfigs->first()->getMaxNegativeLimitManual();
        }

        return 0;
    }

    public function getNegativeFlowsForBalance(string $balanceId): array
    {
        $balanceConfigs = $this->repo->balance_config->fetch([Entity::BALANCE_ID  => $balanceId]);

        if ($balanceConfigs->count() !== 0)
        {
            $this->trace->info(TraceCode::BALANCE_CONFIG_FETCH_REQUEST,
                [
                    'balance config' => $balanceConfigs->first(),
                    'flows array'    => $balanceConfigs->first()->getNegativeTransactionFlows()
                ]
            );

            return $balanceConfigs->first()->getNegativeTransactionFlows();
        }

        return [];
    }

    /**
     * Add BalanceConfig Entity
     * @param array $input
     * @return \RZP\Models\Merchant\Balance\BalanceConfig\Entity
     */
    private function create($balance, array $input): Entity
    {
        $balanceConfig = (new Entity)->build($input);

        $balanceConfig->balance()->associate($balance);

        $this->repo->saveOrFail($balanceConfig);

        return $balanceConfig;
    }

    /**
     * Edit BalanceConfig entity
     *
     * @param array $input
     * @param \RZP\Models\Merchant\Balance\BalanceConfig\Entity $balanceConfig
     * @return \RZP\Models\Merchant\Balance\BalanceConfig\Entity
     */
    private function edit($input, Entity $balanceConfig)
    {
        $oldAutoLimit = $balanceConfig->getMaxNegativeLimitAuto();

        $oldManualLimit = $balanceConfig->getMaxNegativeLimitManual();

        if (array_key_exists(Entity::NEGATIVE_TRANSACTION_FLOWS, $input) === true)
        {
            $flows = $input[Entity::NEGATIVE_TRANSACTION_FLOWS];

            if ($input[Entity::TYPE] === Balance\Type::PRIMARY)
            {
                $flows = array_values(array_unique(array_merge($flows, self::DEFAULT_PG_FLOWS), false));
            }

            $input[Entity::NEGATIVE_TRANSACTION_FLOWS] = $flows;
        }

        $balanceConfig->edit($input);

        $this->repo->saveOrFail($balanceConfig);

        // send Negative Balance Feature Enabled mail, if previously balance config existed
        // but auto and manual limit in balance config were both zero and now they have been set to non-zero value.
        if (($oldAutoLimit === 0) and
            ($oldManualLimit === 0))
        {
            $balance = $this->repo->balance->find($balanceConfig->getBalanceId());

            $merchant = $this->repo->merchant->find($balance->getMerchantId());

            $this->sendNegativeBalanceFeatureEnabledMail($merchant, $balanceConfig);
        }

        return $balanceConfig;
    }

    private function validateAndCreateBalanceConfig(Merchant\Entity $merchant,
                                                    Balance\Entity $balance,
                                                    array $input) : Entity
    {
        $input[Entity::BALANCE_ID] = $balance->getId();

        (new Validator)->validateCreateBalanceConfig($input);

        $flows = array_key_exists(Entity::NEGATIVE_TRANSACTION_FLOWS, $input) ?
            $input[Entity::NEGATIVE_TRANSACTION_FLOWS] : [];

        $defaultFlows = $balance->getType() === Balance\Type::PRIMARY ? self::DEFAULT_PG_FLOWS : [];

        $flows = array_values(array_unique(array_merge($flows, $defaultFlows), false));

        $input[Entity::NEGATIVE_TRANSACTION_FLOWS] = $flows;

        $balanceConfig = $this->create($balance, $input);

        $this->sendNegativeBalanceFeatureEnabledMail($merchant, $balanceConfig);

        $this->trace->info(TraceCode::BALANCE_CONFIG_CREATE_SUCCESSFUL,
            [
                'merchant_id'    => $merchant->getMerchantId(),
                'balance_config' => $balanceConfig,
                'balance_type'   => $input[Entity::TYPE]
            ]
        );

        return $balanceConfig;
    }

    private function sendNegativeBalanceFeatureEnabledMail(Merchant\Entity $merchant, Entity $balanceConfig)
    {
        $response = $this->app->razorx->getTreatment($merchant->getId(), self::NEGATIVE_BALANCE_FEATURE,
                                                      $this->mode);

        if ($response !== 'on')
        {
            return;
        }

        //If transaction flows does not contain 'refund' then show E-Mandate Negative Balance documentation.
        $flowDoc = array_key_exists('refund', $balanceConfig->getNegativeTransactionFlows()) === true ?
                    self::NEGATIVE_BALANCE_DOCUMENTATION['refund_route'] :
                    self::NEGATIVE_BALANCE_DOCUMENTATION['e-mandate'];

        $data['feature']                             = self::NEGATIVE_BALANCE_FEATURE_DISPLAY_NAME;
        $data['contact_name']                        = $merchant->getName();
        $data['contact_email']                       = $merchant->getEmail();
        $data['documentation']                       = $flowDoc;
        $data[self::NEGATIVE_LIMIT_MANUAL]           = $balanceConfig->getMaxNegativeLimitManual();
        $data[self::NEGATIVE_LIMIT_AUTO]             = $balanceConfig->getMaxNegativeLimitAuto();
        $data[self::NEGATIVE_TRANSACTION_FLOWS]      = $balanceConfig->getNegativeTransactionFlows();

        $featureUpdateEmail = new FeatureEnabled($data, $merchant);

        Mail::queue($featureUpdateEmail);

        $this->trace->info(
            TraceCode::FEATURE_ENABLED_MERCHANT_NOTIFIED,
            [
                PublicEntity::MERCHANT_ID           => $merchant->getId(),
                'mode'                              => $this->mode,
                Feature\Entity::NEW_FEATURE         => self::NEGATIVE_BALANCE_FEATURE_DISPLAY_NAME,
                PublicEntity::ID                    => $balanceConfig->getId(),
            ]);
    }

    public function isNegativeBalanceEnabledForTxnAndMerchant(string $txnType, string $balanceType = Balance\Type::PRIMARY) : bool
    {
        if ((array_key_exists($balanceType, Balance\Core::NEGATIVE_FLOWS) === false) or
            (in_array($txnType, Balance\Core::NEGATIVE_FLOWS[$balanceType]) === false))
        {
            return false;
        }

        return true;
    }
}
