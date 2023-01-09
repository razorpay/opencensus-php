<?php

namespace RZP\Models\Key;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Modules\Migrate\Migrate;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Notifications\Dashboard\Events as DashboardNotificationEvent;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;

class Service extends Base\Service
{
    const MIGRATE_TO_CREDCASE_INPUT_RULES = [
        'dry_run'            => 'required|boolean',
        'source'             => 'array',
        'source.ids'         => 'array|min:1|max:10000',
        'source.ids.*'       => 'string|unsigned_id',
        'source.mids'        => 'array|min:1|max:10000',
        'source.mids.*'      => 'string|unsigned_id',
    ];

    public function createKey()
    {
        $merchant = $this->merchant;

        (new Validator)->checkHasKeyAccess($merchant, $this->mode);

        return $this->createKeyData($merchant, $this->mode);
    }


    public function createKeyWithOtp(array $input)
    {
        $merchant = $this->merchant;

        $validator = new Validator();

        $validator->checkHasKeyAccess($merchant, $this->mode);

        if (isset($input['otp']) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_OTP_REQUIRED);
        }

        $validator->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        $input['medium'] = array_pull($input, 'medium', 'sms_and_email');

        (new User\Core)->verifyOtp($input + ['action' => 'replace_key'],
            $this->merchant,
            $this->user,
            $this->mode === Mode::TEST);

        return $this->createKeyData($merchant, $this->mode);
    }

    private function createKeyData($merchant, $mode)
    {
        $keyData = (new Core)->createFirstKey($merchant, $mode);

        if ($mode === Mode::LIVE)
        {
            $action = Merchant\Action::LIVE_KEYS_CREATED;
        }
        elseif ($mode === Mode::TEST)
        {
            $action = Merchant\Action::TEST_KEYS_CREATED;
        }

        if ($action !== null)
        {
            $this->app['eventManager']->trackEvents($merchant, $action, $merchant->toArrayEvent());
        }

        return $keyData;
    }

    public function fetchKeys()
    {
        (new Validator)->checkHasKeyAccess($this->merchant, $this->mode);

        $merchantId = $this->merchant->getId();

        $keys = $this->repo->key->getKeysForMerchant($merchantId);

        return $keys->toArrayPublic();
    }

    public function updateKey($keyId, array $input)
    {
        (new Validator)->checkHasKeyAccess($this->merchant, $this->mode);

        $merchantId = $this->merchant->getId();

        $response = (new Core)->rollKey($merchantId, $keyId, $input, $this->mode);

        $this->sendSelfServeSuccessAnalyticsEventToSegmentForApiKeyRegeneration();

        return $response;
    }

    public function updateKeyWithOtp($keyId, array $input)
    {
        $validator = new Validator();
        $validator->checkHasKeyAccess($this->merchant, $this->mode);

        $merchantId = $this->merchant->getId();
        if (isset($input['otp']) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_OTP_REQUIRED);
        }
        $validator->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        (new User\Core)->verifyOtp($input + ['action' => 'replace_key'],
            $this->merchant,
            $this->user,
            $this->mode === Mode::TEST);
        return (new Core)->rollKey($merchantId, $keyId, $input, $this->mode);
    }

    /**
     * Migrates api keys to credcase service. It parallelizes by pushing multiple queued jobs which will do parts.
     * @param  array $input Holds opts for source and target.
     * @return array
     */
    public function migrateToCredcase(array $input): array
    {
        $this->trace->info(TraceCode::MIGRATE_TO_CREDCASE_REQUEST, $input);

        (new JitValidator)->rules(self::MIGRATE_TO_CREDCASE_INPUT_RULES)->caller($this)->input($input)->validate();

        $source  = new MigrateSource;
        $target  = new MigrateTarget;
        $migrate = new Migrate($source, $target);

        $dryRun     = (bool) $input['dry_run'];
        $sourceOpts = $input['source'] ?? [];
        $targetOpts = $input['target'] ?? [];

        return $migrate->migrateAsync($sourceOpts, $targetOpts, $dryRun);
    }

    protected function regenerateApiKeyForMerchantId($merchantId)
    {
        $merchantEntity = $this->repo->merchant->find($merchantId);

        if($merchantEntity === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_FOUND, null);
        }

        if ($merchantEntity->getHasKeyAccess() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_KEY_ACCESS);
        }

        $keys = $this->repo->key->getKeysForMerchant($merchantId);

        if( count($keys) > 1 )
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_KEYS_REGENERATED_PREVIOUSLY, null);
        }

        $key = array_first($keys);

        $keyId = $key->getPublicId();

        (new Core)->rollKey($merchantId, $keyId, [], $this->mode);

        $args = [
            MerchantConstants::MERCHANT                   => $merchantEntity,
            DashboardNotificationEvent::EVENT             => DashboardNotificationEvent::BULK_REGENERATE_API_KEYS,
            MerchantConstants::PARAMS                     => []
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    public function bulkRegenerateApiKey(array $input)
    {
        $validator = new Validator();

        $validator->validateInput('bulk-regenerate-api-key', $input);

        $successMids = [];

        $failedMids  = [];

        foreach ($input['merchant_ids'] as $merchantId)
        {
            try{
                $this->regenerateApiKeyForMerchantId($merchantId);

                $successMids[] = $merchantId;

            }catch (\Exception $exception)
            {
                $failedMids[$merchantId] =  $exception->getMessage();
            }
        }

        $this->trace->info(
            TraceCode::BULK_REGENERATE_API_KEYS,
            [
                Constants::MERCHANT_IDS => $input[Constants::MERCHANT_IDS],
                Constants::REASON       => $input[Constants::REASON],
                Constants::SUCCESS_MIDS => $successMids,
                Constants::FAILED_MIDS  => $failedMids
            ]);

        return [
                Constants::SUCCESS_MIDS => $successMids,
                Constants::FAILED_MIDS  => $failedMids
            ];
    }

    private function sendSelfServeSuccessAnalyticsEventToSegmentForApiKeyRegeneration()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'API Key Regenerated';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }
}
