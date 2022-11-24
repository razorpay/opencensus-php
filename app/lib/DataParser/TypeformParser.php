<?php

namespace RZP\lib\DataParser;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class TypeformParser extends Base implements DataParserInterface
{
    public function parseWebhookData(): array
    {
        if ((array_key_exists('form_response', $this->input)) and
            (array_key_exists('definition', $this->input['form_response'])) and
            (array_key_exists('fields', $this->input['form_response']['definition'])) and
            (array_key_exists('answers', $this->input['form_response'])))
        {
            $questions = $this->input['form_response']['definition']['fields'];

            $answers = $this->input['form_response']['answers'];

            $questionsIdAnswers = $this->createQuestionIdAnswers($questions, $answers);

            $questionAnswers = $this->createQuestionAnwsers($questionsIdAnswers);

            return $questionAnswers;
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }
    }

    private function createQuestionIdAnswers(array $questions, array $answers)
    {
        $questionsIdAnswers = [];

        $questionsIdAnswers = $this->addQuestions($questions, $questionsIdAnswers);

        $questionsIdAnswers = $this->addAnswers($answers, $questionsIdAnswers);

        return $questionsIdAnswers;
    }

    private function addQuestions(array $questions, array $questionsIdAnswers): array
    {
        foreach ($questions as $question)
        {
            $referenceId = $question['id'];

            $questionsIdAnswers[$referenceId]['question'] = $question['title'];

            if (array_key_exists('choices', $question))
            {
                $choices = $this->fetchChoices($question['choices']);

                $questionsIdAnswers[$referenceId]['question'] .= $choices;
            }

            //
            // Remove all dots from the questions as elastic search (where typeform is saved currently)
            // accesses the string after dot as objects.
            //
            $question = $questionsIdAnswers[$referenceId]['question'];

            $questionsIdAnswers[$referenceId]['question'] = str_replace(".", "", $question);
        }

        return $questionsIdAnswers;
    }

    private function fetchChoices(array $choices): string
    {
        $allChoices = '';

        foreach ($choices as $choice)
        {
            $allChoices = $allChoices . '\n' . $choice['label'];
        }

        return $allChoices;
    }

    private function addAnswers(array $answers, array $questionsIdAnswers): array
    {
        foreach ($answers as $answer)
        {
            $labels = $this->fetchLabels($answer);

            $refId = $answer['field']['id'];

            $questionsIdAnswers[$refId]['answer'] =
                ($labels !== null) ? $labels : $answer[$answer['type']];
        }

        return $questionsIdAnswers;
    }

    private function fetchLabels(array $answer): ?string
    {
        if (is_array($answer[$answer['type']]))
        {
            {
                if ((array_key_exists('labels', $answer[$answer['type']])))
                {
                    return implode('\n', $answer[$answer['type']]['labels']);
                }
                elseif (array_key_exists('label', $answer[$answer['type']]))
                {
                    return $answer[$answer['type']]['label'];
                }
            }
        }

        return null;
    }

    private function createQuestionAnwsers(array $questionsIdAnswers): array
    {
        $questionsAnswers = [];

        foreach ($questionsIdAnswers as $questionsIdAnswer)
        {
            $questionsAnswers[$questionsIdAnswer['question']] = $questionsIdAnswer['answer'];
        }

        return $questionsAnswers;
    }

    /**
     * Maps a typeform question id to it's question, which is later used in parsing typeform responses
     *
     * @param  array
     * @return array
     * @throws Exception\BadRequestException
     */
    public function typeformQuestionToQuestionId(): array
    {
        if (array_key_exists('fields', $this->input))
        {
            $questionIdToQuestion = [];

            $questions = $this->input['fields'];

            $questionIdToQuestion = $this->createQuestionIdToQuestion($questions, $questionIdToQuestion);

            return $questionIdToQuestion;
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }
    }

    private function createQuestionIdToQuestion(array $questions, array $questionIdToQuestion): array
    {
        foreach ($questions as $question)
        {
            $referenceId = $question['id'];

            // Replace all spaces with underscores in the questions as we create columns with these names in our table in Query book
            // and column names with spaces are not allowed
            $questionIdToQuestion[$referenceId]['question'] = $this->getQuestionTitleAsColumnName($question['title']);
        }

        return $questionIdToQuestion;
    }

    private function getQuestionTitleAsColumnName($questionTitle)
    {
        $question = str_replace(" ", "_", $questionTitle);

        $pattern = "/[@\.\-\;\:\)\(\?\'\’\,\"]+/";

        return preg_replace($pattern, '', $question);
    }

    public function parseTypeformCompleteResponses($formData, $formId)
    {
        if (array_key_exists('items', $this->input) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        try
        {
            $completeResponses = '';

            $responses = $this->input['items'];

            foreach ($responses as $response)
            {
                $metadata = [];
                $metadata['uid']          = $response['hidden']['uid'];
                $metadata['mid']          = $response['hidden']['mid'];
                $metadata['source']       = $response['hidden']['source'];
                $metadata['tracker_id']   = $response['hidden']['tracker_id'];
                $metadata['initiated_at'] = $response['landed_at'];
                $metadata['submitted_at'] = $response['submitted_at'];
                $metadata['survey_id']    = $formId;

                $result = [];

                $result['completed'] = true;

                $result['metadata'] = json_encode($metadata, JSON_FORCE_OBJECT);

                $answers = $response['answers'];
                $questionToAnswer = [];
                foreach ($answers as $answer)
                {
                    $answerType = $answer['type'];

                    if($answerType === 'number')
                    {
                        if(isset($answer['number']))
                        {
                            $questionToAnswer['survey_score'] = $answer['number'];
                        }
                        else
                        {
                            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
                        }
                    }
                    else if($answerType === 'text')
                    {
                        if(isset($answer['text']))
                        {
                            $question = $formData[$answer['field']['id']]['question'];
                            $questionToAnswer[$question] = $answer['text'];
                        }
                        else
                        {
                            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
                        }
                    }
                    else if($answerType === 'choice')
                    {
                        $question = $formData[$answer['field']['id']]['question'];

                        if($answer['choice']['id'] === 'other')
                        {
                            $questionToAnswer[$question] = $answer['choice']['other'];
                        }
                        else
                        {
                            $questionToAnswer[$question] = $answer['choice']['label'];
                        }
                    }
                    else
                    {
                        $question = $formData[$answer['field']['id']]['question'];
                        $questionToAnswer[$question] = $answer['choices']['labels'];
                    }
                }

                $result['response'] = json_encode($questionToAnswer, JSON_FORCE_OBJECT);

                $completeResponses  .= json_encode($result, JSON_FORCE_OBJECT) . ', ' . "\n";
            }

            return $completeResponses;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::TYPEFORM_COMPLETE_RESPONSES_PARSING_ISSUE,
                [
                    'error' => $e,
                ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, $e);
        }
    }

    public function parseTypeformIncompleteResponses($formData, $formId)
    {
        if (array_key_exists('items', $this->input) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        try
        {
            $incompleteResponses = '';

            $responses = $this->input['items'];

            foreach ($responses as $response)
            {
                $result = [];
                $metadata['initiated_at'] = $response['landed_at'];
                $metadata['survey_id']    = $formId;

                $result['completed'] = false;

                $result['metadata'] = json_encode($metadata, JSON_FORCE_OBJECT);

                if((array_key_exists('metadata', $response)) and
                    (array_key_exists('referer', $response['metadata'])))
                {
                    $result['response']['survey_score'] = $this->getSurveyScoreForIncompleteResponses($response['metadata']['referer']);
                }

                $result['response'] = json_encode($result['response'], JSON_FORCE_OBJECT);

                $incompleteResponses .= json_encode($result, JSON_FORCE_OBJECT) . ', ' . "\n";
            }

            return $incompleteResponses;
        }
        catch(\Throwable $e)
        {
            $this->trace->info(
                TraceCode::TYPEFORM_INCOMPLETE_RESPONSES_PARSING_ISSUE,
                [
                    'error' => $e,
                ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, $e);
        }
    }

    private function getSurveyScoreForIncompleteResponses(string $referer)
    {
        $queryString = 'prefilled_answer';

        $startIndex = strpos($referer, $queryString);

        if($startIndex != false)
        {
            $offset = strlen($queryString);

            $option1 = substr($referer,$startIndex+ $offset + 1, 2);

            $option2 = substr($referer,$startIndex + $offset + 1, 1);

            return $option1 == "10" ? $option1 : $option2;
        }

        return null;
    }
}
