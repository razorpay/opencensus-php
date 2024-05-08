<?php

namespace RZP\Tests\Unit\Batch;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch;


class BatchValidatorTest extends TestCase
{

    protected ?Batch\Validator $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Batch\Validator();
    }

    public function testCreateForInValidFileName()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $input = [
            'filename'          => '123456789235𤨒',
            'batch_type_id'     => 'tally_payout'
        ];
        $this->validator->validateInput('validate_file_name', $input);
    }

    public function testCreateForValidInput()
    {
        $this->expectNotToPerformAssertions();
        $input = [
            'filename'          => '12345678901234',
            'batch_type_id'     => 'tally_payout'
        ];
        $this->validator->validateInput('validate_file_name', $input);
    }
}
