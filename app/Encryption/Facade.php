<?php

namespace RZP\Encryption;

use App;
use Config;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\Admin\Org;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Encryption\Encrypter;
use RZP\Models\Merchant\Entity as MerchantEntity;
use Illuminate\Support\Facades\Crypt as BaseFacade;
use Illuminate\Contracts\Encryption\DecryptException;

class Facade extends BaseFacade
{
    public static function encrypt($data, $serialize = true,  Base\PublicEntity $entity = null)
    {
        $entityName = self::getEntityClassName($entity);

        $shouldUseByok = self::shouldUseByok($entity);

        if ($shouldUseByok === true)
        {
            $orgKey = self::getOrgKeyForCrypt($entity);

            if (empty($orgKey) === true)
            {
                self::traceInfo(TraceCode::BYOK_ENCRYPTION_USING_DEFAULT_KEY, ['entity' => $entityName]);

                return parent::encrypt($data, $serialize);
            }

            self::traceInfo(TraceCode::BYOK_ENCRYPTING_USING_ORG_KEY, ['entity' => $entityName, 'keylen' => strlen($orgKey)]);

            $newEncrypter = new Encrypter($orgKey, Config::get('app.cipher'));

            return $newEncrypter->encrypt($data, $serialize);
        }

        // If $shouldUseByok is false, use default encryption
        self::traceInfo(TraceCode::BYOK_ENCRYPTION_USING_DEFAULT_KEY, ['entity' => $entityName]);

        return parent::encrypt($data, $serialize);
    }

    public static function decrypt($data, $unserialize = true,  Base\PublicEntity $entity = null)
    {
        $orgKey = self::getOrgKeyForCrypt($entity);

        if (empty($orgKey) === true)
        {
            return parent::decrypt($data, $unserialize);
        }

        $entityName = self::getEntityClassName($entity);

        self::traceInfo(TraceCode::BYOK_DECRYPTING_USING_ORG_KEY, ['entity' => $entityName, 'keylen' => strlen($orgKey)]);

        try
        {
            $newEncrypter = new Encrypter($orgKey, Config::get('app.cipher'));

            return $newEncrypter->decrypt($data, $unserialize);
        }
        catch(DecryptException $ex) // If above we try to decrypt data that was encrypted by default key
        {
            self::traceInfo(TraceCode::BYOK_DECRYPTION_FAILED_FALLING_BACK_TO_DEFAULT_KEY, ['entity' => $entityName]);

            return parent::decrypt($data, $unserialize);
        }
    }

    // BYOK = "Bring Your Own Key", i.e. use separate key while encrypting data of different orgs. Currently, going live with axis org first
    protected static function shouldUseByok(Base\PublicEntity $entity = null)
    {
        // Entities for which BYOK will be used viz terminals, key, tokens, bank_account will be passed while calling encrypt()/decrypt() and should not be null here
        if (empty($entity) === true)
        {
            return false;
        }

        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? Mode::LIVE;

        $razorxMid = "";

        $shouldCallRazorx = true;

        try
        {
            $razorxMid = $entity->getMerchantId();
        }
        catch (\Throwable $ex)
        {
            self::traceException($ex, TraceCode::BYOK_ALERT_ERROR_WHILE_GETTING_MERCHANT_ID);

            $shouldCallRazorx = false;
        }

        if (empty($razorxMid) === true)
        {
            $ex = new Exception\ServerErrorException(
                'Failed to get merchant_id',
                ErrorCode::SERVER_ERROR);

            self::traceException($ex, TraceCode::BYOK_ALERT_ERROR_WHILE_GETTING_MERCHANT_ID);

            $shouldCallRazorx = false;
        }


        if ($shouldCallRazorx === true)
        {
            $orgId = self::getOrgIdForCrypt($entity);

            if ((empty($orgId) === true) or
                ($orgId === Org\Entity::RAZORPAY_ORG_ID))
            {
                return false;
            }

            $variantFlag = $app->razorx->getTreatment($razorxMid, "BYOK_USE_ORG_KEY_FOR_ENCRYPTION_API", $mode);

            self::traceInfo(TraceCode::BYOK_RAZORX_VARIANT,  ['variant' => $variantFlag, 'mid' => $razorxMid]);

            if ($variantFlag === 'on')
            {
                return true;
            }
        }
        return false;
    }

    protected static function getOrgKeyForCrypt($entity)
    {
        $orgId = self::getOrgIdForCrypt($entity);

        if ((empty($orgId) === true) or
            ($orgId === Org\Entity::RAZORPAY_ORG_ID))
        {
            return null;
        }

        $orgKey = self::getOrgKeyFromOrgId($orgId);

        if (($orgId === MerchantEntity::AXIS_ORG_ID) and (empty($orgKey) === true))
        {
            // Currently, for axis org, we should get org_key.
            throw new Exception\ServerErrorException(
                'Failed to get org_key for axis org',
                ErrorCode::SERVER_ERROR);
        }

        return $orgKey;
    }

    protected static function getOrgIdForCrypt(Base\Entity $entity = null)
    {
        if (empty($entity) === true)
        {
            return null;
        }

        try
        {
            $orgId = self::getOrgIdFromEntity($entity);

            $orgId = Org\Entity::silentlyStripSign($orgId);

            return $orgId;
        }
        catch (\Throwable $ex) // If entity's org_id is null, then $entity->getOrgId() can throw TypeError. This will happen for unit tests where org_id is not passed in fixture
        {
            // should not happen in production, add an alert for this
            self::traceException($ex, TraceCode::BYOK_ALERT_ERROR_WHILE_GETTING_ORG_ID);

            return null;
        }
    }

    protected static function getOrgIdFromEntity(Base\Entity $entity)
    {
        $entityName = $entity->getEntityName();

        switch ($entityName)
        {
            case Entity::TERMINAL:
                return $entity->getOrgId();
            case Entity::KEY:
            case Entity::TOKEN:
            case Entity::BANK_ACCOUNT:
                return $entity->merchant->getOrgId();
            default:
                // should not reach here, getOrgIdFromEntity not implemented for the entity, please implement
                throw new Exception\ServerErrorException(
                    'Failed to get orgId from entity',
                    ErrorCode::SERVER_ERROR);
        }
    }

    protected static function getOrgKeyFromOrgId($orgId)
    {
        self::traceInfo(TraceCode::BYOK_GETTING_ORG_KEY_FROM_ORG_ID, ['org_id' => $orgId]);

        $configKey = 'app.byok_nonrzp_orgs_encryption_keys.encryption_key_' . $orgId;

        $app = App::getFacadeRoot();

        $key = $app['config']->get($configKey);

        return $key;
    }

    protected static function traceException($ex, $traceCode)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->traceException(
            $ex,
            Trace::ERROR,
            $traceCode,
            []);
    }

    protected static function traceInfo($traceCode, $data = [])
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->info($traceCode, $data);
    }

    protected static function getEntityClassName($entity)
    {
        if (empty($entity) === true)
        {
            return "";
        }

        return $entity->getEntityName();
    }
}
