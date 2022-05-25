<?php


namespace RZP\Models\CreditTransfer\Helper;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\CreditTransfer;
use RZP\Models\VirtualAccount;

class Payout extends Base
{
    public function getCreditAccountTypeFromSourceEntity()
    {
        $payout = $this->source;

        return $payout->fundAccount->getAccountType();
    }

    public function getDestinationVirtualAccountFromSourceEntity(): VirtualAccount\Entity
    {
        $payout = $this->source;

        $creditAccount = $payout->fundAccount->account;

        $destinationVirtualAccount = $this->repo->virtual_account
                                                ->getActiveVirtualAccountFromAccountNumberAndIfsc(
                                                    $creditAccount->getAccountNumber(),
                                                    $creditAccount->getIfscCode()
                                                );

        if ($destinationVirtualAccount === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_PAYOUTS_NO_ACTIVE_BENEFICIARY_VA_FOUND,
                null,
                [
                    'merchant_id'       => $payout->getMerchantId(),
                    'credit_account_id' => $creditAccount->getId()
                ]);
        }

        return $destinationVirtualAccount;
    }

    public function getCreditTransferInputFromSourceEntity()
    {
        $payout = $this->source;

        $creditTransferInput = [
            CreditTransfer\Entity::AMOUNT             => $payout->getAmount(),
            CreditTransfer\Entity::CURRENCY           => $payout->getCurrency(),
            CreditTransfer\Entity::CHANNEL            => $payout->getChannel(),
            CreditTransfer\Entity::DESCRIPTION        => $payout->getNarration(),
            CreditTransfer\Entity::MODE               => $payout->getMode(),
            CreditTransfer\Entity::ENTITY_ID          => $payout->getId(),
            CreditTransfer\Entity::ENTITY_TYPE        => $payout->getEntityName(),
            CreditTransfer\Entity::PAYER_ACCOUNT      => $payout->bankingAccount->getAccountNumber(),
            CreditTransfer\Entity::PAYER_NAME         => $payout->merchant->getDisplayNameElseName(),
            CreditTransfer\Entity::PAYER_IFSC         => $payout->bankingAccount->getAccountIfsc(),
        ];

        return $creditTransferInput;
    }
}
