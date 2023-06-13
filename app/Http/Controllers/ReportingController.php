<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Services\Reporting;

class ReportingController extends Controller
{
    public function getConfig(string $id)
    {
        $data = $this->reportingService()->fetchConfigById($id);

        return ApiResponse::json($data);
    }

    public function listConfig()
    {
        $data = $this->reportingService()->fetchConfigMultiple($this->input);

        return ApiResponse::json($data);
    }

    public function createConfig()
    {
        $data = $this->reportingService()->createConfig($this->input);

        return ApiResponse::json($data);
    }

    public function createFullConfig()
    {
        $data = $this->reportingService()->createFullConfig($this->input);

        return ApiResponse::json($data);
    }

    public function updateConfig(string $id)
    {
        $data = $this->reportingService()->editConfig($id, $this->input);

        return ApiResponse::json($data);
    }

    public function updateFullConfig(string $id)
    {
        $data = $this->reportingService()->editFullConfig($id, $this->input);

        return ApiResponse::json($data);
    }

    public function updateBulkConfigs()
    {
        $data = $this->reportingService()->editBulkConfig($this->input);

        return ApiResponse::json($data);
    }


    public function deleteConfig(string $id)
    {
        $data = $this->reportingService()->deleteConfig($id);

        return ApiResponse::json($data);
    }

    public function createLog()
    {
        $data = $this->reportingService()->createLog($this->input);

        return ApiResponse::json($data);
    }

    public function updateLog(string $id)
    {
        $data = $this->reportingService()->editLog($id, $this->input);

        return ApiResponse::json($data);
    }

    public function getLog(string $id)
    {
        $data = $this->reportingService()->fetchLogById($id);

        return ApiResponse::json($data);
    }

    public function listLog()
    {
        $data = $this->reportingService()->fetchLogMultiple($this->input);

        return ApiResponse::json($data);
    }

    public function getSchedule(string $id)
    {
        $data = $this->reportingService()->fetchScheduleById($id);

        return ApiResponse::json($data);
    }

    public function listSchedule()
    {
        $data = $this->reportingService()->fetchScheduleMultiple($this->input);

        return ApiResponse::json($data);
    }

    public function createSchedule()
    {
        $data = $this->reportingService()->createSchedule($this->input);

        return ApiResponse::json($data);
    }

    public function deleteSchedule(string $id)
    {
        $data = $this->reportingService()->deleteSchedule($id);

        return ApiResponse::json($data);
    }
    public function updateSchedule(string $id)
    {
        $data = $this->reportingService()->updateSchedule($id, $this->input);

        return ApiResponse::json($data);
    }

    public function getConsumerRestrictions()
    {
        $data = $this->reportingService()->getConsumerRestrictions();

        return ApiResponse::json($data);
    }

    public function getTypes()
    {
        $path = 'config-type';

        return $this->proxy($path);
    }

    public function getComponentsByType($type)
    {
        $path = 'config-components/' . $type;

        return $this->proxy($path);
    }

    public function getOptions()
    {
        $path = 'config-options';

        return $this->proxy($path);
    }

    public function listThrottleSetting()
    {
        $path = 'throttle/settings';

        return $this->proxy($path);
    }

    public function createThrottleSetting()
    {
        $path = 'throttle/settings';

        return $this->proxy($path);
    }

    public function createConfigAdmin()
    {
        $data = $this->reportingService()->createConfigAdmin($this->input);

        return ApiResponse::json($data);
    }

    public function updateConfigAdmin(string $id)
    {
        $data = $this->reportingService()->editConfigAdmin($id, $this->input);

        return ApiResponse::json($data);
    }

    public function deleteConfigAdmin(string $id)
    {
        $data = $this->reportingService()->deleteConfigAdmin($id);

        return ApiResponse::json($data);
    }

    /**
     * Warning: Don't use this function from outside this class
     *
     * @return ApiResponse
     */
    protected function proxy($path)
    {
        $method = Request::method();
        $input = Request::all();

        $res = $this->reportingService()->createAndSendRequest($method, '/v1/'.$path, $input);

        return ApiResponse::json($res);
    }

    /**
     * Returns reporting service instance. It's not in constructor as it
     * depends on ba's vars which get set in middleware.
     * depends on ba's vars which get set in middleware.
     *
     * @return Reporting
     */
    protected function reportingService(): Reporting
    {
        return new Reporting();
    }
}
