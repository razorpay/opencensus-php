<?php

namespace RZP\Models\Merchant\AutoKyc;

interface ProcessorFactory
{
    public static function getPOIProcessor(array $input): Processor;

    public static function getRegisterProcessor(array $input): ?Processor;

    public static function getCompanyPanProcessor(array $input): ?Processor;

    public static function getPOAProcessor(array $input): Processor;

    public static function getGSTINProcessor(array $input): ?Processor;

    public static function getCINProcessor(array $input): ?Processor;
}
