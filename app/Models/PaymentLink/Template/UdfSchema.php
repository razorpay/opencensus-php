<?php

namespace RZP\Models\PaymentLink\Template;

use JsonSchema;
use RZP\Exception\BadRequestValidationFailureException;

class UdfSchema
{
    public $schema = [];

    public function getJSONSchema(string $id, string $name = null)
    {
        $json = (new FileAccess(FileAccess::UDF_SCHEMA, $id, $name));

        if ($json->exists() === false)
        {
            return;
        }

        $this->schema = json_decode($json->get(), true);

        return $this->schema;
    }

    public function validate(array $input = [])
    {
        $data = (object) $input;

        $validator = new JsonSchema\Validator;
        $validator->validate($data, $this->schema);

        if ($validator->isValid() === false)
        {
            $error    = head($validator->getErrors());
            $property = $error['property'];
            $message  = "The {$property} field is invalid. {$error['message']}";

            throw new BadRequestValidationFailureException($message);
        }
    }
}
