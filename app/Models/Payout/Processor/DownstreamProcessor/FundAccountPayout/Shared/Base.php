<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Shared;

use RZP\Error\ErrorCode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

class Base extends FundAccountPayout\Base
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        try
        {
            $this->setChannel($payout);

            $this->createTransaction($payout);

            $payout->setStatus(Status::CREATED);

            //
            // Create a fund transfer entity where the fund transfers will be processed.
            // NOTE: Ensure that this is created after transaction creation, so that if
            // the transaction creation fails because of insufficient funds and we want
            // to queue the payout instead of failing the complete DB transaction, this
            // FTA does not get created.
            //
            $this->createFundTransferAttempt($payout, $ftaAccount);
        }
        catch (BadRequestException $ex)
        {
            //
            // This needs to be done since while creating a transaction we also associate
            // the source (payout) with the transaction and then we fail the transaction
            // creation due to insufficient balance and then later attempt to save the payout.
            // Payout save fails because we associated the failed transaction with the payout
            // but we had not actually saved the transaction in the DB.
            //
            $payout->transaction()->dissociate();

            $insufficientFundsErrorCode = ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING;

            if ($ex->getError()->getInternalErrorCode() === $insufficientFundsErrorCode)
            {
                if ($payout->toBeQueued() === false)
                {
                    throw $ex;
                }

                $payout->setStatus(Status::QUEUED);
            }
            else
            {
                throw $ex;
            }
        }
    }
}
