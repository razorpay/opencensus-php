<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Tests\Unit\Trace\Integrations;

use OpenCensus\Trace\Span;
use OpenCensus\Trace\Integrations\PDO;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class PDOTest extends TestCase
{
    public function testHandleQuery()
    {
        $scope = null;
        $query = 'select * from users';
        PDO::setDsn('mysql:host=localhost;dbname=testdb');
        $spanOptions = PDO::handleQuery($scope, $query);
        $expected = [
            'attributes' => [
                'db.statement' => 'select * from users',
                'span.kind'    => strtolower(Span::KIND_CLIENT),
                'db.system' => 'mysql',
                'net.peer.name' => 'localhost:3306'
            ],
            'kind' => strtolower(Span::KIND_CLIENT),
            'sameProcessAsParentSpan' => false
        ];

        $this->assertEquals($expected, $spanOptions);
    }

    public function testHandleConnect()
    {
        $dsn = 'mysql:host=localhost;dbname=testdb';
        $spanOptions = PDO::handleConnect(null, $dsn);
        $expected = [
            'attributes' => [
                'dsn'       => 'mysql:host=localhost;dbname=testdb',
                'db.type'   => 'sql',
                'db.system' => 'mysql',
                'db.name'   => 'testdb',
                'net.peer.name' => 'localhost:3306',
                'net.peer.port' => '3306',
                'db.connection_string' => 'mysql:host=localhost;dbname=testdb',
                'span.kind' => strtolower(Span::KIND_CLIENT)
            ],
            'kind' => strtolower(Span::KIND_CLIENT),
            'sameProcessAsParentSpan' => false,
            'name' => 'PDO connect'
        ];

        $this->assertEquals($expected, $spanOptions);
    }
}
