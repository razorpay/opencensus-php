<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Base\Traits;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Redaction;
use RZP\Services\FTS\Transfer\Client as FtsClient;

class Service extends Base\Service
{
    use Traits\ProcessAccountNumber;
    use Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->fund_account_validation;
    }

    public function create(array $input): array
    {
        if (empty($input[Balance\Entity::ACCOUNT_NUMBER]) === false)
        {
            // mandates account number and converts to balance id
            $this->processAccountNumber($input);
        }

        $entity = $this->core->create($input, $this->merchant);

        return $entity->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        if (empty($input[Balance\Entity::ACCOUNT_NUMBER]) === false)
        {
            // mandates account number and converts to balance id
            $this->processAccountNumber($input);
        }

        $entities = $this->entityRepo->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    /**
     * @param string $favId
     * @param string $merchantId
     *
     * @return array
     */
    public function getFavByMerchantIdAndFavId(string $favId,string $merchantId)
    {
        return $this->core->getFavByMerchantIdAndFavId($favId, $merchantId);
    }

    public function bulkPatchFavAsFailed(array $input): array
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_BULK_PATCH_REQUEST, [
            'input' => $input
        ]);

        $response = $this->core->bulkPatchFavAsFailed($input);

        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_BULK_PATCH_RESPONSE, [
            'response' => $response
        ]);

        return $response;
    }

    public function manualUpdateFavToFailedState(array $favIds)
    {
        $count = 0;

        foreach ($favIds as $favId)
        {
            $this->trace->info(
                TraceCode::FAV_MANUAL_UPDATE_FROM_FTS_WEBHOOK_SERVICE_INIT,
                [
                    'fav_id'     => $favId
                ]);

            $fta = $this->repo
                ->fund_transfer_attempt
                ->getFTSAttemptBySourceId(
                    $favId,
                    'fund_account_validation',
                    true);

            if ($fta->getFTSTransferId() !== null)
            {
                continue;
            }

            $response = $this->core->manualUpdateFavToFailedState($favId);

            $this->trace->info(
                TraceCode::FAV_MANUAL_UPDATE_FROM_FTS_WEBHOOK_CORE_HANDLER_SUCCESSFUL,
                [
                    'response'     => $response,
                    'fav_id' => $favId
                ]);

            $extraInfo = [
                Attempt\Constants::BENEFICIARY_NAME => '',
                Attempt\Entity::CMS_REF_NO          => '',
                Attempt\Constants::INTERNAL_ERROR   => true,
                'ponum'                             => '',
            ];

            $input = [
                Attempt\Entity::SOURCE_ID        => $favId,
                Attempt\Entity::SOURCE_TYPE      => 'fund_account_validation',
                Attempt\Entity::BANK_STATUS_CODE => Status::FAILED,
                Attempt\Entity::REMARKS          => 'Failed manually',
                'extra_info'                     => $extraInfo,
            ];

            (new FtsClient($this->app))->doRecon($input);

            $this->trace->info(
                TraceCode::FAV_FTA_MANUAL_UPDATE_SUCCESSFUL,
                [
                    'fav_id' => $favId
                ]);

            $count++;
        }

        return ['success' => $count];
    }

    public function updateFavWithFtsWebhook(array $input) : array
    {
        $this->trace->info(
            TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_SERVICE_INIT,
            [
                'input'     => (new Redaction())->redactData($input)
            ]);

        // First update the source FAV entity
        $response = $this->core->updateFavWithFtsWebhook($input);

        $this->trace->info(
            TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_FTA_RECON_CALLED,
            [
                'input'     => (new Redaction())->redactData($input)
            ]);

        // Call the doRecon() method provided by the FTS client to update FTA for backwards compatibility
        (new FtsClient($this->app))->doRecon($input);

        return $response;
    }

    public function createFundAccountValidationViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        $this->core->createFundAccountValidationViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
    }
}
