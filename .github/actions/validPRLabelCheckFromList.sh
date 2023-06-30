#!/bin/bash

# this is the static list
VALID_BU_LABELS=("bu_payments" "bu_platform" "bu_capital" "bu_razorpayx" "bu_others")
VALID_MODULE_LABELS=("module_order" "module_cards" "module_vault" "module_netbanking" "module_upi" "module_terminals" "module_router" "module_customers" "module_refunds" "module_settlements" "module_checkout" "module_ledger" "module_pricing" "module_recon" "module_apps"
"module_virtual_accounts" "module_tokens" "module_iin" "module_mozart" "module_pgconfig" "module_risk" "module_merchant_onboarding" "module_datawarehouse" "module_merchant_dashboard" "module_admin_dashboard" "module_partnerships" "module_notifications" "module_care" "module_es"
"module_cmma" "module_offers" "module_affordability" "module_api_decomp" "module_payouts" "module_fts" "module_tax_payments" "module_accounting_integration" "module_workflow" "module_vendor_payments" "module_accounts_receivable" "module_payout_links" "module_invoice" "module_search"
"module_cohesive_payout" "module_cac" "module_reporting" "module_splitz" "module_business_reporting" "module_auth" "module_growth" "module_ufh" "module_apache" "module_post_payments" "module_downtime")

# split the string
PR_LABELS=$(echo $CONCAT_PR_LABELS | tr $DELIMITER "\n")

validBuCount=0
validModuleCount=0

for validBuLabel in "${VALID_BU_LABELS[@]}"; do
    for prLabel in $PR_LABELS; do
        if [ "$validBuLabel" == "$prLabel" ] && [ "$prLabel" != "" ]
        then
            validBuCount=$((validBuCount+1))
        fi
    done
done

for validModuleLabel in "${VALID_MODULE_LABELS[@]}"; do
    for prLabel in $PR_LABELS; do
        if [ "$validModuleLabel" == "$prLabel" ] && [ "$prLabel" != "" ]
        then
            validModuleCount=$((validModuleCount+1))
        fi
    done
done

if [ $validBuCount -lt 1 ]
then
    echo "Valid BU PR label not found. Please refer this for valid BU labels https://github.com/razorpay/api/blob/master/.github/actions/validPRLabelCheckFromList.sh#L4"
    exit 1
elif [ $validModuleCount -lt 1 ]
then
    echo "Valid module PR label not found. Please refer this for valid module labels https://github.com/razorpay/api/blob/master/.github/actions/validPRLabelCheckFromList.sh#L5"
    exit 1
else
    echo "Valid BU and module PR labels found"
    exit 0
fi
