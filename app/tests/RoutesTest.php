<?php

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase transactions are used, also tests if transactions are automatically
 * captured on successful transactions. Hold Transactions are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class RoutesTest extends TestCase {
    public function testJSONPRoute()
    {
        //Should return 401
        $response = $this->action('GET', 'TransactionController@getJSONP');
        $this->assertTrue($response->getStatusCode() == 401);
    }
}
