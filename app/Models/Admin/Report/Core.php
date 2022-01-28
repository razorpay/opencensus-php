<?php

namespace RZP\Models\Admin\Report;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Report;
use RZP\Services\DruidService;

class Core extends Base\Core
{
    const COUNT = 20;
    const SKIP = 0;

    /*
     * Generate the data for a report with type=$type and a list of filters from the user(admin).
     * Parses input filters.
     */
    public function generateReportData($type, $filters)
    {
        $applicableFilters = Entity::getFiltersForReportType($type);

        $defaultFilters = Entity::getFiltersForReportType(Constant::DEFAULT);

        $applicableFilters = array_merge($applicableFilters, $defaultFilters);
        $parsedFilters = [];

        foreach ($applicableFilters as $filterName => $filter)
        {
            if(isset($filters[$filterName]) === true)
            {
                $parsedFilters[$filterName] = $filters[$filterName];
            }
        }

        $data = $this->fetchDataForReport($type, $parsedFilters);

        return $data;
    }

    /*
     * Fetch data for reports from druid depending on the report type
     */
    public function fetchDataForReport($type, $filters)
    {
        $query = $this->fetchReportQuery($type, $filters);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        if(isset($error) === true)
        {
            throw new Exception\ServerErrorException(
                'Unable to process this request.', ErrorCode::SERVER_ERROR);
        }

        return $response;
    }

    public function fetchReportQuery($type, $filters)
    {
        $merchantFactName = Entity::getFactNameByOrgId(Constant::MERCHANT_FACT_NAME);
        $paymentFactName  = Entity::getFactNameByOrgId(Constant::PAYMENT_FACT_NAME);

        switch ((string)$type)
        {
            case Constant::REPORT_TYPE_DETAILED_TRANSACTION:
                return $this->generateTransactionDetailQuery($paymentFactName, $filters);

            default: throw new Exception\InvalidArgumentException(
                'Not a valid report type.');
        }
    }

    public function generateTransactionDetailQuery($factName, $filters)
    {
        $query = 'SELECT * FROM druid.'.$factName.' WHERE ';

        $count = self::COUNT;

        $skip = self::SKIP;

        return $query;
    }
}
