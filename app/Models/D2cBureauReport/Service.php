<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\D2cBureauDetail;
use RZP\Models\Merchant\Account;
use RZP\Jobs\D2cCsvReportCreate;

class Service extends Base\Service
{
    public function getReport(D2cBureauDetail\Entity $bureauDetail, Merchant\Entity $merchant, User\Entity $user): Entity
    {
        $report = $this->repo->d2c_bureau_report->findByProviderDetailIdAndMerchantId(Provider::EXPERIAN, $bureauDetail->getId(), $merchant->getId());

        if ($report !== null)
        {
            return $report;
        }

        $report = $this->core()->saveAndReturnReport($bureauDetail, $merchant, $user);

        D2cCsvReportCreate::dispatch($this->mode, $report);

        return $report;
    }

    public function update($id, array $input): array
    {
        $this->trace->info(TraceCode::D2C_BUREAU_REPORT_UPDATE, [
            'id'    => $id,
            'input' => $input,
        ]);

        /** @var Entity $bureauReport */
        $bureauReport = $this->repo->d2c_bureau_report->findByPublicIdAndMerchant($id, $this->merchant);

        $bureauReport->edit($input);

        $this->repo->saveOrFail($bureauReport);

        return $bureauReport->toArrayForDashboard();
    }

    public function getDownloadUrl($id)
    {
        $this->trace->info(TraceCode::D2C_BUREAU_REPORT_DOWNLOAD_REQUEST, [
            'id'    => $id,
        ]);

        /** @var Entity $bureauReport */
        $bureauReport = $this->repo->d2c_bureau_report->findByPublicId($id);

        $ufhFileId = $bureauReport->getUfhFileId();

        $csvUfhFileId = $bureauReport->getCsvReportUfhFileId();

        if (is_null($csvUfhFileId) === true)
        {
            $D2cCsvReport = new D2cCsvReportCreate($this->mode, $bureauReport);

            dispatch_now($D2cCsvReport);

            $bureauReport = $this->repo->d2c_bureau_report->findByPublicId($id);

            $csvUfhFileId = $bureauReport->getCsvReportUfhFileId();
        }
        return [
            'signed_url'     => $this->app['ufh.service']->getSignedUrl($ufhFileId,
                                                                        [],
                                                                        $bureauReport->getMerchantId())['signed_url'],

            'csv_signed_url' => $this->app['ufh.service']->getSignedUrl($csvUfhFileId,
                                                                        [],
                                                                        $bureauReport->getMerchantId())['signed_url']
        ];
    }

    public function getCsvReport()
    {
        $reports = $this->repo->d2c_bureau_report->getReportsForCsvCreation(Provider::EXPERIAN);

        $response = [
            'merchant_id_list'  => [],
        ];

        $count = 0;

        foreach ($reports as $report)
        {
            D2cCsvReportCreate::dispatch($this->mode, $report);

            $count++;

            array_push($response['merchant_id_list'], $report['merchant_id']);
        }

        $response['count'] = $count;

        return $response;
    }
}
