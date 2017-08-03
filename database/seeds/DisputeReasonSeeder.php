<?php

use Illuminate\Database\Seeder;

use RZP\Constants\Table;

class DisputeReasonSeeder extends Seeder
{
    protected static $reasons = [
        [
            'UA05',
            'Fraud-Chip Counterfeit Transaction',
            'COUNTERFEIT_TRANSACTION',
            'The cardholder did not participate in the transaction, a fraudster made counterfeit copy of the card.'
        ],
        [
            'M01',
            'Chargeback Authorization',
            'chargeback_authorization',
            'American Express received merchant authorization to process a chargeback for the charge.'
        ],
        [
            'C02',
            'Credit Not Processed',
            'credit_not_processed',
            'The cardholder claims he is due a credit from an establishment that has not been processed.'
        ],
        [
            '85',
            'Credit Not Processed',
            'credit_not_processed',
            'The cardholder claims he is due a credit from an establishment that has not been processed.'
        ],
        [
            'CD',
            'Credit or Debit Posted Incorrectly',
            'credit_or_debit_posted_incorrectly',
            'The cardholder challenges the validity of a card transaction because the transaction should
                have resulted in a credit rather than a card sale or the transaction should have resulted
                in a card sale rather than a credit.'
        ],
        [
            'AA',
            'Does Not Recognize',
            'card_holder_not_recognised',
            'The cardholder claims that their account was charged or credited for a card transaction (other
                than an ATM transaction) that they don\'t recognize.'
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        DB::table(Table::DISPUTE_REASON)->delete();

        $this->seed();
    }

    private function seed()
    {
        $reasons = self::$reasons;

        DB::transaction(function() use ($reasons)
        {
            foreach ($reasons as $reason)
            {
                $id = str_random(14);

                DB::table(Table::DISPUTE_REASON)->insert([
                    'id'                  => str_random(14),
                    'gateway_code'        => $reason[0],
                    'gateway_description' => $reason[1],
                    'code'                => $reason[2],
                    'description'         => $reason[3],
                    'created_at'          => time(),
                    'updated_at'          => time(),
                ]);
            }
            // end of transaction
        });
    }
}
