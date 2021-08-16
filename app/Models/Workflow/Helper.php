<?php

namespace RZP\Models\Workflow;

use RZP\Encryption\AesCrypt;
use Illuminate\Support\Facades\Crypt;
use RZP\Models\Workflow\Action\Constants;

class Helper
{
    public function encryptSensitiveFields(array &$input)
    {
        foreach (Constants::KEYS_TO_ENCRYPT_BEFORE_SAVING_IN_ES as $fieldToEncrypt)
        {
            if(array_key_exists($fieldToEncrypt, $input) === true)
            {
                $encryptedData = Crypt::encrypt( $input[$fieldToEncrypt] );

                $input[$fieldToEncrypt] = $encryptedData;
            }
        }

        return $input;
    }

    public function redactFields(array $input)
    {
        if (array_key_exists('new', $input))
        {
            $input['new'] = $this->encryptSensitiveValuesInArray($input['new']);
        }

        if (array_key_exists('old', $input))
        {
            $input['old'] = $this->encryptSensitiveValuesInArray($input['old']);
        }

        return $input;
    }

    public function decryptSensitiveFieldsBeforeReplayingRequest(array $input)
    {
        foreach (Constants::KEYS_TO_ENCRYPT_BEFORE_SAVING_IN_ES as $fieldToEncrypt)
        {
            if (array_key_exists($fieldToEncrypt, $input) === true)
            {
                $input[$fieldToEncrypt] = Crypt::decrypt($input[$fieldToEncrypt]);
            }
        }

        return $input;
    }

    protected function encryptSensitiveValuesInArray(array $input): array
    {
        foreach (Constants::KEYS_TO_ENCRYPT_BEFORE_SAVING_IN_ES as $fieldToEncrypt)
        {
            if (array_key_exists($fieldToEncrypt, $input) === true)
            {
                $input[$fieldToEncrypt] = str_repeat("*", strlen($input[$fieldToEncrypt]));
            }
        }

        return $input;
    }
}
