<?php

namespace RZP\Models\Survey\Response;

use File;
use Config;
use Requests;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\User;
use RZP\Models\Base;
use \WpOrg\Requests\Response;
use RZP\lib\DataParser;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Survey\Tracker;
use RZP\Exception\BadRequestValidationFailureException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Core extends Base\Core
{
    const TEMP_PATH = '/tmp/';

    const TYPE = 'nps_survey';

    const CONTENT_TYPE_JSON = 'application/json';

    const API_BASE_URL = 'https://api.typeform.com/forms/';

    // Remote API set timeout in seconds.
    const API_TIMEOUT = 5;

    public function processSurveyWebhook(array $input)
    {
        $this->trace->info(TraceCode::SURVEY_TYPEFORM_HIDDEN_FIELDS, $input[Entity::FORM_RESPONSE][Entity::HIDDEN]);

        $trackerId = $input[Entity::FORM_RESPONSE][Entity::HIDDEN][Entity::TRACKER_ID] ?? null;

        $mid = $input[Entity::FORM_RESPONSE][Entity::HIDDEN][Entity::MID];

        $uid = $input[Entity::FORM_RESPONSE][Entity::HIDDEN][Entity::UID];

        if (empty($trackerId) === false)
        {
            return $this->processWebhooksWithTrackerId($input, $trackerId);
        }

        return $this->processWebhooksWithoutTrackerId($input, $uid, $mid);
    }

    /**
     * @param string $trackerId
     * @return Entity
     */
    private function checkIfSurveyAlreadyFilled(string $trackerId)
    {
        $surveyResponse = $this->repo->survey_response->getSurveyResponseByTrackerId($trackerId);

        return $surveyResponse;
    }

    private function processWebhooksWithoutTrackerId(array $input, string $uid, string $mid)
    {
        $user = $this->repo->user->findOrFailPublic($uid);

        $tracker = $this->repo->survey_tracker->getTrackerByUserEmail($user[User\Entity::EMAIL]);

        if (empty($tracker) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SURVEY_TRACKER_NOT_FOUND,
                null,
                [Entity::UID => $uid]);
        }

        $response = $this->processAndSaveResponse($input, $tracker);

        return $response;
    }

    private function processWebhooksWithTrackerId(array $input, string $trackerId)
    {
        $tracker = $this->repo->survey_tracker->findOrFailPublic($trackerId);

        $response = $this->processAndSaveResponse($input, $tracker);

        return $response;
    }

    private function processAndSaveResponse(array $input, Tracker\Entity $trackerEntity)
    {
        $typeformParser = DataParser\Factory::getDataParserImpl(DataParser\Base::TYPEFORM, $input);

        $typeformParsedData = json_encode($typeformParser->parseWebhookData());

        // Save only the first survey response, ignore the rest
        $surveyResponse = $this->checkIfSurveyAlreadyFilled($trackerEntity[Tracker\Entity::ID]);

        $this->trace->info(TraceCode::SURVEY_RESPONSE_TRACKER, [Entity::TRACKER_ID => $trackerEntity[Tracker\Entity::ID]]);

        if (empty($surveyResponse) === false)
        {
            $this->trace->info(TraceCode::SURVEY_TYPEFORM_RESPONSE_IGNORED,
                [
                    Entity::TRACKER_ID        => $trackerEntity[Tracker\Entity::ID],
                    Entity::FORM_RESPONSE     => $typeformParsedData
                ]);

            return [
                Entity::SUCCESS                 => true,
                Entity::SURVEY_RESPONSE_SAVED   => false
            ];
        }

        $surveyResponseInput = [
            Entity::TRACKER_ID        => $trackerEntity[Tracker\Entity::ID],
            Entity::SURVEY_ID         => $trackerEntity[Tracker\Entity::SURVEY_ID],
        ];

        $surveyResponseEntity = (new Entity)->build($surveyResponseInput);

        $this->repo->saveorFail($surveyResponseEntity);

        return [
            Entity::SUCCESS                 => true,
            Entity::SURVEY_RESPONSE_SAVED   => true
        ];
    }

    public function pushTypeFormResponsesToDataLake(array $input)
    {
        $formIds = $input["formIds"];

        if(empty($formIds) === true)
        {
            $this->trace->info(TraceCode::TYPEFORM_FORM_ID_INPUT_EMPTY);

            return [
                Entity::SUCCESS                 => false,
                Entity::SURVEY_RESPONSE_SAVED   => false
            ];
        }

        foreach ($formIds as $formId) {
            $formDetails = $this->getFormDetails($formId);

            if(array_key_exists('code', $formDetails) and $formDetails['code'] === "FORM_NOT_FOUND")
            {
                $this->trace->info(TraceCode::TYPEFORM_INCORRECT_FORM_ID);

                continue;
            }

            $typeformQuestionToQuestionId = $this->getTypeformParser($formDetails)->typeformQuestionToQuestionId();

            $typeformResponses = $this->getTypeformResponses($formId);

            $parsedResponses = $this->getTypeformParsedResponses($typeformQuestionToQuestionId, $typeformResponses, $formId);

            if(empty($parsedResponses) === false)
            {
                //Final data in the proposed template to be pushed to datalake
                $data = $parsedResponses;

                $tempFileName = "nps_response" . '_' . $formId . Carbon::now(Timezone::IST)->getTimestamp() . '.txt';

                $tempFileFullPath = self::TEMP_PATH . $tempFileName;

                $fileHandle = fopen($tempFileFullPath, 'w');

                fwrite($fileHandle, $data);

                fclose($fileHandle);

                $fileAccessUrl = $this->uploadViaUfh($tempFileFullPath);
            }
        }

        return [
            Entity::SUCCESS                 => true,
            Entity::SURVEY_RESPONSE_SAVED   => true
        ];
    }

    private function getFormDetails($formId)
    {
        $endpoint = self::API_BASE_URL . $formId;

        $headers = $this->getHeaders();

        $options = [
            'timeout' => self::API_TIMEOUT,
        ];

        $response = Requests::get($endpoint, $headers, $options);

        return $this->validateResponse($response, TraceCode:: TYPEFORM_FORM_DATA_FETCH);
    }

    private function getTypeformParsedResponses($typeformQuestionToQuestionId, $typeformResponses, $formId)
    {
        $typeformParsedDataForCompleteResponses = $this->getTypeformParser($typeformResponses[0])
                                                       ->parseTypeformCompleteResponses($typeformQuestionToQuestionId, $formId);

        $typeformParsedDataForIncompleteResponses = $this->getTypeformParser($typeformResponses[1])
                                                         ->parseTypeformIncompleteResponses($typeformQuestionToQuestionId, $formId);

        return $typeformParsedDataForCompleteResponses . $typeformParsedDataForIncompleteResponses;
    }

    public function getTypeformResponses($formId){
        $API_BASE_URL_COMPLETE = $this->getTypeformUrl($formId, "true");

        $API_BASE_URL_INCOMPLETE = $this->getTypeformUrl($formId, "false");

        $headers = $this->getHeaders();

        $headers['Authorization'] = 'Bearer ' . Config::get('applications.typeform.typeform_api_key');

        $options = [
            'timeout' => self::API_TIMEOUT,
        ];

        $complete_responses = $this->validateResponse(Requests::get($API_BASE_URL_COMPLETE, $headers, $options), TraceCode::TYPEFORM_COMPLETE_RESPONSES);

        $incomplete_responses = $this->validateResponse(Requests::get($API_BASE_URL_INCOMPLETE, $headers, $options), TraceCode::TYPEFORM_INCOMPLETE_RESPONSES);

        return array($complete_responses, $incomplete_responses);
    }

    /**
     * Validates remote API response and returns array response.
     *
     * @param \WpOrg\Requests\Response $resp
     *
     * @param $traceCode
     * @return array
     * @throws BadRequestValidationFailureException
     */
    protected function validateResponse($resp, string $traceCode): array
    {
        $code      = $resp->status_code;
        $body      = $resp->body;
        $jsonResp  = json_decode($body, true);
        $jsonError = json_last_error();
        $success   = (($jsonError === JSON_ERROR_NONE));

        $this->trace->info(
            $traceCode,
            compact('code', 'body', 'success'));

        if ($success === false)
        {
            throw new BadRequestValidationFailureException(
                'Request failed, please try again later.',
                null,
                compact('code', 'body'));
        }

        return $jsonResp;
    }

    protected function uploadViaUfh(string $pathToTemporaryFile)
    {
        $ufhService = $this->app['ufh.service'];

        $uploadedFileInstance = $this->getUploadedFileInstance($pathToTemporaryFile);

        $response = $ufhService->uploadFileAndGetUrl($uploadedFileInstance,
            $name = File::name($pathToTemporaryFile),
            self::TYPE,
            null);

        $this->trace->info(
            TraceCode::UFH_RESPONSE,
            [
                'ufh_response'          => $response,
            ]);

        return $response;
    }

    protected function getUploadedFileInstance(string $path)
    {
        $name = File::name($path);

        $extension = File::extension($path);

        $originalName = $name . '.' . $extension;

        $mimeType = File::mimeType($path);

        $size = File::size($path);

        $error = null;

        // Setting as Test, because UploadedFile expects the file instance to be a temporary uploaded file, and
        // reads from Local Path only in test mode. As our requirement is to always read from local path, so
        // creating the UploadedFile instance in test mode.

        $test = true;

        $object = new UploadedFile($path, $originalName, $mimeType, $error, $test);

        return $object;
    }

    private function getTypeformParser($type)
    {
        return DataParser\Factory::getDataParserImpl(DataParser\Base::TYPEFORM, $type);
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => self::CONTENT_TYPE_JSON,
            'Content-Type' => self::CONTENT_TYPE_JSON,
        ];

        return $headers;
    }

    private function getTypeformUrl($formId, $completed)
    {
        $from = Carbon::today(Timezone::IST)->subHours(24)->getTimestamp();

        $to = Carbon::today(Timezone::IST)->getTimestamp();

        return self::API_BASE_URL . $formId . '/responses?page_size=1000&since=' . $from . '&until=' . $to . '&completed=' . $completed ;
    }
}
