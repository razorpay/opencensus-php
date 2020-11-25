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

namespace OpenCensus\Trace\Integrations;

use OpenCensus\Trace\Span;

/**
 * This class handles instrumenting PDO requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\PDO;
 *
 * PDO::load();
 * ```
 */
class PDO implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to the PDO requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load PDO integrations.', E_USER_WARNING);
            return;
        }

        // public int PDO::exec(string $query)
        opencensus_trace_method('PDO', 'exec', [static::class, 'handleQuery']);

        // public PDOStatement PDO::query(string $query)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_COLUMN, int $colno)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_CLASS, string $classname, array $ctorargs)
        // public PDOStatement PDO::query(string $query, int PDO::FETCH_INFO, object $object)
        opencensus_trace_method('PDO', 'query', [static::class, 'handleQuery']);

        // public bool PDO::commit ( void )
        opencensus_trace_method('PDO', 'commit');

        // public PDO::__construct(string $dsn [, string $username [, string $password [, array $options]]])
        opencensus_trace_method('PDO', '__construct', [static::class, 'handleConnect']);

        // public bool PDOStatement::execute([array $params])
        opencensus_trace_method('PDOStatement', 'execute', [static::class, 'handleStatementExecute']);
    }

    /**
     * Handle extracting the SQL query from the first argument
     *
     * @internal
     * @param PDO $pdo The connectoin
     * @param string $query The SQL query to extract
     * @return array
     */
    public static function handleQuery($pdo, $query)
    {
        return [
            'attributes' => ['db.statement' => $query, 'span.kind' => Span::KIND_CLIENT],
            'kind' => Span::KIND_CLIENT
        ];
    }

    /**
     * Handle extracting the Data Source Name (DSN) from the constructor aruments to PDO
     *
     * @internal
     * @param PDO $pdo
     * @param string $dsn The connection DSN
     * @return array
     */
    public static function handleConnect($pdo, $dsn)
    {
        $attributes = ['dsn' => $dsn, 'db.type' => 'sql', 'span.kind' => Span::KIND_CLIENT];

        return [ 'attributes' => $attributes,
            'kind' => Span::KIND_CLIENT,
        ];
    }

    /**
     * Handle extracting the SQL query from a PDOStatement instance
     *
     * @internal
     * @param PDOStatement $statement The prepared statement
     * @return array
     */
    public static function handleStatementExecute($statement)
    {
        /*
            refer following for SQL return codes
            https://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm
        */

        $rowCount = $statement->rowCount();
        $errorCode = $statement->errorCode();
        $error =  substr($errorCode, 0, 2);
        $errorTags = [];

        switch ($error) {
            case (string) '01':
                $errorTags = ['warning' => 'true', 'warning.code' => $errorCode];
                break;
        };

        $errorCodeMsgArray = [
            "02" => "No Data",
            "07" => "Dynamic SQL error",
            "08" => "Connection Exception",
            "0A" => "Feature not supported",
            "21" => "Cardinality violation",
            "22" => "Data exception",
            "23" => "Integrity constraint violation",
            "24" => "Invalid Cursor State",
            "25" => "Invalid Transaction state",
            "26" => "Invalid SQL Statement Name",
            "27" => "Triggered Data Change Violation",
            "28" => "Invalid Authorization Specification",
            "2A" => "Syntax Error or Access Rule Violation in Direct SQL Statement",
            "2B" => "Dependent Privilege Descriptors Still Exist",
            "2C" => "Invalid Character Set Name",
            "2D" => "Invalid Transaction Termination",
            "2E" => "Invalid Connection Name",
            "33" => "Invalid SQL Descriptor Name",
            "34" => "Invalid Cursor Name",
            "35" => "Invalid Condition Number",
            "37" => "Syntax Error or Access Rule Violation in Dynamic SQL Statement",
            "3C" => "Ambigous Cursor Name",
            "3F" => "No Data",
            "40" => "Transition Rollback",
            "42" => "Syntax Error or Access Rule Violation",
            "44" => "With Check Option Violation"
        ];

        if (array_key_exists($error, $errorCodeMsgArray)) {
            $errorTags['error'] = 'true';
            $errorTags['error.code'] = $errorCode;
            $errorTags['error.message'] = $errorCodeMsgArray[$error] ?? '';
        }

        $tags = [
            'db.statement' => $statement->queryString,
            'db.row_count' => $rowCount,
            'span.kind' => Span::KIND_CLIENT
        ];

        return [
            'attributes' => $tags + $errorTags,
            'kind' => Span::KIND_CLIENT
        ];
    }
}
