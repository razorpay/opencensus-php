<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Services\Mozart;
use RZP\Models\Merchant;
use RZP\Services\UfhService;
use RZP\Models\D2cBureauDetail;
use Illuminate\Http\UploadedFile;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;

    const EXTENSION = 'txt';

    const FILE_NAME_PREFIX = 'bureau_report_';

    const MOZART_NAMESPACE = 'capital';

    const MOZART_GET_REPORT_ACTION = 'get_report';

    public function saveAndReturnReport(D2cBureauDetail\Entity $bureauDetail, Merchant\Entity $merchant, User\Entity $user): Entity
    {
        $bureauDetailArray = $bureauDetail->toArrayPublic();

        $this->providerSpecificProcessing($bureauDetailArray, Provider::EXPERIAN);

        $request['d2c_bureau_details'] = $bureauDetailArray;

        /** @var Mozart $mozartService */
        $mozartService = $this->app->mozart;

        $response = $mozartService->sendMozartRequest(self::MOZART_NAMESPACE,
                                                      Provider::EXPERIAN,
                                                      self::MOZART_GET_REPORT_ACTION,
                                                      $request,
                                                      Mozart::DEFAULT_MOZART_VERSION,
                                                      true);

        // Response contains 3 keys:
        // 1. score: credit score of owner.
        // 2. report: map of attributes to be shown on dashboard mandatorily. this will be saved in json format in db.
        // 3. raw_report: whole dump to be saved in filestore.

        $fileName = 'report_' . Provider::EXPERIAN . '_' . $bureauDetail->getPublicId() . '.txt';

        $filePath = $this->createTxtFile($fileName, json_encode($response['data']['raw_report']));

        $file = new UploadedFile($filePath, $fileName, 'text/plain', filesize($filePath), null, true);

        $ufhFile = $this->app['ufh.service']->uploadFileAndGetUrl($file, $fileName, 'bureau_report', $bureauDetail);

        $input = [
            Entity::SCORE       => (int) $response['data']['score'],
            Entity::REPORT      => json_encode($response['data']['report']),
            Entity::PROVIDER    => Provider::EXPERIAN,
            Entity::UFH_FILE_ID => $ufhFile[UfhService::FILE_ID],
        ];

        $report = (new Entity)->build($input);

        $report->merchant()->associate($merchant);

        $report->user()->associate($user);

        $report->d2cBureauDetail()->associate($bureauDetail);

        $this->repo->saveOrFail($report);

        return $report;
    }

    private function providerSpecificProcessing(array & $bureauDetailArray, string $provider)
    {
        switch ($provider)
        {
            case Provider::EXPERIAN:

                $bureauDetailArray[D2cBureauDetail\Entity::ADDRESS] = preg_replace('/[^a-zA-Z0-9 ]+/', '', $bureauDetailArray[D2cBureauDetail\Entity::ADDRESS]);

                $bureauDetailArray['buildingName'] = substr($bureauDetailArray[D2cBureauDetail\Entity::ADDRESS], 40, 40) ?: '';

                $bureauDetailArray['roadName'] = substr($bureauDetailArray[D2cBureauDetail\Entity::ADDRESS], 80, 40) ?: '';

                $bureauDetailArray[D2cBureauDetail\Entity::ADDRESS] = substr($bureauDetailArray[D2cBureauDetail\Entity::ADDRESS], 0, 40);
        }
    }

}
