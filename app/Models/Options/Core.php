<?php

namespace RZP\Models\Options;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Options\Helpers\Factory\DefaultOptionFactory;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::OPTIONS_CREATE_REQUEST, $input);

        $option = (new Entity)->generateId();

        $option->setMerchantId($merchant->getId());

        $option->setOptionsJson($input[Entity::OPTIONS] ?? []);

        $option->build($input);

        // before this step all necessary fields needed for options entity should be set
        $this->validateNamespaceAndService($option[Entity::NAMESPACE], $option[Entity::SERVICE_TYPE]);

        $this->checkDuplicates($option);

        $this->repo->transaction(function() use ($option, $input)
        {
            $this->repo->saveOrFail($option);
        });

        $this->trace->info(TraceCode::OPTIONS_CREATED, $option->toArrayPublic());

        return $option;
    }

    // to be used by internal payment link creation service
    public function createOptionForPaymentLink(array $input, Merchant\Entity $merchant, Invoice\Entity $invoice)
    {
        // setup create request params
        $request[Entity::OPTIONS] = $input[Entity::OPTIONS];
        $request[Entity::REFERENCE_ID_KEY] = $invoice->getId();
        $request[Entity::NAMESPACE_KEY] = Constants::NAMESPACE_PAYMENT_LINKS;
        $request[Entity::SERVICE_TYPE_KEY] = Constants::SERVICE_PAYMENT_LINKS;

        $this->create($request, $merchant);
    }

    private function checkDuplicates(Entity $option)
    {
        $isGlobalScopeRequest = true;

        // called via internal service
        if(empty($option->getAttribute(Entity::REFERENCE_ID)) === false)
        {
            // If reference id is sent, then set scope = entity
            $option->setScope(Constants::SCOPE_ENTITY);
            // internal requests should use scope as entity instead of global
            $isGlobalScopeRequest = false;
        }

        // check for duplicates and disallow
        if($isGlobalScopeRequest === true)
        {
            $existingOption = $this->fetchByNamespace($option[Entity::NAMESPACE], $option[Entity::MERCHANT_ID]);

            if(empty($existingOption) === false)
            {
                throw new BadRequestValidationFailureException(
                    sprintf(Constants::ERROR_MSG_DUPLICATE_NS_FIELD,
                        Entity::NAMESPACE, $option[Entity::NAMESPACE]
                    ));
            }
        }
        else
        {
            $existingOption = $this->fetchByServiceAndReferenceId($option[Entity::SERVICE_TYPE],
                $option[Entity::REFERENCE_ID], $option[Entity::MERCHANT_ID]);

            if(empty($existingOption) === false)
            {
                throw new BadRequestValidationFailureException(
                    sprintf(Constants::ERROR_MSG_DUPLICATE_SERVICE_REF_ID_FIELD,
                        Entity::NAMESPACE, $option[Entity::NAMESPACE],
                        Entity::SERVICE_TYPE, $option[Entity::SERVICE_TYPE],
                        Entity::REFERENCE_ID, $option[Entity::REFERENCE_ID]
                    ));
            }
        }
    }

    public function fetch(string $id): Entity
    {
        $tracePayload = [
            Entity::ID      => $id
        ];

        $this->trace->info(TraceCode::OPTIONS_READ_BY_ID_REQUEST, $tracePayload);

        $option = $this->repo->options->findByPublicIdAndMerchant($id, $this->merchant);

        return $option;
    }

    public function delete(Entity $option)
    {
        $this->trace->info(TraceCode::OPTIONS_DELETE_BY_ID_REQUEST, [$option->getId()]);

        return $this->repo->options->deleteOrFail($option);
    }

    public function update(Entity $option, array $input): Entity
    {
        $tracePayload = [
            Entity::OPTIONS      => $option,
            'INPUT'              => $input
        ];

        $this->trace->info(TraceCode::OPTIONS_UPDATE_REQUEST, $tracePayload);

        // update entity fields then save
        $option->setOptionsJson($input[Entity::OPTIONS] ?? []);

        $this->repo->transaction(function() use ($option, $input)
        {
            $this->repo->saveOrFail($option);
        });

        $this->trace->info(TraceCode::OPTIONS_UPDATED, $option->toArrayPublic());

        // send cache eviction request to PL service on successful update
        $plService = $this->app['paymentlinkservice'];

        $merchant = $this->repo->merchant->find($option->getMerchantId());

        // this func call will log the trace on error internally, so no need to add try/catch here
        $plService->notifyMerchantStatusAction($merchant);

        return $option;
    }

    public function findOptionsForMerchant(string $namespace = null, string $merchantId)
    {
        if(empty($namespace) === true)
        {
            return null;
        }

        $entity = $this->repo->options
            ->getOptionsForMerchantAndNamespace($merchantId, $namespace);

        return (empty($entity) === true) ? null : json_decode($entity->getAttribute(Entity::OPTIONS_JSON), true);
    }

    public function findOptionsForService(string $serviceName = null, string $referenceId = null, string $merchantId)
    {
        if(empty($serviceName) === true or empty($referenceId) === true)
        {
            return null;
        }

        $entity = $this->repo->options
            ->getOptionsForMerchantServiceAndReferenceId($merchantId, $serviceName, $referenceId);

        return (empty($entity) === true) ? null : json_decode($entity->getAttribute(Entity::OPTIONS_JSON), true);
    }

 public function getMergedOptions(
        string $namespace,
        string $serviceName = null,
        string $referenceId = null,
        string $merchantId)
    {
        return $this->find($namespace, $serviceName, $referenceId, $merchantId)[Constants::MERGED_OPTIONS];
    }

    public function find(string $namespace, string $serviceName, string $referenceId = null, string $merchantId)
    {
        $this->validateNamespaceAndService($namespace, $serviceName);

        $defaultOptions = (array) DefaultOptionFactory::find($namespace)->get();

        $merchantOptions =  (array) $this->findOptionsForMerchant($namespace, $merchantId);

        $serviceOptions = (array) $this->findOptionsForService($serviceName, $referenceId, $merchantId);

        $mergedOptions =  $this->mergeOptions($defaultOptions, $merchantOptions, $serviceOptions);

        $options = array();

        $options[Constants::DEFAULT_OPTIONS] = $defaultOptions;
        $options[Constants::MERCHANT_OPTIONS] = $merchantOptions;
        $options[Constants::SERVICE_OPTIONS] = $serviceOptions;
        $options[Constants::MERGED_OPTIONS] = $mergedOptions;

        return $options;
    }

    public function fetchByNamespace(string $namespace, string $merchantId)
    {
        $tracePayload = [
            Entity::MERCHANT_ID      => $merchantId,
            Entity::NAMESPACE        => $namespace
        ];

        $this->trace->info(TraceCode::OPTIONS_READ_BY_MERCHANT_NAMESPACE_REQUEST, $tracePayload);

        return $this->repo->options->getOptionsForMerchantAndNamespace($merchantId, $namespace);
    }

    public function fetchByServiceAndReferenceId(string $service, string $referenceId, string $merchantId)
    {
        $tracePayload = [
            Entity::MERCHANT_ID         => $merchantId,
            Entity::SERVICE_TYPE        => $service,
            Entity::REFERENCE_ID        => $referenceId
        ];

        $this->trace->info(TraceCode::OPTIONS_READ_BY_MERCHANT_SERVICE_REFERENCE_ID_REQUEST, $tracePayload);

        return $this->repo->options->getOptionsForMerchantServiceAndReferenceId($merchantId, $service, $referenceId);
    }

    public function mergeOptions(array $defaultOptions, array $merchantOptions = [], array $serviceOptions = [])
    {
        // make copy of input parameters
        $finalOptions = array_merge(array(), $defaultOptions);

        // apply merchant options on top of final options
        if(empty($merchantOptions) === false)
        {
            $finalOptions = $this->merge($finalOptions, $merchantOptions);
        }

        // apply service options on top of final options
        if(empty($serviceOptions) === false)
        {
            $finalOptions = $this->merge($finalOptions, $serviceOptions);
        }

        return $finalOptions;
    }

    // Using a in-built PHP merge function for now. Write a custom merge logic if this does not work as expected
    private function merge(array $arr1, array $arr2)
    {
        return array_replace_recursive($arr1, $arr2);
    }

    public function validateNamespaceAndService(string $namespace, string $serviceName)
    {
        $this->validateNamespace($namespace);

        $this->validateService($serviceName);
    }

    private function validateNamespace(string $namespace)
    {
        if(in_array($namespace , Constants::ALLOWED_NAMEPSPACES) === false)
        {
            throw new BadRequestValidationFailureException(
                sprintf(Constants::ERROR_MSG_NAMESPACE_NOT_SUPPORTED, $namespace));
        }
    }

    private function validateService(string $serviceName)
    {
        if(in_array($serviceName , Constants::ALLOWED_SERVICES) === false)
        {
            throw new BadRequestValidationFailureException(
                sprintf(Constants::ERROR_MSG_SERVICE_NOT_SUPPORTED, $serviceName));
        }
    }
}
