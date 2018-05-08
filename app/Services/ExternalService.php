<?php

namespace RZP\Services;

interface ExternalService
{
    public function fetchMultiple(string $entity, array $input);

    public function fetch(string $entity, string $id, array $input);
}
