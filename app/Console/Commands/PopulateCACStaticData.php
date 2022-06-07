<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Models\AccessControlPrivileges\Service;
use RZP\Models\AccessPolicyAuthzRolesMap;
use RZP\Models\AccessControlPrivileges\Repository;


class PopulateCACStaticData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:cac_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate cac static data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    protected $idToNameMap = [];


    public function handle()
    {
        $this->populatePrivilegeData();
        $this->populateAuthzRolesMap();
    }

    private function populateAuthzRolesMap()
    {
        $filePath = dirname(__FILE__) .
            '/../../../storage/files/privileges/authzRolesMap.csv';
        $file = fopen($filePath, 'r');
        $first = true;

        //delete all the existing data
        (new AccessPolicyAuthzRolesMap\Repository())->deleteAll();

        while (! feof($file)) {
            $rowData = fgetcsv($file);
            if ($first) {
                $first = false;
                continue;
            }

            $entityData = [];

            $mappingId = $rowData[1];
            $privilegeName = $this->idToNameMap[$mappingId];
            $privilegeEntityObject = (new Repository())->findByName($privilegeName);
            $entityData['privilege_id'] = $privilegeEntityObject->getId();

            $entityData['action'] = $rowData[2];
            $entityData['authz_roles'] = !empty(json_decode($rowData[3], true)) ? json_decode($rowData[3], true) : [];
            $entityData['meta_data'] = json_decode($rowData[4], true);
            (new AccessPolicyAuthzRolesMap\Service())->createMap($entityData);
        }
        fclose($file);
    }

    private function populatePrivilegeData()
    {
        $filePath = dirname(__FILE__) .
            '/../../../storage/files/privileges/privileges.csv';
        $file = fopen($filePath, 'r');

        $first = true;

        //delete all the existing data
        (new Repository())->deleteAll();

        while (! feof($file)) {
            $rowData = fgetcsv($file);
            if ($first) {
                $first = false;
                continue;
            }

            $entityData = [];
            $entityData['name'] = $rowData[1];
            $entityData['label'] = $rowData[2];
            $this->idToNameMap[$rowData[0]] = $rowData[1];

            if (!empty($rowData[3])) {
                $entityData['description'] = $rowData[3];
            }

            if (!empty($rowData[4]) and $rowData[4] != 'null') {
                // get privilege object of parent through parent name
                $privilegeEntityObject = (new Repository())->findByName($this->idToNameMap[$rowData[4]]);
                $entityData['parent_id'] = $privilegeEntityObject->getId();
            }

            $entityData['visibility'] = $rowData[5];

            (new Service())->createPrivilege($entityData);
        }

        fclose($file);
    }

}
