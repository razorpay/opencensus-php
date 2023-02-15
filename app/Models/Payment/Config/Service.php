<?php


namespace RZP\Models\Payment\Config;

use RZP\Diag\EventCode;
use RZP\Models\Base;
use RZP\Models\Order\Entity as OrderEntity;
use RZP\Trace\TraceCode;


class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * @param string           $type
     * @param array            $input
     *
     * @return array
     */
    public function fetch(string $type = 'checkout', $input)
    {
        $configs = $this->repo->config->fetchConfigByMerchantIdAndType($this->merchant->getId(), $type, $input);

        return $configs->toArrayPublic();
    }

    public function fetchPaymentConfigForCheckout($input)
    {
        (new Validator())->validateInput('fetch_payment_config_for_checkout', $input);

        $configId = $input['config_id'] ?? null;

        $checkoutConfig = $this->core->getFormattedConfigForCheckout($configId, $this->merchant->getId());

        // Type-casting the response to object as the golang checkout-service
        // wouldn't be able to parse empty array `[]` as response as it expects
        // an empty json `{}` response
        return (object) ($checkoutConfig ?? []);
    }

    public function internalFetchById($id)
    {
        $config = $this->repo->config->fetchByIdAndNotDeleted($id);

        return $config->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $configs =  $this->repo->config->fetchMultipleByParam($input);

        return $configs->toArrayPublic();
    }

    /**
     * @param array           $input
     *
     *
     * @return Entity
     */
    public function create(array $input)
    {
        $config = $this->core->create($input);

        return $config->toArrayPublic();
    }

    /**
     * @param array           $input
     *
     *
     * @return Entity
     */
    public function update(array $input)
    {
        (new Validator())->validateInput('edit', $input);

        if ($input['type'] === Type::LATE_AUTH)
        {
            return $this->updateLateAuthConfig($input);
        }
        else
        {
            $config = $this->core->update($input);
        }

        return $config->toArrayPublic();
    }


    private function updateLateAuthConfig(array $input)
    {
        $config = $this->core->updateLateAuthConfig($input);

        return $config->toArrayPublic();
    }

    public function updateLateAuthConfigBulk(array  $input)
    {
        $this->trace->info(TraceCode::CONFIG_UPDATE_BULK_REQUEST, $input);

        (new Validator())->validateInput('edit_bulk', $input);

        $merchantIds = $input['merchant_ids'];

        $success  = 0;
        $failures = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $bulkUploadInput = array(
                    'type' => Type::LATE_AUTH,
                    'config' => $input['config'],
                );

                $this->core->withMerchant($merchant)->updateLateAuthConfig($bulkUploadInput);

                $success += 1;
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);

                $failures[] = $merchantId;
            }
        }

        $summary  = [
            'success'  => $success,
            'failures' => $failures
        ];

        $this->trace->info(TraceCode::CONFIG_UPDATE_BULK_RESPONSE, $summary);

        return $summary;
    }

    public function createBulk(array  $input)
    {
        $this->trace->info(TraceCode::CONFIG_CREATE_BULK_REQUEST, $input);

        (new Validator())->validateInput('create_bulk', $input);

        $merchantIds = $input['merchant_ids'];

        $type = $input['type'];

        $success  = 0;
        $failures = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $name = $type.'_'.$merchantId;

                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $bulkCreateInput = array(
                    'type'       => $type,
                    'config'     => $input['config'],
                    'name'       => $name,
                    'is_default' => $input['is_default'],
                );

                $this->core->withMerchant($merchant)->create($bulkCreateInput);

                $success += 1;
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);

                $failures[] = $merchantId;
            }
        }

        $summary  = [
            'success'  => $success,
            'failures' => $failures
        ];

        $this->trace->info(TraceCode::CONFIG_CREATE_BULK_RESPONSE, $summary);

        return $summary;
    }

    public function delete($input)
    {
        $this->trace->info(TraceCode::CONFIG_DELETE_REQUEST, $input);

        $merchantIds = $input['merchant_ids'];

        $success  = 0;
        $failures = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->repo->config->deletePaymentConfig($merchantId, $input['type']);

                $success += 1;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e,null, TraceCode::CONFIG_DELETE_EXCEPTION);

                $failures[] = $merchantId;
            }
        }

        $summary  = [
            'success'  => $success,
            'failures' => $failures
        ];

        $this->trace->info(TraceCode::CONFIG_DELETE_RESPONSE, $summary);

        return $summary;
    }
}
