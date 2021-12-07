<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class KeyController extends Controller
{
    public function postCreateKeys()
    {
        $data = $this->service()->createKey();

        return ApiResponse::json($data);
    }

    public function postCreateKeysWithOtp()
    {
        $input = Request::all();
        $data = $this->service()->createKeyWithOtp($input);

        return ApiResponse::json($data);
    }

    public function getKeys()
    {
        $data = $this->service()->fetchKeys();

        return ApiResponse::json($data);
    }

    public function putKeys($keyId)
    {
        $input = Request::all();

        $keys = $this->service()->updateKey($keyId, $input);

        return ApiResponse::json($keys);
    }

    public function putKeysWithOtp($keyId)
    {
        $input = Request::all();

        $keys = $this->service()->updateKeyWithOtp($keyId, $input);

        return ApiResponse::json($keys);
    }

    /**
     * @see Key\Service's migrateToCredcase function.
     * @return void
     */
    public function migrateToCredcase()
    {
        $summary = $this->service()->migrateToCredcase($this->input);

        return ApiResponse::json($summary);
    }

    public function bulkRegenerateApiKey()
    {
        $input = Request::all();

        $keys = $this->service()->bulkRegenerateApiKey($input);

        return ApiResponse::json($keys);
    }
}
