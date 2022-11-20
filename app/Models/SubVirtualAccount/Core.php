<?php

namespace RZP\Models\SubVirtualAccount;

use Razorpay\Trace\Logger;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Merchant\Balance\Type;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

/**
 * Class Core
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Core extends Base\Core
{
    const SUB_VA_PAYOUT_ON_DIRECT_MASTER_BALANCE_ERROR = "Error in mapping sub VA payout to direct master balance";

    public function create(array $input): Entity
    {
        $subVirtualAccount = $this->repo->sub_virtual_account->getSubVirtualAccountWithSimilarDetails($input);

        if ($subVirtualAccount !== null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_EXISTS,
                null,
                [
                    Entity::ID                      => $subVirtualAccount->getId(),
                    Entity::MASTER_MERCHANT_ID      => $subVirtualAccount->getMasterMerchantId(),
                    Entity::SUB_MERCHANT_ID         => $subVirtualAccount->getSubMerchantId(),
                    Entity::MASTER_ACCOUNT_NUMBER   => $subVirtualAccount->getMasterAccountNumber(),
                    Entity::SUB_ACCOUNT_NUMBER      => $subVirtualAccount->getSubAccountNumber(),
                ]
            );
        }

        $masterBalance = $this->repo->balance->getBalanceByTypeAccountNumberAndAccountTypeOrFail($input[Entity::MASTER_ACCOUNT_NUMBER], Type::BANKING, AccountType::SHARED);

        $masterMerchant = $masterBalance->merchant;

        $subBalance = $this->repo->balance->getBalanceByTypeAccountNumberAndAccountTypeOrFail($input[Entity::SUB_ACCOUNT_NUMBER],Type::BANKING, AccountType::SHARED);

        $subMerchant = $subBalance->merchant;

        $subVirtualAccount = (new Entity)->build($input);

        $subVirtualAccount->masterMerchant()->associate($masterMerchant);

        $subVirtualAccount->subMerchant()->associate($subMerchant);

        $subVirtualAccount->balance()->associate($masterBalance);

        $this->repo->saveOrFail($subVirtualAccount);

        $this->trace->info(TraceCode::SUB_VIRTUAL_ACCOUNT_CREATED,
            [
                Entity::ID => $subVirtualAccount->getId(),
            ]);

        return $subVirtualAccount;
    }

    public function fetchMultiple(array $input)
    {
        $this->repo->sub_virtual_account->setMerchantIdRequiredForMultipleFetch(false);

        return $this->repo->sub_virtual_account->fetch($input);
    }

    public function enableOrDisable(string $id, array $input)
    {
        /** @var  $subVirtualAccount Entity */
        $subVirtualAccount = $this->repo->sub_virtual_account->findByPublicId($id);

        if ($input[Entity::ACTIVE] === $subVirtualAccount->getActive())
        {
            if ($subVirtualAccount->getActive() === true)
            {
                $errorCode = ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_ENABLED;
            }
            else
            {
                $errorCode = ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_DISABLED;
            }
            throw new Exception\BadRequestException(
                $errorCode,
                null,
                [
                    Entity::ID  => $subVirtualAccount->getId()
                ]
            );
        }

        $subVirtualAccount->setActive($input[Entity::ACTIVE]);

        $this->repo->saveOrFail($subVirtualAccount);

        return $subVirtualAccount;
    }

    public function transfer(array $input, MerchantEntity $masterMerchant, MerchantEntity $subMerchant)
    {
        $masterBalance = $this->repo->balance->getBankingBalanceWithMerchantIdAndAccountNumberOrFail($masterMerchant->getId(), $input[Entity::MASTER_ACCOUNT_NUMBER]);

        $amount = $input[Entity::AMOUNT];

        if ($masterBalance->getBalance() < $amount) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_NOT_ENOUGH_BANKING_BALANCE,
                null,
                [
                    Entity::MASTER_MERCHANT_ID => $this->merchant->getId(),
                ]
            );
        }

        $masterDescription = 'Internal Fund Transfer to ' . $input[Entity::SUB_ACCOUNT_NUMBER];

        $subDescription = 'Internal Fund Transfer from ' . $input[Entity::MASTER_ACCOUNT_NUMBER];

        $masterAdjInput = [
            Entity::AMOUNT => -$amount,
            Entity::DESCRIPTION => $masterDescription,
            BalanceEntity::TYPE => Type::BANKING,
            Entity::CURRENCY => $input[Entity::CURRENCY] ?? 'INR',
        ];

        $subAdjInput = [
            Entity::AMOUNT => $amount,
            Entity::DESCRIPTION => $subDescription,
            BalanceEntity::TYPE => Type::BANKING,
            Entity::CURRENCY => $input[Entity::CURRENCY] ?? 'INR',
        ];

        $masterAdjEntity = $this->repo->transaction(function() use (
            $masterAdjInput,
            $masterMerchant,
            $subAdjInput,
            $subMerchant
        ) {
            $masterAdjEntity = (new Adjustment\Core())->createAdjustment($masterAdjInput, $masterMerchant);

            $subAdjEntity = (new Adjustment\Core())->createAdjustment($subAdjInput, $subMerchant);

            $this->trace->info(
                TraceCode::SUB_VIRTUAL_ACCOUNT_TRANSFER_ADJUSTMENT_RESPONSE,
                [
                    Entity::MASTER_ADJUSTMENT_ID => $masterAdjEntity->getId(),
                    Entity::SUB_ADJUSTMENT_ID    => $subAdjEntity->getId(),
                ]);

            return $masterAdjEntity;
        });

        return $masterAdjEntity;
    }

    public function getDirectBalanceOfMasterMerchantFromSubMerchantIdForSubVaPayout($subMerchantId)
    {
        /** @var Entity $subVirtualAccount */
        $subVirtualAccount = $this->repo->sub_virtual_account->getSubVirtualAccountFromSubMerchantId($subMerchantId);

        $subVirtualAccountValidator = new Validator();

        $directBalance  = null;
        $masterMerchant = null;

        try
        {
            $subVirtualAccountValidator->validateSubVirtualAccount($subVirtualAccount,
                                                                   [
                                                                       Entity::SUB_MERCHANT_ID => $subMerchantId
                                                                   ]);
            /** @var MerchantEntity $masterMerchant */
            $masterMerchant =  $this->repo->merchant->findOrFail($subVirtualAccount->getMasterMerchantId());

            $subVirtualAccountValidator->validateMasterMerchant($masterMerchant);

            $directBalance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType($masterMerchant->getId(),
                                                                                           Type::BANKING,
                                                                                           AccountType::DIRECT);
            if (empty($directBalance) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    [
                        Entity::MASTER_MERCHANT_ID => $masterMerchant->getId()
                    ],
                    "Master merchant direct balance not found"
                );
            }
        }
        catch(\Throwable $exception)
        {
            $this->trace->traceException($exception,
                                         Logger::ERROR,
                                         TraceCode::MASTER_MERCHANT_DIRECT_BALANCE_NOT_FOUND,
                                         [
                                             Entity::SUB_MERCHANT_ID    => $subMerchantId,
                                             Entity::MASTER_MERCHANT_ID => $masterMerchant
                                         ]);


            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    Entity::SUB_MERCHANT_ID    => $subMerchantId,
                    Entity::MASTER_MERCHANT_ID => $masterMerchant,
                ],
                self::SUB_VA_PAYOUT_ON_DIRECT_MASTER_BALANCE_ERROR
            );
        }

        return $directBalance;
    }
}
