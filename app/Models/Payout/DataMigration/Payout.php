<?php

namespace RZP\Models\Payout\DataMigration;

use RZP\Models\Payout\Entity;

class Payout
{
    public function getPayoutServicePayoutFromApiPayout(Entity $payout)
    {
        return [
            Entity::AMOUNT               => $payout->getAmount(),
            Entity::BALANCE_ID           => $payout->getBalanceId(),
            Entity::BATCH_ID             => $payout->getBatchId(),
            Entity::CANCELLATION_USER_ID => $payout->getCancellationUserId(),
            Entity::CHANNEL              => $payout->getChannel(),
            Entity::CREATED_AT           => $payout->getCreatedAt(),
            Entity::CURRENCY             => $payout->getCurrency(),
            Entity::FAILURE_REASON       => $payout->getFailureReason(),
            Entity::FEE_TYPE             => $payout->getFeeType(),
            Entity::FEES                 => $payout->getFees(),
            Entity::FTS_TRANSFER_ID      => $payout->getFtsTransferId(),
            Entity::FUND_ACCOUNT_ID      => $payout->getFundAccountId(),
            Entity::ID                   => $payout->getId(),
            Entity::IDEMPOTENCY_KEY      => $payout->getIdempotencyKey(),
            Entity::MERCHANT_ID          => $payout->getMerchantId(),
            Entity::METHOD               => $payout->getMethod(),
            Entity::MODE                 => $payout->getMode(),
            Entity::NARRATION            => $payout->getNarration(),
            Entity::NOTES                => $payout->getRawAttribute(Entity::NOTES),
            Entity::ON_HOLD_AT           => $payout->getOnHoldAt(),
            Entity::ORIGIN               => $payout->getRawAttribute(Entity::ORIGIN),
            Entity::PAYOUT_LINK_ID       => $payout->getRawAttribute(Entity::PAYOUT_LINK_ID),
            Entity::PRICING_RULE_ID      => $payout->getPricingRuleId(),
            Entity::PURPOSE              => $payout->getPurpose(),
            Entity::PURPOSE_TYPE         => $payout->getPurposeType(),
            Entity::QUEUED_AT            => $payout->getQueuedAt(),
            Entity::QUEUED_REASON        => $payout->getQueuedReason(),
            Entity::REFERENCE_ID         => $payout->getReferenceId(),
            Entity::REGISTERED_NAME      => $payout->getRegisteredName(),
            Entity::REMARKS              => $payout->getRemarks(),
            Entity::SCHEDULED_AT         => $payout->getScheduledAt(),
            Entity::STATUS               => $payout->getStatus(),
            Entity::STATUS_CODE          => $payout->getStatusCode(),
            Entity::TAX                  => $payout->getTax(),
            Entity::TRANSACTION_ID       => $payout->getTransactionId(),
            Entity::UPDATED_AT           => $payout->getUpdatedAt(),
            Entity::USER_ID              => $payout->getUserId(),
            Entity::UTR                  => $payout->getUtr(),
            Entity::WORKFLOW_FEATURE     => $payout->getWorkflowFeature(),
        ];
    }
}
