<?php

namespace RZP\Services\FTS\Transfer;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorDescription;
use RZP\Services\FTS\Constants;
use RZP\Exception\LogicException;
use RZP\Exception\RuntimeException;
use RZP\Exception\BadRequestException;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Models\FundTransfer\Attempt\Version;
use RZP\Models\FundTransfer\Attempt\Entity as TransferAttempt;

trait Initiate
{

    /**
     * @var array
     */
    protected $request;

    protected $values;

    /**
     * @var TransferAttempt|null
     */
    protected $fta;

    protected function createFTA(): Client
    {
        $transfer = $this->request[RequestFields::TRANSFER];

        $sourceType = $transfer[RequestFields::SOURCE_TYPE];

        $sourceId = $transfer[RequestFields::SOURCE_ID];

        // This checks if an FTA with the same source already exists, and returns if true, to avoid duplication
        if ($this->isDuplicateSource($sourceType, $sourceId))
        {
            return $this;
        }

        $this->setFTAValues();

        $default = $this->getFTADefaults();

        $this->values = array_merge($default, $this->values);

        $this->fta->fillAndGenerateId($this->values);

        $this->repo->saveOrFail($this->fta);

        return $this;
    }

    // TODO: Remove icici as default channel and allow null values
    protected function getFTADefaults()
    {
        return [
          TransferAttempt::CHANNEL     => Channel::ICICI,
          TransferAttempt::VERSION     => Version::V3,
          TransferAttempt::STATUS      => Status::CREATED,
          TransferAttempt::IS_FTS      => true,
          TransferAttempt::INITIATE_AT => Carbon::now(Timezone::IST)->getTimestamp(),
        ];
    }

    protected function setFTAValues()
    {
        $transfer = $this->request[RequestFields::TRANSFER];

        $sourceType = $transfer[RequestFields::SOURCE_TYPE];

        $sourceId = $transfer[RequestFields::SOURCE_ID];

        $this->fta = new TransferAttempt;

        $this->fta->setSourceType($sourceType);

        $this->fta->setSourceId($sourceId);

        $this->fta->setMerchantId($transfer[RequestFields::MERCHANT_ID]);

        $ftaInput = [
          TransferAttempt::PURPOSE => $transfer[RequestFields::PURPOSE],
        ];

        $accountType = $transfer[RequestFields::TRANSFER_ACCOUNT_TYPE];

        $account = $this->request[$accountType];

        switch ($accountType)
        {
            case RequestFields::BANK_ACCOUNT:
                $this->fta->setBankAccountId($account[RequestFields::ID]);

                break;
            case RequestFields::VPA:
                $this->fta->setVpaId($account[RequestFields::ID]);

                break;
            case RequestFields::CARD:
                $this->fta->setCardId($account[RequestFields::ID]);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $accountType);
        }
        if (isset($transfer[RequestFields::PREFERRED_MODE]) === true)
        {
            $ftaInput += [
              TransferAttempt::MODE => $transfer[RequestFields::PREFERRED_MODE]
            ];
        }
        if (isset($transfer[RequestFields::PREFERRED_CHANNEL]) === true)
        {
            $ftaInput += [
              TransferAttempt::CHANNEL => $transfer[RequestFields::PREFERRED_CHANNEL]
            ];
        }
        if (isset($transfer[RequestFields::NARRATION]) === true)
        {
            $ftaInput += [
              TransferAttempt::NARRATION => $transfer[RequestFields::NARRATION]
            ];
        }
        $this->values = $ftaInput;
    }

    protected function extractAndUpdateResponse(array $responseBody = [])
    {
        $responseBody = $responseBody['body'];

        $failureReason = null;

        $ftsTransferId = 0;

        $status = Constants::STATUS_INITIATED;

        if ((isset($responseBody[Constants::STATUS]) === true) and
          (strtolower($responseBody[Constants::STATUS]) !== Constants::STATUS_CREATED))
        {
            $status = strtolower($responseBody[Constants::STATUS]);
        }

        if (isset($responseBody[Constants::FUND_TRANSFER_ID]) === true)
        {
            $ftsTransferId = $responseBody[Constants::FUND_TRANSFER_ID];
        }

        if ((isset($responseBody[Constants::INTERNAL_ERROR]) === true) and
          ((isset($responseBody[Constants::INTERNAL_ERROR][Constants::CODE]) === true) and
            ($responseBody[Constants::INTERNAL_ERROR][Constants::CODE] === Constants::VALIDATION_ERROR)))
        {
            $status = Constants::STATUS_FAILED;

            if (isset($responseBody[Constants::INTERNAL_ERROR][Constants::MESSAGE]) === true)
            {
                $failureReason = $responseBody[Constants::INTERNAL_ERROR][Constants::MESSAGE];
            }
        }

        $this->updateResponse($ftsTransferId, $status, $failureReason);
    }

    protected function updateResponse(
      $ftsTransferId,
      string $status = null,
      string $failureReason = null)
    {
        if (empty($failureReason) === false)
        {
            $this->fta->setFailureReason($failureReason);
        }

        if (empty($ftsTransferId) === false)
        {
            $this->fta->setFTSTransferId($ftsTransferId);
        }

        if (empty($status) === false)
        {
            $this->fta->setStatus($status);
        }

        $this->repo->saveOrFail($this->fta);
    }

    protected function makePayload()
    {
        $transfer = $this->request[RequestFields::TRANSFER];

        $purpose = $transfer[RequestFields::PURPOSE];

        $sourceType = $transfer[RequestFields::SOURCE_TYPE];

        $accountType = $transfer[RequestFields::TRANSFER_ACCOUNT_TYPE];

        $type = '';

        if (array_key_exists(RequestFields::TYPE, $transfer))
        {
            $type = $transfer[RequestFields::TYPE];
        }

        $channel = null;

        if (array_key_exists(RequestFields::PREFERRED_CHANNEL, $transfer))
        {
            $channel = $transfer[RequestFields::PREFERRED_CHANNEL];
        }

        $account = $this->request[$accountType];

        $merchantId = $transfer[RequestFields::MERCHANT_ID];

        unset($account[RequestFields::ID]);

        unset($transfer[RequestFields::PURPOSE]);

        unset($transfer[RequestFields::MERCHANT_ID]);

        unset($transfer[RequestFields::TYPE]);

        unset($transfer[RequestFields::TRANSFER_ACCOUNT_TYPE]);

        $product = $this->getProductForTransfer($sourceType, $purpose, $type, $channel);

        $values = [
          RequestFields::MERCHANT_ID => $merchantId,
          RequestFields::PRODUCT     => $product,
          RequestFields::ACCOUNT     => [
            $accountType => $account,
          ],
          RequestFields::TRANSFER    => $transfer,
        ];

        $this->request = $values;
    }

    protected function getProductForTransfer(string $sourceType, string $purpose, string $type, $channel): string
    {
        if (in_array($sourceType, [Constants::REFUND, Constants::SETTLEMENT], true) === true)
        {
            return $sourceType;
        }

        if ($sourceType === Constants::FUND_ACCOUNT_VALIDATION)
        {
            return Constants::PENNY_TESTING;
        }

        if ($sourceType === Constants::PAYOUT)
        {
            if ($type == Constants::ON_DEMAND)
            {
                return Constants::ES_ON_DEMAND;
            }
            if ($channel === Channel::RBL)
            {
                return $sourceType;
            }
            if ($purpose === Constants::REFUND)
            {
                return Constants::PAYOUT_REFUND;
            }
            return $sourceType;
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_SUPPORTED_SOURCE_TYPE,
          null,
          [
            'source_type' => $sourceType,
            'purpose'     => $purpose,
          ],
          PublicErrorDescription::BAD_REQUEST_UNSUPPORTED_SOURCE_TYPE);
    }

    protected function isDuplicateSource($sourceType, $sourceId): bool
    {
        $this->fta = $this->repo
                          ->fund_transfer_attempt
                          ->getAttemptBySourceId($sourceId, $sourceType);

        if ($this->fta !== null)
        {
            return true;
        }
        return false;
    }
}
