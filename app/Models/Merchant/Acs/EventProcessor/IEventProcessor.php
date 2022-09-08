<?php


namespace RZP\Models\Merchant\Acs\EventProcessor;


interface IEventProcessor
{
    function ShouldProcess(array $input): bool;

    function Process(array $input);
}
