<?php

namespace RZP\Services\Edge;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use ApiResponse;

trait TraceError
{
    private function failedSetCredentials($res)
    {
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_SET_CREDENTIALS_FAILED);
        return $res;
    }

    private function failedSetAdminAuth($res)
    {
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_SET_ADMIN_AUTH_FAILED);
        return $res;
    }

    private function failedSetAccountScope($res)
    {
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_SET_ACCOUNT_SCOPE_FAILED);
        return $res;
    }

    private function failedKeyNotBlank($res = null)
    {
        $res = $res ?? ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_KEY_NOT_BLANK);
        return $res;
    }

    private function failedVerifyApp($res = null)
    {
        $res = $res ?? ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);
        return $res;
    }

    private function failedUnreachable($res = null)
    {
        $res = $res ?? ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_VERIFY_APP_FAILED);
        return $res;
    }

    private function failedInvalidAuth($res = null)
    {
        $res = $res ?? ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ERROR);
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_INVALID_AUTH);
        return $res;
    }

    private function failedInternalAuthNotSupported($res = null)
    {
        $res = $res ?? ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ERROR);
        $this->trace->error(TraceCode::EDGE_THIRD_PARTY_INTERNAL_AUTH_NOT_SUPPORTED);
        return $res;
    }
}
