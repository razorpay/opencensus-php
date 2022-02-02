<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const WORKFLOW_CONFIG_CREATE = 'workflow_config_create';

    const WORKFLOW_CONFIG_UPDATE = 'workflow_config_update';

    protected static $workflowConfigCreateRules = [
        Entity::TEMPLATE             => 'required|array',
        Entity::VERSION              => 'required|numeric',
        Entity::TYPE                 => 'required|string|max:20',
        Entity::NAME                 => 'required|string|max:255',
        Entity::ENABLED              => 'required',
        Entity::SERVICE              => 'required|string|in:rx_live,rx_test,growth',
        Entity::OWNER_ID             => 'required|string|max:14',
        Entity::OWNER_TYPE           => 'required|string|in:merchant',
        Entity::ORG_ID               => 'required|string|max:14',
        Entity::CONTEXT              => 'sometimes|array',
    ];

    protected static $workflowConfigUpdateRules = [
        Entity::ID                   => 'required|string|max:14',
        Entity::NAME                 => 'required|string|max:255',
        Entity::ENABLED              => 'required',
        Entity::SERVICE              => 'required|string|in:rx_live,rx_test,growth',
        Entity::OWNER_ID             => 'required|string|max:14',
        Entity::OWNER_TYPE           => 'required|string|max:20',
    ];

    protected static $createRules = [
        Entity::ID                   => 'required|string|max:14',
        Entity::CONFIG_ID            => 'required|string|max:14',
        Entity::CONFIG_TYPE          => 'required|string|max:255',
        Entity::ENABLED              => 'required|bool',
    ];

    /**
     * @param Merchant\Entity $merchant
     */
    public function validateForNoPendingPayouts(Merchant\Entity $merchant)
    {
        $pendingPayoutsCount = app('repo')->payout->fetchCountOfPendingPayoutsForMerchant($merchant->getId());

        if ($pendingPayoutsCount > 0)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUTS,
                null,
                ['merchant_id' => $merchant->getId()]
            );
        }
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     */
    public function validateOrgId(array $input, Merchant\Entity $merchant)
    {
        if ($input[Entity::ORG_ID] !== $merchant->org->getId())
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_ORG_ID_IS_INCORRECT,
                null,
                [
                    'merchant_id'       => $merchant->getId(),
                    'merchant_org_id'   => $merchant->getOrgId(),
                    'input_org_id'      => $input[Entity::ORG_ID],
                ]
            );
        }
    }

    public function validateForNoPendingPayoutLinks(Merchant\Entity $merchant)
    {
        $pendingPayoutLinks = app('payout-links')->fetchPendingPayoutLinks($merchant->getId());

        if ($pendingPayoutLinks['count'] > 0)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUT_LINKS,
                null,
                ['merchant_id' => $merchant->getId()]
            );
        }
    }
}
