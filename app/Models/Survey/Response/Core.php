<?php

namespace RZP\Models\Survey\Response;

use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Base;
use RZP\lib\DataParser;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Survey\Tracker;

class Core extends Base\Core
{
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
}
