<?php

namespace RZP\Models\Consumer;

use Crypt;
use RZP\Models\Base;
use RZP\Models\Key\Credcase as CredcaseKey;
use RZP\Models\Key\Entity as Key;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    const ConsumerTypeApplication = "application";
    const ConsumerTypeMerchant    = "merchant";
    const ConsumerDomainRazorpay  = "razorpay";

    /**
     * @var CredcaseKey
     */
    private $credcase;
    /**
     * @var mixed
     */
    private $v2ApplicationsConfig;

    public function __construct()
    {
        parent::__construct();
        $this->credcase = new CredcaseKey;
        $this->v2ApplicationsConfig = app('config')['applications_v2'];
    }

    /**
     * Reads applications_v2 config and migrate all credentials in that config to Credcase, Edge & AuthZ.
     * Please note that if password for a credential is not supplied via env, we'll ignore that while processing
     * credentials.
     *
     * @param null $input
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    public function migrateInternalApplicationsToCredcase($input = null): array
    {
        $result = [];
        // This flow can be used if you want to migrate a specific application.
        // Otherwise it'll migrate all applications in applications_v2 config.
        if (!empty($input)) {
            $result[] = $this->migrateApplicationToCredcase($input["app_id"], $input["app_config"]);
            return $result;
        }

        foreach ($this->v2ApplicationsConfig as $appId => $appConfig) {
            $result[] = $this->migrateApplicationToCredcase($appId, $appConfig);
        }

        return $result;
    }

    /**
     * @param $appId     - This will be used as the owner_id in Credcase consumer.
     * @param $appConfig - Config for this app.
     *
     * @throws \RZP\Exception\ServerErrorException
     */
    private function migrateApplicationToCredcase($appId, $appConfig)
    {
        $appName = $appConfig["name"];
        $result  = ["app" => $appName, "migrated" => 0, "skipped" => 0];
        (new Credcase)->create($appId, self::ConsumerTypeApplication, self::ConsumerDomainRazorpay, ["name" => $appName]);
        foreach ($appConfig["credentials"] as $credential) {
            $isMigrated = $this->migrateCredentialToCredcase($appId, $appName, $credential);
            if ($isMigrated === true) {
                $result["migrated"]++;
            } else {
                $result["skipped"]++;
            }
        }

        return $result;
    }

    private function migrateCredentialToCredcase(string $appId, string $appName, array $credential)
    {
        if (empty($credential["password"])) {
            $this->trace->info(TraceCode::SKIP_INTERNAL_CREDENTIAL_MIGRATION, ["app_name" => $appName]);
            return false;
        }

        $key = new Key;
        $key->build();
        $key->setAttribute(Key::ID, substr($credential["username"], -14));
        $key->setAttribute(Key::SECRET, Crypt::encrypt($credential["password"]));
        $key->setAttribute(Key::CREATED_AT, time());
        $key->setOwnerType(self::ConsumerTypeApplication);
        $key->setOwnerId($appId);
        $key->setRoleNames($credential["roles"]);
        // Todo: expired keys need to be handled via a different outbox job.
        if (isset($credential[Key::EXPIRED_AT])) {
            $key->setAttribute(Key::EXPIRED_AT, $credential[Key::EXPIRED_AT]);
        }

        $this->repo->transaction(function () use ($key, $credential) {
            $isForced = true;
            $this->credcase->migrate($key, $credential["mode"], $isForced);
        });
        return true;
    }
}
