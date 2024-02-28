<?php

namespace RZP\Tests\Functional\Contacts;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Contact\Core;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Workflow\Action\Comment;


class ActionCommentsTest extends TestCase
{

    protected ?Comment\Validator $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Comment\Validator();
    }

    public function testCreateForInvalidComments()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $input = [
            'action_id' => '12345678901234',
            'admin_id'  => '12345678901235',
            'comment'   => 'hello𤨒'
        ];
        $this->validator->validateInput('create', $input);
    }

    public function testCreateForInValidActionId()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $input = [
            'action_id' => '1234567890124𤨒',
            'admin_id'  => '12345678901235',
            'comment'   => 'hello'
        ];
        $this->validator->validateInput('create', $input);
    }

    public function testCreateForInValidAdminId()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $input = [
            'action_id' => '123458901234',
            'admin_id'  => '123456789235𤨒',
            'comment'   => 'hello'
        ];
        $this->validator->validateInput('create', $input);
    }

    public function testCreateForValidInput()
    {
        $this->expectNotToPerformAssertions();
        $input = [
            'action_id' => '12345678901234',
            'admin_id'  => '12345678901235',
            'comment'   => 'hello'
        ];
        $this->validator->validateInput('create', $input);
    }
}
