<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class MerchantDocument extends Base
{
    public function create(array $attributes = array())
    {
        $merchantDocument = $this->createEntityInTestAndLive('merchant_document', $attributes);

        return $merchantDocument;
    }

    public function createMultiple(array $data)
    {
        $documents = [];

        $documentTypes = $data['document_types'];

        $attributes    = $data['attributes'];

        foreach ($documentTypes as $documentType)
        {
            $attributes['document_type'] = $documentType;

            $documents[] = $this->create($attributes);
        }

        return $documents;
    }
}
