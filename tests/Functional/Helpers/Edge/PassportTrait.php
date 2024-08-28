<?php

namespace RZP\Tests\Functional\Helpers\Edge;

use DateTimeZone;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Razorpay\Edge\Passport;
use RZP\Constants\Mode;
use Razorpay\Edge\Passport\Tests\GeneratesTestPassportJwts;
use RZP\Tests\Functional\Authorization;


trait PassportTrait
{
    use GeneratesTestPassportJwts;

    protected function samplePassportJwtBuilder(
        array $consumer = [],
        array $credential = [],
        string $mode = Mode::TEST,
        array $impersonation = [],
        array $oauth = [],
        array $roles = [],
        bool $identified = true,
        bool $authenticated = true
    ): string {
        $sysClock = new SystemClock(new DateTimeZone('UTC'));
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        $builder =  $builder
            ->issuedBy('https://edge.razorpay.com')
            ->permittedFor('https://api.razorpay.com')
            ->identifiedBy('per-req-uuid', true)
            ->issuedAt($sysClock->now())
            ->canOnlyBeUsedAfter($sysClock->now())
            ->expiresAt($sysClock->now()->add(new \DateInterval('P15M')))
            ->withHeader('kid', 'edgev1')
            // Custom claims follows.
            ->withClaim('identified', $identified)
            ->withClaim('authenticated', $authenticated)
            ->withClaim('mode', $mode)
            ->withClaim('domain', 'razorpay')
            ->withClaim('consumer', $consumer)
            ->withClaim('credential', $credential);

        if (!empty($impersonation)) {
            $builder = $builder->withClaim('impersonation', $impersonation);
        }

        if (!empty($oauth)) {
            $builder = $builder->withClaim('oauth', $oauth);
        }

        if (!empty($roles)) {
            $builder = $builder->withClaim('roles', $roles);
        }

        return $this->samplePassportJwt($builder);
    }

    protected function sampleKeylessPassportJwtBuilder(
        array $consumer = [],
        array $credential = [],
        string $mode = Mode::TEST,
        array $roles = [],
        bool $identified = true,
        bool $authenticated = false
    ): string {
        $sysClock = new SystemClock(new DateTimeZone('UTC'));
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        $builder =  $builder
            ->issuedBy('https://edge.razorpay.com')
            ->permittedFor('https://api.razorpay.com')
            ->identifiedBy('per-req-uuid', true)
            ->issuedAt($sysClock->now())
            ->canOnlyBeUsedAfter($sysClock->now())
            ->expiresAt($sysClock->now()->add(new \DateInterval('P15M')))
            ->withHeader('kid', 'edgev1')
            // Custom claims follows.
            ->withClaim('identified', $identified)
            ->withClaim('authenticated', $authenticated)
            ->withClaim('mode', $mode)
            ->withClaim('domain', 'razorpay')
            ->withClaim('consumer', $consumer)
            ->withClaim('credential', $credential);


        if (!empty($roles)) {
            $builder = $builder->withClaim('roles', $roles);
        }

        return $this->samplePassportJwt($builder);
    }

    /**
     * Return Passport JWT header to be attached to the request
     * @param $request
     * @return array
     */
    public function getPassportJwtHeader($request) {
        $id = '10000000000000';
        $key = $this->ba->getKey();
        $publicKey = $key;
        $type = str_contains($key, '_partner_') ? Authorization::PARTNER : Authorization::MERCHANT;
        $mode = str_contains($key, '_live_') ? 'live' : 'test';
        $impersonation = [];
        $oauth = [];
        $roles = [];
        $authenticated = true;
        $identified = true;

        // only for private auth (merchant/partner/oauth)
        if (($this->ba->isPrivateAuth() && !$this->ba->isProxyAuth())) {
            if ($key !== Authorization::DEFAULT_TEST_KEY && $key !== Authorization::DEFAULT_LIVE_KEY) {
                $id = ($type === Authorization::PARTNER) ? $this->ba->getPartnerMerchantId() : $this->getKeyId($key, $mode);
            }
            $accountId = $this->getAccountId($request);
            if (! empty($accountId)) {
                $impersonation = $this->getImpersonationClaims($accountId);
                $publicKey = $key . '-acc_' . $accountId;
            }
        }
        if ($this->ba->isBearerAuth()) {
            $tokenEntity = $this->ba->getOauthTokenEntity()->toArray();
            $id = $tokenEntity['merchant_id'];
            $type = 'merchant';
            $mode = $tokenEntity['mode'];
            $key = 'rzp_' . $tokenEntity['mode'] . '_oauth_' . $tokenEntity['public_token'];
            $publicKey = $key;
            $oauth = $this->getOauthClaims($tokenEntity);
            $roles = $this->getRolesClaims($tokenEntity['scopes']);
        }
        if ($this->ba->isPublicAuth()) {
            $authenticated = false;
            if ($key !== Authorization::DEFAULT_TEST_KEY && $key !== Authorization::DEFAULT_LIVE_KEY) {
                if (str_contains($key, '_oauth_'))
                {
                    $tokenEntity = $this->ba->getOauthTokenEntity()->toArray();
                    $id = $tokenEntity['merchant_id'];
                    $mode = $tokenEntity['mode'];
                    $key = 'rzp_' . $tokenEntity['mode'] . '_oauth_' . $tokenEntity['public_token'];
                    $publicKey = $key;
                    $oauth = $this->getOauthClaims($tokenEntity);
                    $roles = $this->getRolesClaims($tokenEntity['scopes']);
                } else {
                    $id = ($type === Authorization::PARTNER) ? $this->ba->getPartnerMerchantId() : $this->getKeyId($key, $mode);
                }
            }
            $accountId = $this->getAccountId($request);
            if (! empty($accountId)) {
                $impersonation = $this->getImpersonationClaims($accountId);
                $publicKey = $key . '-acc_' . $accountId;
            }
            $authenticated = false;
        }

        $consumer = ['id' => $id, 'type' => $type];
        $credential = ['username' => $key, 'public_key' => $publicKey];
        return [
            'HTTP_X-Passport-JWT-V1' => $this->samplePassportJwtBuilder($consumer, $credential, $mode, $impersonation, $oauth, $roles, $identified, $authenticated),
            'HTTP_X-PASSPORT-USABLE' => 'true'
        ];
    }

    /**
     * @param $tokenEntity
     * @return array
     */
    protected function getOauthClaims($tokenEntity) {
        return [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => $tokenEntity['merchant_id'],
            'user_id' => $tokenEntity['user_id'],
            'env' => $tokenEntity['client_environment']
        ];
    }

    /**
     * @param array $scopes
     * @return array
     */
    protected function getRolesClaims($scopes) {
        $roles = [];
        foreach ($scopes as $scope) {
            $role = 'oauth::scope::' . $scope;
            array_push($roles, $role);
        }
        return $roles;
    }

    /**
     * @param $request
     * @return string|null
     */
    protected function getAccountId($request) {
        if ((!(empty($request['server']['HTTP_X-Razorpay-Account']) && empty($request['content']['account_id']))) || $this->ba->isAccountAuth()) {
            $accountId = $request['server']['HTTP_X-Razorpay-Account'] ?? $request['content']['account_id'] ?? $this->ba->getAccountId();
            return substr($accountId, -14);
        }
        return null;
    }

    /**
     * @param string $accountId
     * @return array
     */
    protected function getImpersonationClaims($accountId) {
        return ['consumer' => ['id' => $accountId, 'type' => 'merchant'], 'type' => 'partner'];
    }

    /**
     * @param string $key
     * @param string $mode
     * @return string|null
     */
    protected function getKeyId($key, $mode) {
        $keyId = substr($key, -14);
        // do a db fetch to get the merchant associated with the key
        $keyEntity = $this->app['repo']->key->connection($mode)->find($keyId);
        return $keyEntity->getMerchantId();
    }


    /**
     * @return Passport\Passport
     */
    protected function getDummyMerchantAuthPassport() :Passport\Passport
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->authenticated = true;
        $passport->mode = "live";
        $passport->domain = "razorpay";
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = "10000000000000";
        $passport->consumer->type = "merchant";

        $passport->credential = new Passport\CredentialClaims;
        $passport->credential->username = "rzp_live_10000000000000";
        $passport->credential->publicKey = "rzp_live_10000000000000";

        return $passport;
    }
}
