<?php

namespace RZP\Services\Dcs\Configurations;

use App;
use Razorpay\Dcs\Constants as SDKConstants;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Dcs\DataFormatter;
use RZP\Services\Dcs\Features\Service as FeaturesService;

class Service extends FeaturesService
{
    public function __construct($app = null)
    {
        $this->app = $app ?? App::getFacadeRoot();

        parent::__construct($this->app);
    }

    public function fetchConfiguration(string $key, string $entityId, array $fields, $mode = Mode::TEST)
    {
        $dcsKey = Constants::$configurationsToDCSKeyMapping[$key];

        $data = DataFormatter::toKeyMapWithOutId($dcsKey);

        $this->trace->info(TraceCode::DCS_FETCH_SERVICE_CONFIG_REQUEST,
            [
                'action'    => 'fetch_configuration',
                'key'       => $dcsKey,
                'fields'    => $fields,
                'mode'      => $mode,
                'data'      => $data,
                'entity_id' => $entityId
            ]);

        $res = [];

        $response = $this->client($mode)->fetchMultiple($data, [$entityId], $fields);

        if ($response === null) {

            return $res;
        }

        $kvs =  $response->getKvs() == null ? []: $response->getKvs();

        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();

            $res = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));
        }

        $this->trace->info(TraceCode::DCS_FETCH_RESPONSE_RECEIVED, [
            'action'    => 'fetch_configuration',
            'key'       => $dcsKey,
            'data'      => $data,
            'fields'    => $fields,
            'response'  => $res,
            'mode'      => $mode
        ]);

        return $res;
    }

    public function fetchConfigurationBulk(string $key, array $entityIds, array $fields, $mode = Mode::TEST)
    {
        $dcsKey = Constants::$configurationsToDCSKeyMapping[$key];

        $data = DataFormatter::toKeyMapWithOutId($dcsKey);

        $this->trace->info(TraceCode::DCS_FETCH_SERVICE_CONFIG_REQUEST,
            [
                'action'    => 'fetch_bulk_configuration',
                'key'       => $dcsKey,
                'fields'    => $fields,
                'mode'      => $mode,
                'data'      => $data,
                'entity_id' => $entityIds
            ]);

        $res = [];

        $response = $this->client($mode)->fetchMultiple($data, $entityIds, $fields);

        if ($response === null) {

            return $res;
        }

        $kvs =  $response->getKvs() == null ? []: $response->getKvs();

        $finalResponse = [];

        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();

            $res = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

            $finalResponse[$key->getEntityId()] = $res;
        }

        $this->trace->info(TraceCode::DCS_FETCH_RESPONSE_RECEIVED, [
            'action'    => 'fetch_bulk_configuration',
            'key'       => $dcsKey,
            'data'      => $data,
            'fields'    => $fields,
            'response'  => $finalResponse,
            'mode'      => $mode
        ]);

        return $finalResponse;
    }

    public function createConfiguration(string $key, string $entityId, array $input, $mode = Mode::TEST)
    {
        $dcsKey = Constants::$configurationsToDCSKeyMapping[$key];

        $data = DataFormatter::toKeyMapWithOutId($dcsKey);

        $request = $input;

        $this->trace->info(TraceCode::DCS_CREATE_SERVICE_CONFIG_REQUEST, [
            'action'            => 'create_configuration',
            'create_request'    => $request,
            'key'               => $dcsKey,
            'request_data'      => $data,
            'mode'              => $mode
        ]);

        $value = DataFormatter::marshal($request, DataFormatter::convertDCSKeyToClassName(DataFormatter::convertKeyStringToDCSKey($dcsKey)));

        // TODO change it to what ever client it is base on mode
        $res = $this->client($mode)->put($data, $entityId, $value, $this->getAuditInfo());

        $this->trace->info(TraceCode::DCS_CREATE_SERVICE_CONFIG_RESPONSE, [
            'action'    => 'create_configuration',
            'response'  => $res,
            'key'       => $dcsKey,
            'mode'      => $mode,
        ]);
    }

    public function editConfiguration(string $key, string $entityId, array $input, $mode = Mode::TEST)
    {
        $dcsKey = Constants::$configurationsToDCSKeyMapping[$key];

        $data = DataFormatter::toKeyMapWithOutId($dcsKey);

        $modifiedFields = array_keys($input);

        $request = $input;

        $this->trace->info(TraceCode::DCS_EDIT_SERVICE_CONFIG_REQUEST, [
            'action'            => 'edit_configuration',
            'edit_request'      => $request,
            'key'               => $dcsKey,
            'request_data'      => $data,
            'mode'              => $mode
        ]);

        $value = DataFormatter::marshal($request, DataFormatter::convertDCSKeyToClassName(DataFormatter::convertKeyStringToDCSKey($dcsKey)));

        // TODO change it to what ever client it is base on mode
        $res = $this->client($mode)->patch($data, $entityId, $value, $modifiedFields, $this->getAuditInfo());

        $this->trace->info(TraceCode::DCS_EDIT_SERVICE_CONFIG_RESPONSE, [
            'action'    => 'edit_configuration',
            'response'  => $res,
            'key'       => $dcsKey,
            'mode'      => $mode,
        ]);
    }

    public function fetchEntityIdsWithValueByConfigNameAndFieldNameFromDcs(string $config, string $field, $mode = Mode::TEST)
    {
        $key = Constants::$configurationsToDCSKeyMapping[$config];
        $data = DataFormatter::toKeyMapWithOutId($key);

        $key = $this->getAggregateKey($data);
        $this->trace->info(TraceCode::DCS_FETCH_REQUEST_RECEIVED, [
            'request_data' => $data,
            'key' => $key,
            'mode' => $mode,
        ]);

        $response = $this->client($mode)->aggregateFetch($data);

        $res = [];
        if ($response === null) {
            return $res;
        }
        $kvs =  $response->getKvs() == null ? []: $response->getKvs();
        foreach ($kvs as $index => $kv)
        {
            $data = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($kv->getKey()));

            foreach ($data as $fieldName => $value){
                if ($fieldName === $field)
                {
                    $res[$kv->getKey()->getEntityId()] = $value;
                }
            }
        }

        $this->trace->info(TraceCode::DCS_FETCH_RESPONSE_RECEIVED, [
            'response_count' => sizeof($res),
            'key' => $key,
            'mode' => $mode,
        ]);

        return $res;
    }

}
