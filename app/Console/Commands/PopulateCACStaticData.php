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
        $this->createStandardRoles();
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

    private function createStandardRoles()
    {
        //delete role data entries
        (new \RZP\Models\Roles\Repository())->deleteAll();
        // delete role to access policy entries
        (new \RZP\Models\RoleAccessPolicyMap\Repository())->deleteAll();

        $rolesData = $this->getRolesData();
        foreach ($rolesData as $roleData){
            $accessPolicyIds = [];
            foreach ($roleData['access_policies'] as $access_policy){
                $privilegeObject = (new Repository())->findByName($access_policy['privilege_name']);
                $privilegeId = $privilegeObject->getId();
                $accessPolicyObject = (new \RZP\Models\AccessPolicyAuthzRolesMap\Repository())
                    ->findByPrivilegeIdAndAction($privilegeId, $access_policy['action']);
                $accessPolicyId = $accessPolicyObject->getId();
                $accessPolicyIds[] = $accessPolicyId;
            }
            $roleDataFinal = $roleData;
            unset($roleDataFinal['access_policies']);
            $roleDataFinal['access_policy_ids'] = $accessPolicyIds;

            (new \RZP\Models\Roles\Service())->createStandardRole($roleDataFinal);
        }
    }

    private function getRolesData() :array
    {
        $merchantId = \RZP\Models\Roles\Entity::STANDARD_ROLE_MERCHANT_ID;
        return [
            [
                "id" => "owner",
                "name" => "owner",
                "description" => "Test description for owner",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],
                ],
            ],
            [
                "id" => "admin",
                "name" => "admin",
                "description" => "Test description for admin",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],
                ],
            ],
            [
                "id" => "finance_l1",
                "name" => "finance_l1",
                "description" => "Test description for finance l1",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],
                ],
            ],

            [
                "id" => "operations",
                "name" => "operations",
                "description" => "Test description for operations",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    /*[
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],*/
                ],
            ],

            [
                "id" => "ca",
                "name" => "ca",
                "description" => "Test description for CA",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],*/
                    /*[
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],*/
                ],
            ],

            [
                "id" => "view_only",
                "name" => "view_only",
                "description" => "Test description for view only",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],*/
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    /*[
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],*/
                ],
            ],

            [
                "id" => "vendor",
                "name" => "vendor",
                "description" => "Test description for vendor",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],
                ],
            ],

            [
                "id" => "finance_l2",
                "name" => "finance_l2",
                "description" => "Test description for finance l2",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],
                ],
            ],

            [
                "id" => "finance_l3",
                "name" => "finance_l3",
                "description" => "Test description for finance l3",
                "type" => "standard",
                "merchant_id" => $merchantId,
                "created_by" => "system",
                "updated_by" => "system",
                "access_policies" => [
                    [
                        "privilege_name" => "Account Statement & Balance",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Payouts",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Invoices",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Tax Payments",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Reports",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Account & Settings",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Manage Team & Workflow",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Developer Controls",
                        "action" => "view",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "create",
                    ],
                    [
                        "privilege_name" => "Business Profile/ Tax Settings",
                        "action" => "view",
                    ],
                ],
            ],
        ];
    }

}
