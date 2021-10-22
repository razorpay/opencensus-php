<?php


namespace RZP\Models\Merchant\Store;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\InvalidPermissionException;
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

    public function fetchMerchantStore(string $merchantId, array $input, string $role = Constants::PUBLIC)
    {

        (new Validator())->validateInput('fetch', $input);

        $namespace = $input[Constants::NAMESPACE] ?? null;

        return $this->getAll($merchantId, $namespace, $role);;
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
                    $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

                    $data[$key] = $store->get($merchantId, $namespace, $key);
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
