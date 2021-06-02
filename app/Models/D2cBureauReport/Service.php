<?php

namespace RZP\Models\D2cBureauReport;

use Carbon\Carbon;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\D2cBureauDetail;
use RZP\Jobs\D2cCsvReportCreate;

class Service extends Base\Service
{
    public function getReport(D2cBureauDetail\Entity $bureauDetail,
                              Merchant\Entity $merchant = null,
                              User\Entity $user = null): Entity
    {
        if ($merchant === null)
        {
            $merchant = $this->repo->merchant->findOrFail($bureauDetail->getMerchantId());
        }

        if ($user === null)
        {
            $user = $this->repo->user->findOrFail($bureauDetail->getUserId());
        }

        $reportValidity = Carbon::now(Timezone::IST)->subDays(15)->getTimestamp();

        $report = $this->repo->d2c_bureau_report->findByProviderDetailIdAndMerchantIdCreatedAfter(
                                                        Provider::EXPERIAN,
                                                        $bureauDetail->getId(),
                                                        $merchant->getId(),
                                                        $reportValidity);

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

    public function fetchReport($id)
    {
        $bureauReport = $this->repo->d2c_bureau_report->findByPublicId($id);

        return $bureauReport->toArrayForDashboard();
    }

    public function deleteReport($id)
    {
        $bureauReport = $this->repo->d2c_bureau_report->findByPublicId($id);

        $this->repo->deleteOrFail($bureauReport);

        return [];
    }

    public function fetchReportForLos($input)
    {
        $this->trace->info(TraceCode::LOS_D2C_BUREAU_REPORT_FETCH, $input);

        if (empty($input['d2c_bureau_report_id']) === false)
        {
            $input['d2c_bureau_report_id'] = Entity::verifyIdAndStripSign($input['d2c_bureau_report_id']);
        }

        $bureauReport = $this->repo->d2c_bureau_report->findByParams($input);

        if ($bureauReport === null)
        {
            throw new BadRequestValidationFailureException('no report found for '. json_encode($input));
        }
        if (empty($input['pan']) === false)
        {
            $d2cBureauDetail = (new D2cBureauDetail\Repository)->findByIdMerchantIdAndPan($bureauReport[Entity::D2C_BUREAU_DETAIL_ID], $bureauReport[Entity::MERCHANT_ID], $input['pan']);
        }
        else
        {
            $d2cBureauDetail = (new D2cBureauDetail\Repository)->findByIdAndMerchantId($bureauReport[Entity::D2C_BUREAU_DETAIL_ID], $bureauReport[Entity::MERCHANT_ID]);
        }

        if ($d2cBureauDetail === null)
        {
            throw new BadRequestValidationFailureException('no report found for '. json_encode($input));
        }

        $bureauReport = $this->getReportArrayForLos($bureauReport);

        $bureauDetailArray = $d2cBureauDetail->toArrayPublic();

        $bureauReport[Entity::REQUEST_OBJECT] = $bureauDetailArray;

        return $bureauReport;
    }

    public function getReportArrayForLos($bureauReport)
    {
        $bureauReport = $bureauReport->toArrayForDashboard();

        if ((empty($bureauReport[Entity::NTC_SCORE]) === false) ||
             ($bureauReport[Entity::NTC_SCORE] === 0))
        {
            $bureauReport['ntc_score'] = strval($bureauReport['ntc_score']);
        }

        return $bureauReport;
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

        if(is_null($ufhFileId) === false)
        {
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
        else
        {
            return [
                'signed_url'     => null,
                'csv_signed_url' => null,
            ];
        }
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
