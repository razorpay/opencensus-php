<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response;
use RZP\Services\SplitzService as BaseSplitz;


class SplitzService extends BaseSplitz
{

    public function createSegment($preSignedUrl, $segmentName, $s3Path)
    {
        $resp = new \WpOrg\Requests\Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode([
            "id" => "HNLHhViw9KFPnT",
            "name" => "growth_segment_qr_code_v1",
            "description" => "growth_segment_qr_code_av1 bet",
            "falsePositivityRate" => "0.000001",
            "entries" => "1000",
            "inputFileID" => "file_HNLHfH1LKmaxFx",
            "outputFileID" => "file_HNLHhnPKAJRE61",
            "created_at" => "2021-06-15T12=>54=>13Z"
        ]);
        return $resp;
    }

    public function updateSegment($preSignedUrl, $segmentName, $id, $s3Path)
    {
        $resp = new \WpOrg\Requests\Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode([
            "id" => "HNLHhViw9KFPnT",
            "name" => "growth_segment_qr_code_v1",
            "description" => "growth_segment_qr_code_av1 bet",
            "falsePositivityRate" => "0.000001",
            "entries" => "1000",
            "inputFileID" => "file_HNLHfH1LKmaxFx",
            "outputFileID" => "file_HNLHhnPKAJRE61",
            "created_at" => "2021-06-15T12=>54=>13Z"
        ]);
        return $resp;
    }

    public function evaluateRequest($input)
    {
        $code = 200;
        $body = json_encode([
            "id" => "A",
            "project_id" => "HHhdsBjOfdFmSR",
            "experiment" => [
                "id" => "HHhiZYiI79mJbj",
                "name" => "splitz.feature.reporting.experiment",
                "environment_id" => "HHhdspxWzjWktQ"
            ],
            "variant" => [
                "id" => "HHhiZaJRJbcT9Y",
                "name" => "buy_button_2",
                "variables" => [[
                    "key" => "button_color",
                    "value" => "purple"
                ]],
                "experiment_id" => "HHhiZYiI79mJbj"
            ],
            "Reason" => "bucketer",
            "steps" => ["sampler", "exclusion", "audience", "assign_bucket"]
        ]);
        $res = json_decode($body, true);
        return ['status_code' => $code, 'response' => $res];
    }

    public function getSegmentFromName($segmentName)
    {
        $resp = new \WpOrg\Requests\Response;
        $resp->success     = true;
        $resp->status_code = 200;
        $resp->body        = json_encode([
            "id" => "HNLHhViw9KFPnT",
            "name" => "growth_segment_qr_code_v1",
            "description" => "growth_segment_qr_code_av1 bet",
            "falsePositivityRate" => "0.000001",
            "entries" => "1000",
            "inputFileID" => "file_HNLHfH1LKmaxFx",
            "outputFileID" => "file_HNLHhnPKAJRE61",
            "created_at" => "2021-06-15T12=>54=>13Z"
        ]);
        return $resp;
    }

    public function bulkCallsToSplitz($input)
    {
        return [
            [
                "id" => "A",
                "project_id" => "HHhdsBjOfdFmSR",
                "experiment" => [
                    "id" => "HHhiZYiI79mJbj",
                    "name" => "splitz.feature.reporting.experiment",
                    "environment_id" => "HHhdspxWzjWktQ"
                ],
                "variant" => [
                    "id" => "HHhiZaJRJbcT9Y",
                    "name" => "buy_button_2",
                    "variables" => [[
                        "key" => "button_color",
                        "value" => "purple"
                    ]],
                    "experiment_id" => "HHhiZYiI79mJbj"
                ],
                "Reason" => "bucketer",
                "steps" => ["sampler", "exclusion", "audience", "assign_bucket"]
            ],
            [
                "id" => "B",
                "project_id" => "HHhdsBjOfdFmSR",
                "experiment" => [
                    "id" => "HHhiZYiI79mJbj",
                    "name" => "splitz.feature.reporting.experiment",
                    "environment_id" => "HHhdspxWzjWktQ"
                ],
                "variant" => [
                    "id" => "HHhiZaJRJbcT9Y",
                    "name" => "buy_button_2",
                    "variables" => [[
                        "key" => "button_color",
                        "value" => "purple"
                    ]],
                    "experiment_id" => "HHhiZYiI79mJbj"
                ],
                "Reason" => "bucketer",
                "steps" => ["sampler", "exclusion", "audience", "assign_bucket"]
            ]
        ];

    }

    public function allowCors()
    {
        $response = ApiResponse::json([]);

        $response->headers->set(self::ACCESS_CONTROL_ALLOW_METHODS, 'POST, OPTIONS' );

        $response->headers->set(self::ACCESS_CONTROL_ALLOW_HEADERS, self::CONTENT_TYPE);

        return $response;
    }
}
