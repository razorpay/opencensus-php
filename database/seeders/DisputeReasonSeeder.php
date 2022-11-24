<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;
use RZP\Constants\Table;
use RZP\Models\Dispute\Reason\Network;

class DisputeReasonSeeder extends Seeder
{
    // Here we enter values for `network`, `gateway_code`, `gateway_description`, `code` and `description` in this order.
    // `network` must come from the list of possible networks, and
    // `code` must be all lower and snake case.
    protected static $reasons = [
        [
            Network::JCB,
            'UA05',
            'Fraud-Chip Counterfeit Transaction',
            'counterfeit_transaction',
            'The cardholder did not participate in the transaction, a fraudster made counterfeit copy of the card.'
        ],
        [
            Network::AMEX,
            'M01',
            'Chargeback Authorization',
            'chargeback_authorization',
            'American Express received merchant authorization to process a chargeback for the charge.'
        ],
        [
            Network::JCB,
            'C02',
            'Credit Not Processed',
            'credit_not_processed',
            'The cardholder claims he is due a credit from an establishment that has not been processed.'
        ],
        [
            Network::VISA,
            '85',
            'Credit Not Processed',
            'credit_not_processed',
            'The cardholder claims he is due a credit from an establishment that has not been processed.'
        ],
        [
            Network::AMEX,
            'CD',
            'Credit or Debit Posted Incorrectly',
            'credit_or_debit_posted_incorrectly',
            'The cardholder challenges the validity of a card transaction because the transaction should
                have resulted in a credit rather than a card sale or the transaction should have resulted
                in a card sale rather than a credit.'
        ],
        [
            Network::JCB,
            'AA',
            'Does Not Recognize',
            'card_holder_not_recognised',
            'The cardholder claims that their account was charged or credited for a card transaction (other
                than an ATM transaction) that they don\'t recognize.'
        ]
    ];

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        //Eloquent::unguard();

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
                    'network'             => $reason[0],
                    'gateway_code'        => $reason[1],
                    'gateway_description' => $reason[2],
                    'code'                => $reason[3],
                    'description'         => $reason[4],
                    'created_at'          => time(),
                    'updated_at'          => time(),
                ]);
            }
            // end of transaction
        });
    }
}
