<?php

namespace RZP\Models\Merchant\Fraud\WebsiteChecker;

use Throwable;
use RZP\Models\Base;
use RZP\Http\Request\Requests;

class Service extends Base\Service
{
    public function isLive(array $input): array
    {
        $url = $input['url'];

        $comment = null;
        $result = null;

        try
        {
            $response = Requests::request($url);
            $comment = sprintf(Constants::NO_EXCEPTION_COMMENT_FORMAT, $response->status_code);
            $result = Constants::STATUS_CODE_RESULT_MAP[$response->status_code] ?? Constants::RESULT_MANUAL_REVIEW;
        }
        catch (Throwable $e)
        {
            $comment = sprintf(Constants::EXCEPTION_COMMENT_FORMAT, $e->getMessage());
            $result = Constants::RESULT_MANUAL_REVIEW;
        }

        return [
            'url'     => $url,
            'result'  => $result,
            'comment' => $comment,
        ];
    }

    public function periodicCron(): array
    {
        return $this->core()->periodicCron();
    }

    public function retryCron(): array
    {
        return $this->core()->retryCron();
    }

    public function reminderCron(): array
    {
        return $this->core()->reminderCron();
    }
}
