<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use RZP\Constants\Table;
use Illuminate\Database\Seeder;

class GroupMapSeeder extends Seeder
{

    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run()
    {
        //Eloquent::unguard();

        $this->seed();
    }

    private function seed()
    {
        $name = DB::connection()->getName();

        DB::transaction(function() use ($name)
        {
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'hEJJyhYr35v71S',
                    'name'          =>  'Some name one',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'nh9Ixjoj22MdRk',
                    'name'          =>  'Some name two',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'hEJJyhYr35v71S',
                    'entity_id'    =>  'nh9Ixjoj22MdRk',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'JpSuVlMR67O1J7',
                    'name'          =>  'Some name three',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'nh9Ixjoj22MdRk',
                    'entity_id'    =>  'JpSuVlMR67O1J7',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'TDjd5yjJ6oL8qe',
                    'name'          =>  'Some name four',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'JpSuVlMR67O1J7',
                    'entity_id'    =>  'TDjd5yjJ6oL8qe',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'HEMH803EQRhLGK',
                    'name'          =>  'Some name five',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'TDjd5yjJ6oL8qe',
                    'entity_id'    =>  'HEMH803EQRhLGK',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'DQUM0vXtlSBaJp',
                    'name'          =>  'Some name six',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'DQUM0vXtlSBaJp',
                    'entity_id'    =>  'JpSuVlMR67O1J7',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'ZKXuBlfuwy3LpA',
                    'name'          =>  'Some name seven',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'DQUM0vXtlSBaJp',
                    'entity_id'    =>  'ZKXuBlfuwy3LpA',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'HAg9V9NnyLMepb',
                    'name'          =>  'Some name eight',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'DQUM0vXtlSBaJp',
                    'entity_id'    =>  'HAg9V9NnyLMepb',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'df3AhChTlBtDEq',
                    'name'          =>  'Some name nine',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'df3AhChTlBtDEq',
                    'entity_id'    =>  'DQUM0vXtlSBaJp',
                    'entity_type'  =>  'group'
                    )
                );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'E15BhsdMSofcUJ',
                    'name'          =>  'SF_UNCLAIMED_GROUP_ID',
                    'description'   =>  'Salesforce Unclaimed Group',
                    'org_id'        =>  '100000razorpay',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                )
            );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'E15FKNaXgALD6Y',
                    'name'          =>  'SF_CLAIMED_MERCHANTS_GROUP_ID',
                    'description'   =>  'Salesforce Claimed Merchants Group',
                    'org_id'        =>  '100000razorpay',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                )
            );

            DB::table(Table::GROUP_MAP)->insert(
                array(
                    'group_id'     =>  'E15FKNaXgALD6Y',
                    'entity_id'    =>  'E34ct85lm9wLEz',
                    'entity_type'  =>  'group'
                )
            );

            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  'E34ct85lm9wLEz',
                    'name'          =>  'SF_CLAIMED_SME_GROUP_ID',
                    'description'   =>  'Salesforce Claimed SME Group',
                    'org_id'        =>  '100000razorpay',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                )
            );

        });
    }
}
