<?php

namespace Unit\Listeners;

use Tests\Unit\TestCase;
use RZP\Listeners\DatabaseEventListener;

class DatabaseEventListenerTest extends TestCase
{

    public function testExtractTableNamesAndOperationsFromSQL() {
        $classInstance = new DatabaseEventListener();

        // Define test cases in a tabular format
        $testCases = [
            [
                "sql" => "SELECT * FROM orders",
                "expectedTables" => ['orders'],
                "expectedOperations" => [['orders' => 'SELECT']]
            ],
            [
                "sql" => "SELECT * FROM `orders`",
                "expectedTables" => ['orders'],
                "expectedOperations" => [['orders' => 'SELECT']]
            ],
            [
                "sql" => "SELECT `id` FROM `merchants` WHERE `parent_id` = ?",
                "expectedTables" => ['merchants'],
                "expectedOperations" => [['merchants' => 'SELECT']]
            ],
            [
                "sql" => "SELECT `id` FROM merchants WHERE `parent_id` = ?",
                "expectedTables" => ['merchants'],
                "expectedOperations" => [['merchants' => 'SELECT']]
            ],
            [
                "sql" => "SELECT * FROM api.`orders` o JOIN `customers` c ON o.customer_id = c.id",
                "expectedTables" => ['orders', 'customers'],
                "expectedOperations" => [['orders' => 'SELECT'], ['customers' => 'JOIN']]
            ],
            [
                "sql" => "SELECT * FROM `orders` o JOIN `customers` c ON o.customer_id = c.id JOIN `payments` p ON o.payment_id = p.id",
                "expectedTables" => ['orders', 'customers', 'payments'],
                "expectedOperations" => [['orders' => 'SELECT'], ['customers' => 'JOIN'], ['payments' => 'JOIN']]
            ],
            [
                "sql" => "UPDATE `orders` SET `status` = 'shipped' WHERE `id` = 1",
                "expectedTables" => ['orders'],
                "expectedOperations" => [['orders' => 'UPDATE']]
            ],
            [
                "sql" => "INSERT INTO `orders` (`id`, `status`) VALUES (1, 'shipped')",
                "expectedTables" => ['orders'],
                "expectedOperations" => [['orders' => 'INSERT']]
            ],
            [
                "sql" => "DELETE FROM `orders` WHERE `id` = 1",
                "expectedTables" => ['orders'],
                "expectedOperations" => [['orders' => 'DELETE']]
            ]
        ];

        // Loop through the test cases
        foreach ($testCases as $testCase) {
            $result = $classInstance->extractTableNamesAndOperationsFromSQL($testCase["sql"]);

            // Compare expected tables with the result
            $this->assertEquals(
                $testCase["expectedTables"],
                $result['tables'],
                "Test Failed for SQL: " . $testCase["sql"] .
                "\nExpected Tables: " . json_encode($testCase["expectedTables"]) .
                "\nActual Tables: " . json_encode($result['tables'])
            );

            // Compare expected table operations with the result
            $this->assertEquals(
                $testCase["expectedOperations"],
                $result['table_operations'],
                "Test Failed for SQL: " . $testCase["sql"] .
                "\nExpected Operations: " . json_encode($testCase["expectedOperations"]) .
                "\nActual Operations: " . json_encode($result['table_operations'])
            );
        }
    }


}
