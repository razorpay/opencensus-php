<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Services\Mozart;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
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

    const INVALID_EMAIL_OR_CONTACT_REGEX = '/mobile number ([0-9,X]+)/';

    public function saveAndReturnReport(D2cBureauDetail\Entity $bureauDetail, Merchant\Entity $merchant, User\Entity $user): Entity
    {
        $bureauDetailArray = $bureauDetail->toArrayPublic();

        $this->providerSpecificProcessing($bureauDetailArray, Provider::EXPERIAN);

        $request['d2c_bureau_details'] = $bureauDetailArray;

        /** @var Mozart $mozartService */
        $mozartService = $this->app->mozart;

        $retryCount = 3;

        while (true)
        {
            try
            {
                $response = $mozartService->sendMozartRequest(self::MOZART_NAMESPACE,
                                                              Provider::EXPERIAN,
                                                              self::MOZART_GET_REPORT_ACTION,
                                                                $request,
                                                              Mozart::DEFAULT_MOZART_VERSION,
                                                              true);

                break;
            }
            catch (\RZP\Exception\GatewayErrorException $e)
            {
                if ((in_array($e->getError()->getInternalErrorCode(), [
                    ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                    ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
                ], true) === true) and
                    ($retryCount > 0))
                {
                    $retryCount--;

                    continue;
                }

                $input = [
                    Entity::PROVIDER    => Provider::EXPERIAN,
                    Entity::ERROR_CODE  => $e->getError()->getInternalErrorCode(),
                ];

                $report = (new Entity)->build($input);

                $report->merchant()->associate($merchant);

                $report->user()->associate($user);

                $report->d2cBureauDetail()->associate($bureauDetail);

                $this->repo->saveOrFail($report);

                if ($e->getCode() === ErrorCode::BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT)
                {
                    preg_match(self::INVALID_EMAIL_OR_CONTACT_REGEX, $e->getData()['data']['error'], $matches);
                    $errorDesc = 'No records found for this phone number.';
                    if (sizeof($matches) !== 0)
                    {
                        $errorDesc .= ' Please try again with ' . $matches['1'];
                    }
                    else
                    {
                        $errorDesc .= ' Please contact support - capital.support@razorpay.com';
                    }
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT,
                        null,
                        $e->getData(),
                        $errorDesc);
                }


                throw $e;
            }
            catch (\WpOrg\Requests\Exception $e)
            {
                if ($retryCount > 0)
                {
                    $retryCount--;

                    continue;
                }
            }
        }

        // Response contains 4 keys:
        // 1. score: credit score of owner.
        // 2. report: map of attributes to be shown on dashboard mandatorily. this will be saved in json format in db.
        // 3. raw_report: whole dump to be saved in filestore.
        // 4. ntc_score: this field cantain a value 1- 10 for merchants who does not have experian record,
        //    other fields will be empty when ntc_score is not null

        $input = [];

       if (empty($response['data']['score']) === false)
       {
            $fileName = 'report_' . Provider::EXPERIAN . '_' . $bureauDetail->getPublicId() . '.txt';

            $filePath = $this->createTxtFile($fileName, json_encode($response['data']['raw_report']));

            $file = new UploadedFile($filePath, $fileName, 'text/plain', null, true);

            $ufhFile = $this->app['ufh.service']->uploadFileAndGetUrl($file, $fileName, 'bureau_report', $bureauDetail);

            $input = [
                Entity::SCORE        => (int) $response['data']['score'],
                Entity::REPORT       => json_encode($response['data']['report']),
                Entity::PROVIDER     => Provider::EXPERIAN,
                Entity::UFH_FILE_ID  => $ufhFile[UfhService::FILE_ID],
                Entity::NTC_SCORE    => null,
            ];
        }
        else
        {
            $input = [
                Entity::SCORE       => null,
                Entity::REPORT      => null,
                Entity::PROVIDER    => Provider::EXPERIAN,
                Entity::UFH_FILE_ID => null,
                Entity::NTC_SCORE   => (int) $response['data']['ntc_score'],
            ];
        }

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
                $bureauDetailArray[D2cBureauDetail\Entity::ADDRESS] = preg_replace('!\s+!', ' ', preg_replace('/[^a-zA-Z0-9 ]+/', '', $bureauDetailArray[D2cBureauDetail\Entity::ADDRESS]));

                $bureauDetailArray['buildingName'] = substr($bureauDetailArray[D2cBureauDetail\Entity::ADDRESS], 40, 40) ?: '';

                $bureauDetailArray['roadName'] = substr($bureauDetailArray[D2cBureauDetail\Entity::ADDRESS], 80, 40) ?: '';

                $bureauDetailArray[D2cBureauDetail\Entity::ADDRESS] = substr($bureauDetailArray[D2cBureauDetail\Entity::ADDRESS], 0, 40);
        }
    }
}
