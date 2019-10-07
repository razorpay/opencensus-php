<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever;

interface DataRetriever
{
    public function fetchData(array $input): array;
}