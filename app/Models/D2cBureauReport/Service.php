<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\D2cBureauDetail;

class Service extends Base\Service
{
    public function getReport(D2cBureauDetail\Entity $bureauDetail, Merchant\Entity $merchant, User\Entity $user): Entity
    {
        $report = $this->repo->d2c_bureau_report->findByProviderDetailIdAndMerchantId(Provider::EXPERIAN, $bureauDetail->getId(), $merchant->getId());

        if ($report !== null)
        {
            return $report;
        }

        return $this->core()->saveAndReturnReport($bureauDetail, $merchant, $user);
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

        return $this->app['ufh.service']->getSignedUrl($ufhFileId, [], $bureauReport->getMerchantId());
    }
}
