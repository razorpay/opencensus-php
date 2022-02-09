<?php

namespace RZP\Models\Admin\Report;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Report;
use RZP\Services\DruidService;
use RZP\Trace\TraceCode;

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
        // Todo: only for test envs, remove after testing
//        config(['services.druid.mock' => false]);

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
    public function fetchDataForReport(string $type, $filters)
    {
        $merchantFactName = Entity::getFactNameByFactTypeForCurrentOrg(Constant::MERCHANT_FACT_NAME);
        $paymentFactName  = Entity::getFactNameByFactTypeForCurrentOrg(Constant::PAYMENT_FACT_NAME);

        switch ($type)
        {
            case Constant::REPORT_TYPE_DETAILED_TRANSACTION:
                [$error, $response] = $this->generateTransactionDetailData($paymentFactName, $filters);
                break;

            case Constant::REPORT_TYPE_DETAILED_MERCHANT:
                [$error, $response] = $this->generateMerchantDetailData($paymentFactName, $filters);
                break;

            case Constant::REPORT_TYPE_DETAILED_FAILURE:
                [$error, $response] = $this->generateDetailedFailureData($paymentFactName, $filters);
                break;

            case Constant::REPORT_TYPE_SUMMARY_MERCHANT:
                [$error, $response] = $this->generateSummaryMerchantData($merchantFactName, $filters);
                break;

            case Constant::REPORT_TYPE_SUMMARY_PAYMENT:
                [$error, $response] = $this->generateSummaryTransactionData($paymentFactName, $filters);
                break;

            case Constant::REPORT_TYPE_DETAILED_FAILURE_DETAIL:
                [$error, $response] = $this->generateDetailedFailureDetailData($paymentFactName, $filters);
                break;

            default: throw new Exception\InvalidArgumentException(
                'Not a valid report type.');
        }

        if(isset($error) === true)
        {
            throw new Exception\ServerErrorException(
                'Unable to process this request.', ErrorCode::SERVER_ERROR);
        }

        return $response;
    }

    public function generateTransactionDetailData($factName, $filters)
    {
        $count = $filters['count'] ?? self::COUNT;

        $skip = $filters['skip'] ?? self::SKIP;

        $selectQuery = 'SELECT
                  payments_merchant_id as partner_id,
                  method_custom as payment_method,
                  count(CASE WHEN is_success=1 THEN 1 END) as success_count,
                  count(CASE WHEN is_success=0 THEN 1 END) as failure_count,
                  sum(CASE WHEN is_success=1 THEN payments_base_amount END) as success_amount,
                  sum(CASE WHEN is_success=0 THEN payments_base_amount END) as failure_amount,
                  count(*) as total_count,
                  sum(payments_base_amount) as total_amount,
                  months
              from
              (
                SELECT *, EXTRACT(MONTH FROM __time) as months FROM  '.$factName.'
              )
                ';

        $conditions = [];

        $conditions = array_merge($conditions, $this->parseFilterEqualsForQuery($filters, Constant::FIELD_PARTNER_ID, 'payments_merchant_id'));

        $conditions = array_merge($conditions, $this->parseTimeFilters($filters));

        $groupByQuery = 'GROUP BY
                    payments_merchant_id,
                    method_custom,
                    months ';

        $orderByQuery = ' ORDER by
                    total_amount desc ';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery, $count, $skip);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        $result = [];

        foreach ($response as $row)
        {
            $sr = ($row[Constant::FIELD_SUCCESS_COUNT]) / ($row[Constant::FIELD_TOTAL_COUNT]);
            $row[Constant::FIELD_SUCCESS_RATE] = round($sr*100);

            $result[] = $row;
        }

        return [$error, $result];
    }

    public function generateMerchantDetailData($factName, $filters)
    {
        $count = $filters['count'] ?? self::COUNT;

        $skip = $filters['skip'] ?? self::SKIP;

        $selectQuery = 'SELECT
                  payments_merchant_id as partner_id,
                  merchants_name as merchant_name,
                  method_custom as payment_method,
                  sum(payments_base_amount) as payment_amount,
                  COUNT(*) as payment_count
              from  '.$factName.'
              ';

        $conditions = [];

        $conditions = array_merge($conditions, $this->parseFilterEqualsForQuery($filters, Constant::FIELD_PARTNER_ID, 'payments_merchant_id'));

        $conditions = array_merge($conditions, $this->parseTimeFilters($filters));

        $conditions = array_merge($conditions, $this->parseFilterEqualsForQuery($filters, Constant::FIELD_PAYMENT_METHOD, 'method_custom'));

        $groupByQuery = 'GROUP BY
                    payments_merchant_id,
                    method_custom,
                    merchants_name ';

        $orderByQuery = ' ORDER by
                    payment_amount desc ';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery, $count, $skip);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        $result = [];

        $counter = 0;

        foreach ($response as $row)
        {
            $row[Constant::FIELD_RANK] = $counter+$skip+1;

            $counter += 1;

            $result[] = $row;
        }

        return [$error, $result];
    }

    public function generateDetailedFailureData($factName, $filters)
    {
        $count = $filters['count'] ?? self::COUNT;

        $skip = $filters['skip'] ?? self::SKIP;

        $selectQuery = 'SELECT
                  count(*) as failure_count,
                  method_custom as payment_method,
                  sum(payments_base_amount) as failure_amount
              from  '.$factName.'
              ';

        $conditions = ['is_success=0'];

        $conditions = array_merge($conditions, $this->parseFilterEqualsForQuery($filters, Constant::FIELD_PARTNER_ID, 'payments_merchant_id'));

        $conditions = array_merge($conditions, $this->parseTimeFilters($filters));

        $groupByQuery = 'GROUP BY
                    method_custom ';

        $orderByQuery = ' ORDER by
                    failure_count desc ';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery, $count, $skip);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        return [$error, $response];
    }

    public function generateDetailedFailureDetailData($factName, $filters)
    {
        $selectQuery = 'SELECT
                  count(*) as failure_count,
                  payments_error_code as error_code,
                  method_custom as payment_method
              from  '.$factName.'
              ';

        $conditions = [' is_success=0 '];

        if(isset($filters[Constant::FIELD_PAYMENT_METHOD]) === true)
        {
            $conditions = array_merge($conditions, $this->parseFilterEqualsForQuery($filters, Constant::FIELD_PAYMENT_METHOD, 'method_custom'));
        }
        else
        {
            throw new Exception\InvalidArgumentException(
                'Payment method parameter missing');
        }

        $conditions = array_merge($conditions, $this->parseTimeFilters($filters));

        $groupByQuery = 'GROUP BY
                            payments_error_code,
                            method_custom ';

        $orderByQuery = ' ORDER by
                                failure_count desc ';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        return [$error, $response];
    }

    public function generateDetailedFailureDetailDownloadData($factName, $filters)
    {
        $count = $filters['count'] ?? self::COUNT;

        $skip = $filters['skip'] ?? self::SKIP;

        $selectQuery = 'SELECT
                  count(*) as payment_count,
                  method_custom,
                  payments_error_code,
                  payments_merchant_id as partner_id,
                  payments_internal_error_code as internal_code
              from  '.$factName.'
              ';

        $conditions = [' is_success=0 '];

        $conditions = array_merge($conditions, $this->parseFilterEqualsForQuery($filters, Constant::FIELD_PARTNER_ID, 'payments_merchant_id'));

        $conditions = array_merge($conditions, $this->parseTimeFilters($filters));

        $groupByQuery = 'GROUP BY
                        payments_merchant_id,
                        method_custom,
                        payments_error_code,
                        payments_internal_error_code ';

        $orderByQuery = ' ORDER by
                            payment_count desc ';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery, $count, $skip);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        // Todo: get *** STEP < SOURCE < REASON ***

//        // todo: remove/update this log
//        $this->trace->info(TraceCode::ADMIN_REPORTS_DRUID_QUERY,
//            [
//                'response'  => $response,
//                'error'     => $error,
//                'query'     => $q,
//            ]
//        );

        return [$error, $response];
    }

    public function generateSummaryMerchantData($factName, $filters)
    {
        $selectQuery = 'SELECT
                    count(CASE WHEN status=\'Moved out\' THEN 1 END) as moved_out,
                    count(CASE WHEN status=\'Active\' THEN 1 END) as active,
                    count(CASE WHEN status=\'Inactive\' THEN 1 END) as inactive,
                    count(*) total,
                    status
                from  '.$factName.'
                    ';

        $conditions = [];

        $conditions = array_merge($conditions, $this->parseTimeFilters($filters));

        $groupByQuery = 'GROUP BY
                            status ';

        $orderByQuery = ' ';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);


        return [$error, $response];
    }

    public function generateSummaryTransactionData($factName, $filters)
    {
        $selectQuery = '  SELECT
                    method_custom as payment_method,
                    count(CASE WHEN is_success=1 THEN 1 END) as success_count,
                    count(CASE WHEN is_success=0 THEN 1 END) as failure_count,
                    count(*) as total_count
                  from   '.$factName.'
                   ';

        $conditions = [];

        $conditions = array_merge($conditions, $this->parseTimeFilters($filters));

        $groupByQuery = 'GROUP BY
                            method_custom';

        $orderByQuery = '';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        return [$error, $response];
    }

    public function generateSingleMerchantDetailDownloadData($factName, $filters)
    {
        $selectQuery = 'SELECT
                  merchants_id as partner_id,
                  merchants_name as merchant_legal_name,
                  banking_hdfc_terminals_terminals_array as tid_list,
                  merchant_details_business_website as merchant_url,
                  merchant_details_business_registered_address as merchant_address
                from  '.$factName.'
                ';

        $conditions = [];

        if(isset($filters[Constant::FIELD_PARTNER_ID]) === true)
        {
            $conditions = array_merge($conditions, $this->parseFilterEqualsForQuery($filters, Constant::FIELD_PARTNER_ID, 'payments_merchant_id'));

        }
        else
        {
            throw new Exception\InvalidArgumentException(
                'Partner ID parameter missing');
        }

        $groupByQuery = '';

        $orderByQuery = '';

        $query = $this->prepareDruidQuery($selectQuery, $conditions, $groupByQuery, $orderByQuery);

        $druidPayload = [
            'query' => $query,
        ];

        [$error, $response] = $this->app['druid.service']->getDataFromDruid($druidPayload);

        return [$error, $response];
    }

    protected function parseTimeFilters($filters)
    {
        $result = [];

        if(isset($filters['from']))
        {
            $result[] = ' payments_created_at>'.$filters['from'];
        }

        if(isset($filters['from']) and isset($filters['to']))
        {
            $result[] = ' payments_created_at<'.$filters['to'];
        }

        return $result;
    }

    protected function parseFilterEqualsForQuery($filters, $filterName, $queryParamName)
    {
        if(isset($filters[$filterName]))
        {
            return [' '.$queryParamName.' = \''.$filters[$filterName].'\' '];
        }

        return [];
    }

    protected function parseWhereConditions(array $conditions): string
    {
        if (empty($conditions))
        {
            return '';
        }

        $result = ' WHERE ';

        $numConditions = count($conditions);

        for ($i=0; $i<$numConditions-1; $i += 1)
        {
            $result = $result.' '.$conditions[$i].' AND ';
        }

        $result = $result.' '.$conditions[$numConditions-1];

        return $result;
    }

    protected function prepareDruidQuery($selects, $conditions, $groupBys, $orderBy, $count=-1, $skip=-1): string
    {
        $query = $selects.
            ' ' .$this->parseWhereConditions($conditions).
            ' ' . $groupBys.
            ' ' . $orderBy;

        if($count > -1)
        {
            $query = $query. ' LIMIT '.$count;
        }

        if($skip > -1)
        {
            $query = $query.' OFFSET '.$skip;
        }

        return $query;
    }
}
