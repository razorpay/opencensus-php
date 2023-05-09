<?php


namespace RZP\Models\Merchant\Store;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Status;
use RZP\Exception\InvalidPermissionException;
use RZP\Models\Merchant\Detail\Constants as DEConstant;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    //update all editable keys/values in the $input list for the role
    //if role does not have permission for writing key throw error
    public function updateMerchantStore(string $merchantId, array $input, string $role = Constants::PUBLIC)
    {
        (new Validator())->validateUpdateRequest($input, $role);

        $namespace = $input[Constants::NAMESPACE];

        unset($input[Constants::NAMESPACE]);

        foreach ($input as $key => $value)
        {
            $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

            $store->put($merchantId, $namespace, $key, $value);
        }

        return $input;
    }

    public function incrementKey(string $merchantId, string $key, int $increment)
    {
        $fetchData = $this->fetchValuesFromStore($merchantId, ConfigKey::ONBOARDING_NAMESPACE, [$key], Constants::INTERNAL);

        $data = [
            Constants::NAMESPACE => ConfigKey::ONBOARDING_NAMESPACE,
            $key                 => $fetchData[$key] + $increment
        ];

        $this->updateMerchantStore($merchantId, $data, Constants::INTERNAL);
    }

    public function fetchMerchantStore(string $merchantId, array $input, string $role = Constants::PUBLIC)
    {

        (new Validator())->validateInput('fetch', $input);

        $namespace = $input[Constants::NAMESPACE] ?? null;

        return $this->getAll($merchantId, $namespace, $role);
    }

    //delete all keys in the keys list for the role
    // if role does not have permission to delete error is thrown
    //if namespace is empty error is thrown
    public function deleteMerchantStore(string $merchantId, $namespace, array $keys, string $role = Constants::PUBLIC)
    {
        $data = [];

        $validator = (new Validator());

        $validator->validateNamespace(null, $namespace);

        (new Validator())->validateDeleteRequest($namespace,$keys, $role);

        foreach ($keys as $key)
        {
            $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

            $store->delete($merchantId, $namespace, $key);
        }

    }
    //return all readable keys in the keys list for the role
    //if namespace is empty error is thrown
    public function fetchValuesFromStore(string $merchantId, $namespace, array $keys, string $role = Constants::PUBLIC)
    {

        $data = [];

        $validator = (new Validator());

        $validator->validateNamespace(null, $namespace);

        foreach ($keys as $key)
        {
            $validator->validateKey($namespace, $key);

            $config = ConfigKey::NAMESPACE_KEY_CONFIG[$namespace][$key];

            if ($validator->isPermittedAction($config, Constants::READ, $role))
            {
                $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

                $data[$key] = $store->get($merchantId, $namespace, $key);
            }
            else
            {
                throw new InvalidPermissionException('Not permitted action ' . $role . ' for key ' . $key);
            }
        }

        return $data;
    }

    //return all readable keys in the namespace for the role
    //if namespace is empty return all readable keys for the role for all namespaces
    public function getAll(string $merchantId, string $namespace, string $role)
    {
        $data = [];

        $validator = (new Validator());

        if (empty($namespace) === false)
        {
            $configKeys = array_keys(ConfigKey::NAMESPACE_KEY_CONFIG[$namespace] ?? []);

            foreach ($configKeys as $key)
            {
                $config = ConfigKey::NAMESPACE_KEY_CONFIG[$namespace][$key];

                if ($validator->isPermittedAction($config, Constants::READ, $role))
                {
                    try
                    {
                        $instance = Factory::getInstance($key);

                        $data[$key] = $instance->getValue($merchantId, $namespace, $key);
                    }
                    catch (LogicException $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::FETCH_CONFIG_VALUE_FAILURE,
                            [
                                'key'         => $key,
                                'merchant_id' => $merchantId,
                            ]);
                    }
                }
            }
        }
        else
        {
            foreach (array_keys(ConfigKey::NAMESPACE_KEY_CONFIG) as $namespace)
            {
                $data[$namespace] = $this->getAll($merchantId, $namespace, $role);
            }
        }

        return $data;
    }
}
