<?php

namespace RZP\Http\Edge;

use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\App;
use Razorpay\Edge\Passport\Passport;
use Razorpay\OAuth\Application\Repository;

class PassportUtil
{
    protected $passport;

    protected $app;

    protected $trace;

    public function __construct(Passport $passport)
    {
        $app = App::getFacadeRoot();

        $this->app      = $app;
        $this->trace    = $app['trace'];
        $this->passport = $passport;
    }

    /**
     * Check that given value is empty and if they are then the value is put in error.
     *
     * @param string $key
     * @param array  $errors
     *
     * @return void
     */
    function ensureNotEmpty($value, string $key = '', array &$errors = []): bool
    {
        if (empty($value))
        {
            $errors[$key] = ['empty'];
            return false;
        }
        return true;
    }


    public function canPassportBeUsedForOauth(): bool
    {
        $errors = [];
        if(
            $this->ensureNotEmpty($this->passport->mode, 'mode', $errors) === true and
            $this->ensureNotEmpty($this->passport->consumer, 'consumer', $errors) === true and
            $this->ensureNotEmpty($this->passport->consumer->id, 'consumer_id', $errors) === true and
            $this->passport->consumer->type === 'merchant' and
            $this->ensureNotEmpty($this->passport->credential, 'credential', $errors) === true and
            $this->ensureNotEmpty($this->passport->credential->publicKey, 'public_key', $errors) === true and
            $this->ensureNotEmpty($this->passport->oauth, 'oauth', $errors) === true and
            $this->ensureNotEmpty($this->passport->oauth->accessTokenId, 'access_token_id', $errors) === true and
            $this->ensureNotEmpty($this->passport->oauth->clientId, 'client_id', $errors) === true and
            $this->ensureNotEmpty($this->passport->oauth->appId, 'app_id', $errors) === true and
            $this->ensureNotEmpty($this->passport->oauth->ownerId, 'oauth_owner_id', $errors) === true and
            $this->ensureNotEmpty($this->passport->oauth->env, 'oauth_env', $errors) === true and
            $this->ensureNotEmpty($this->fetchOauthScopes(), 'oauth_scopes', $errors) === true and
            $this->checkConsumerId($errors) === true
        )
        {
            return true;
        }

        return false;
    }

    /**
     * Resolve and process an OAuth scope from passoport roles
     *
     * @return array
     */
    public function fetchOauthScopes(): array
    {
        // roles look like ["finance", "support-l1", "oauth::scope::read_only"]
        /* @var string[] $roles */

        $roles = $this->passport->roles;

        $scopes = [];
        if (!empty($roles))
        {
            foreach ($roles as $role)
            {
                if ((starts_with($role, 'oauth::')) === true)
                {
                    $roleSplit = explode("::", $role);

                    if(empty($roleSplit[2]) === false)
                    {
                        array_push($scopes, $roleSplit[2]);
                    }
                }
            }
        }

        return $scopes;
    }

    //putting this check as there are several mismatches reported for consumer id.
    // consumer id is equal to partner merchant id
    //TODO remove this when passport data is synced.
    private function checkConsumerId(array &$errors): bool
    {
        $application = (new Repository())->findOrFail($this->passport->oauth->appId);
        if ($application->getMerchantId() !== $this->passport->consumer->id)
        {
            $errors[TraceCode::EDGE_PASSPORT_CONSUMER_ID_MISMATCH] = ['expected' => $application->getMerchantId(),
                                                                      'actual' => $this->passport->consumer->id];
        }
        return ($application->getMerchantId() === $this->passport->consumer->id);
    }

}
