<?php

namespace RZP\lib\DataParser;

use RZP\Exception;
use RZP\Error\ErrorCode;

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


}
