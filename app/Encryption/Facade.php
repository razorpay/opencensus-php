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
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(), "BYOK_USE_ORG_KEY_FOR_ENCRYPTION_API", $mode);

        if ($variantFlag === 'on')
        {
            $orgKey = self::getOrgKeyForCrypt($entity);

            if (empty($orgKey) === true)
            {
                return parent::encrypt($data, $serialize);
            }

            self::traceInfo(TraceCode::BYOK_ENCRYPTING_USING_ORG_KEY);

            $newEncrypter = new Encrypter($orgKey, Config::get('app.cipher'));

            return $newEncrypter->encrypt($data, $serialize);
        }

        // If razorx is off
        return parent::encrypt($data, $serialize);
    }

    public static function decrypt($data, $unserialize = true,  Base\PublicEntity $entity = null)
    {
        $orgKey = self::getOrgKeyForCrypt($entity);

        if (empty($orgKey) === true)
        {
            return parent::decrypt($data, $unserialize);
        }

        self::traceInfo(TraceCode::BYOK_DECRYPTING_USING_ORG_KEY);

        try
        {
            $newEncrypter = new Encrypter($orgKey, Config::get('app.cipher'));

            return $newEncrypter->decrypt($data, $unserialize);
        }
        catch(DecryptException $ex) // If above we try to decrypt data that was encrypted by default key
        {
            self::traceInfo(TraceCode::BYOK_DECRYPTION_USING_ORG_KEY_FAILED);

            return parent::decrypt($data, $unserialize);
        }
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

        $orgId = self::getOrgIdFromEntity($entity);

        $orgId = Org\Entity::silentlyStripSign($orgId);

        return $orgId;
    }

    protected static function getOrgIdFromEntity(Base\Entity $entity)
    {
        $entityName = $entity->getEntityName();

        if ($entityName !== Entity::TERMINAL)
        {
            // should not reach here, getOrgIdFromEntity not implemented for the entity, please implement
            throw new Exception\ServerErrorException(
                'Failed to get orgId from entity',
                ErrorCode::SERVER_ERROR);

            return null;
        }

        try
        {
            return $entity->getOrgId();
        }
        catch(\TypeError $ex) // If entity's org_id is null, then $entity->getOrgId() can throw TypeError. This will happen for unit tests where org_id is not passed in fixture
        {
            // should not happen in production, add an alert for this
            self::traceException($ex, TraceCode::BYOK_GET_ORG_ID_FAILED_WITH_TYPE_ERROR);
        }

        return null;
    }

    protected static function getOrgKeyFromOrgId($orgId)
    {
        self::traceInfo(TraceCode::BYOK_DECRYPTING_USING_ORG_KEY, ['org_id' => $orgId]);

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
}
