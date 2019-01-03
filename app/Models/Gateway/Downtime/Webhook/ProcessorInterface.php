<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

interface ProcessorInterface
{
	public function validate(array $input);

    public function process(array $input);
}
