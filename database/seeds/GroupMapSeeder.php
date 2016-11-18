<?php

use RZP\Constants\Table;
use Illuminate\Database\Seeder;

class GroupMapSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        $this->seed();
    }

    private function seed()
    {
        $name = DB::connection()->getName();

        DB::transaction(function() use ($name)
        {
            $id1 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id1,
                    'name'          =>  'Some name one',
                    'description'   =>  'Some description',
                    'org_id'        =>  '6dLbNSpv5XbCOG',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    'deleted_at'    =>  null,
                    )
                );

            $id2 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id2,
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
                    'group_id'     =>  $id1,
                    'entity_id'    =>  $id2,
                    'entity_type'  =>  'group'
                    )
                );

            $id3 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id3,
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
                    'group_id'     =>  $id2,
                    'entity_id'    =>  $id3,
                    'entity_type'  =>  'group'
                    )
                );

            $id4 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id4,
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
                    'group_id'     =>  $id3,
                    'entity_id'    =>  $id4,
                    'entity_type'  =>  'group'
                    )
                );

            $id5 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id5,
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
                    'group_id'     =>  $id4,
                    'entity_id'    =>  $id5,
                    'entity_type'  =>  'group'
                    )
                );

            $id6 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id6,
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
                    'group_id'     =>  $id6,
                    'entity_id'    =>  $id3,
                    'entity_type'  =>  'group'
                    )
                );

            $id7 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id7,
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
                    'group_id'     =>  $id6,
                    'entity_id'    =>  $id7,
                    'entity_type'  =>  'group'
                    )
                );

            $id8 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id8,
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
                    'group_id'     =>  $id6,
                    'entity_id'    =>  $id8,
                    'entity_type'  =>  'group'
                    )
                );

            $id9 = str_random(14);
            DB::table(Table::GROUP)->insert(
                array(
                    'id'            =>  $id9,
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
                    'group_id'     =>  $id9,
                    'entity_id'    =>  $id6,
                    'entity_type'  =>  'group'
                    )
                );
            
        });
    }
}
