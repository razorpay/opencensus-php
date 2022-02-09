<?php

namespace RZP\Models\Admin\Report;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin\Report;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Report\Core();
    }

    /**
     * Get filters list by report type
     */
    public function adminReportsFiltersGetByType(array $input): array
    {
        $type = $input['type'];

        (new Validator)->validateType($type);

        $applicableFilters = Entity::getFiltersForReportType($type);

        return [
            'version'   => 1,
            'fields'    => [],
            'entities'  => [
                'admin_report' => $applicableFilters
            ],
        ];
    }

    /**
     * Get report data for UI by report type + filter params
     */
    public function adminReportsGetReportData(array $input)
    {
        $type = $input['type'];

        (new Validator)->validateType($type);

        // Todo:  Temporary dummy data, to be replaced after Druid integration

        $reportData = $this->core->generateReportData($type, $input);

        return [
            'count' => count($reportData),
            'entity' => 'collection',
            'items' => $reportData,
        ];
    }

    /**
     * Initiate generation of downloadable report file by report type + filter params
     */
    public function adminReportsGetReportsByType(array $input)
    {
        return [];
    }

    /**
     * Get list of available reports for an admin
     */
    public function adminReportsGetReportsForAdmin(array $input)
    {
        return [];
    }

    /**
     * Initiate download of a report file
     */
    public function adminReportsGetReportById(array $input)
    {
        return [];
    }
}
