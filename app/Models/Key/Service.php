<?php

namespace RZP\Models\Key;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Modules\Migrate\Migrate;

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

        $keyData = (new Core)->createFirstKey($merchant, $this->mode);

        if ($this->mode === Mode::LIVE)
        {
            $action = Merchant\Action::LIVE_KEYS_CREATED;
        }
        elseif ($this->mode === Mode::TEST)
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
}
