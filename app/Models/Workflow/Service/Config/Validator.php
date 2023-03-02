<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const WORKFLOW_CONFIG_CREATE = 'workflow_config_create';

    const WORKFLOW_CONFIG_UPDATE = 'workflow_config_update';

    const WORKFLOW_CONFIG_BULK_CREATE = 'workflow_config_bulk_create';

    const WORKFLOW_CONFIG_CREATE_V2 = 'workflow_config_create_v2';

    const WORKFLOW_CONFIG_UPDATE_V2 = 'workflow_config_update_v2';

    const WORKFLOW_CONFIG_DELETE_V2 = 'workflow_config_delete_v2';

    protected static $workflowConfigCreateRules = [
        Entity::TEMPLATE             => 'required_if:asl_template,null|array',
        Entity::ASL_TEMPLATE         => 'required_if:template,null|array',
        Entity::VERSION              => 'required|numeric',
        Entity::TYPE                 => 'required|string|max:20',
        Entity::NAME                 => 'required|string|max:255',
        Entity::ENABLED              => 'required',
        Entity::SERVICE              => 'required|string|in:rx_live,rx_test,growth,relay,splitz',
        Entity::OWNER_ID             => 'required|string|max:14',
        Entity::OWNER_TYPE           => 'required|string|in:merchant,user',
        Entity::ORG_ID               => 'required|string|max:14',
        Entity::CONTEXT              => 'sometimes|array',
    ];

    protected static $workflowConfigUpdateRules = [
        Entity::ID                   => 'required|string|max:14',
        Entity::NAME                 => 'required|string|max:255',
        Entity::ENABLED              => 'required',
        Entity::SERVICE              => 'required|string|in:rx_live,rx_test,growth,relay,splitz',
        Entity::OWNER_ID             => 'required|string|max:14',
        Entity::OWNER_TYPE           => 'required|string|max:20',
        Entity::TEMPLATE             => 'sometimes|array',
    ];

    protected static $workflowConfigBulkCreateRules = [
        Entity::MERCHANT_IDS         => 'required|array|max:50',
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

    /**
     * @param array $input
     * @param MerchantEntity $merchant
     * @throws BadRequestValidationFailureException
     */
    public function checkForNoPendingPayouts(array $input, MerchantEntity $merchant)
    {
        $accountNumbers = $input[PayoutConstants::ACCOUNT_NUMBERS];

        $pendingPayouts = app('repo')->payout->findPendingPayoutsSummaryForAccountNumbers($accountNumbers, $merchant->getId());

        if ($pendingPayouts->count() > 0)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUTS,
                null,
                [
                    'merchant_id' => $merchant->getId(),
                    'pending_payouts_count' => $pendingPayouts->count()
                ]
            );
        }
    }

    /**
     * @param array $input
     * @param MerchantEntity $merchant
     * @throws BadRequestValidationFailureException
     */
    public function checkForNoPendingPayoutLinks(array $input, MerchantEntity $merchant)
    {
        $pendingPayoutLinks = app('payout-links')->fetchPayoutLinksSummaryForMerchant($input, $merchant->getId());

        if (count($pendingPayoutLinks) > 0)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUT_LINKS,
                null,
                [
                    'merchant_id' => $merchant->getId(),
                    'pending_payout_links_count' => count($pendingPayoutLinks)
                ]
            );
        }
    }
}
