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
        foreach (Constants::KEYS_TO_ENCRYPT_BEFORE_SAVING_IN_ES as $fieldToEncrypt)
        {
            if (array_key_exists($fieldToEncrypt, $input['new']) === true)
            {
                $input['new'][$fieldToEncrypt] = str_repeat("*", strlen($input['new'][$fieldToEncrypt]));
            }

            if (array_key_exists($fieldToEncrypt, $input['old']) === true) {

                $input['old'][$fieldToEncrypt] = str_repeat("*", strlen($input['old'][$fieldToEncrypt]));
            }
        }

        return $input;
    }

    public function decryptSensitiveFieldsBeforeReplayingRequest(array $input)
    {
        foreach (Constants::KEYS_TO_ENCRYPT_BEFORE_SAVING_IN_ES as $fieldToEncrypt)
        {
            if(array_key_exists($fieldToEncrypt, $input) === true)
            {
                $input[$fieldToEncrypt] = Crypt::decrypt($input[$fieldToEncrypt]);
            }
        }

        return $input;
    }
}
