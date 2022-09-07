#!/bin/bash
#
# Shell script to generate crontab file and install it for local dev.
#
# This file is to be used for tracking changes in our cron jobs.
# You can also use it for setting up crons on your local machine.
# The script generates a crontab file in /tmp and installs it.
# Output of the jobs is logged in storage/logs/cron.log
#
# Commented-out jobs are currently disabled on prod.

BASE_URL="http://api.razorpay.in/v1"

TMP_CRONTAB=/tmp/crontab

LIVE_AUTH="rzp_live:RANDOM_CRON_PASSWORD"
TEST_AUTH="rzp_test:RANDOM_CRON_PASSWORD"

CRON_LOG=`pwd`/storage/logs/cron.log

# Preserve existing crontab
crontab -l > $TMP_CRONTAB 2>/dev/null

# Comment this out if you'd like to receive emails with cron output
echo "MAILTO=\"\"" >> $TMP_CRONTAB

add_cron() {
    exp=$1
    name=$2
    action=$3
    url=$4
    body=$5
    auth=$6

    # Expression needs to be written separately, to avoid *-expansion
    echo -n "$exp " >> $TMP_CRONTAB

    # Explicit linebreak is required because the last-executed curl
    # command wrote output without a newline
    logContext="printf \"\n\`date\` $name: \" >> $CRON_LOG"

    curlCommand="curl -X $action '$url' -d '$body' -u '$auth' >> $CRON_LOG"

    line="$logContext && $curlCommand"

    # The line added to crontab has the format: "cronExpression logContext && curlCommand"
    # logContext writes a timestamp and the name of the cron job to a file
    # named in CRON_LOG. curlCommand also redirects stdout to the same file.

    echo $line >> $TMP_CRONTAB
}

#        Expression        CronName (25 chars)         Verb Route                                        Payload                         Auth

add_cron "6 2 * * *"       "payment_auth_notify_prod"  GET  "$BASE_URL/payments/auth/notify"             ""                              $LIVE_AUTH
add_cron "5 0 * * *"       "mrchnt_daily_report_prod"  POST "$BASE_URL/merchants/report"                 ""                              $LIVE_AUTH
add_cron "0 14 * * *"      "authorized_reminder_live"  GET  "$BASE_URL/payments/all/reminder"            ""                              $LIVE_AUTH
add_cron "0 3 * * *"       "emi_excel_generate"        POST "$BASE_URL/emi/generate/excel"               ""                              $LIVE_AUTH
add_cron "5 0 * * *"       "scorecard_prod"            POST "$BASE_URL/scorecard"                        ""                              $LIVE_AUTH
add_cron "5 0 * * *"       "banking_scorecard_prod"    POST "$BASE_URL/banking_scorecard"                ""                              $LIVE_AUTH
add_cron "0 * * * *"       "prod_international_curren" POST "$BASE_URL/international/USD/rates"          ""                              $LIVE_AUTH
add_cron "22 */2 * * *"    "payment_update_on_hold"    POST "$BASE_URL/payments/on_hold/update"          ""                              $LIVE_AUTH

# Settlements/Payouts
add_cron "1 7-18 * * 1-6"  "settlement_prod_live"      POST "$BASE_URL/settlements/initiate/kotak"       ""                              $LIVE_AUTH
add_cron "0 6 * * 1-6"     "settlement_prod_test"      POST "$BASE_URL/settlements/initiate/kotak"       ""                              $TEST_AUTH
add_cron "30 22 * * 1-6"   "settlement_recon_test"     POST "$BASE_URL/settlements/reconcile/test"       ""                              $TEST_AUTH
add_cron "30 0 * * 1-6"    "beneficiary_gen_live"      POST "$BASE_URL/merchants/beneficiary/file/bank"  ""                              $LIVE_AUTH
add_cron "1 5-18 * * 1-6"  "payouts_prod_live"         POST "$BASE_URL/payouts/initiate/kotak"           ""                              $LIVE_AUTH
add_cron "0 15,20 * * *"   "fta_recon_report"          GET  "$BASE_URL/fund_transfer_attempts/recon_report" ""                           $LIVE_AUTH

# Verify
add_cron "* * * * *"       "payment_verify_prod_live"  POST "$BASE_URL/payments/verify/payments_failed"  ""                              $LIVE_AUTH
add_cron "1-59/2 * * * *"  "pymnt_verify_prod_created" POST "$BASE_URL/payments/verify/payments_created" ""                              $LIVE_AUTH
# add_cron "2-57/5 * * * *"  "pymnt_verify_prod_failed"  POST "$BASE_URL/payments/verify/verify_failed"    ""                              $LIVE_AUTH
# add_cron "2-57/5 * * * *"  "pymnt_verify_prod_error"   POST "$BASE_URL/payments/verify/verify_error"     ""                              $LIVE_AUTH
# add_cron "* * * * *"       "payment_verify_bucket_0"   POST "$BASE_URL/payments/verify/payments_failed"  "bucket=0"                      $LIVE_AUTH
# add_cron "* * * * *"       "payment_verify_bucket_1"   POST "$BASE_URL/payments/verify/payments_failed"  "bucket=1"                      $LIVE_AUTH

# Timeout
add_cron "21,51 * * * *"   "payment_timeout_prod_test" POST "$BASE_URL/payments/timeout"                 ""                              $TEST_AUTH
add_cron "2-57/5 * * * *"  "payment_timeout_prod_live" POST "$BASE_URL/payments/timeout"                 ""                              $LIVE_AUTH

# Holidays
add_cron "0 18 * * *"      "merch_holiday_add_emails"  POST "$BASE_URL/merchants/notify/holiday"         "action=add_to_list&lists=live" $LIVE_AUTH
add_cron "0 19 * * *"      "merch_holiday_notify_hol"  POST "$BASE_URL/merchants/notify/holiday"         "action=email&lists=live"       $LIVE_AUTH

# Migration
# add_cron "15 * * * *"    "prod_merchant_details_mig" POST "$BASE_URL/merchant/activation/migrate"      ""                              $LIVE_AUTH
add_cron "0 2 * * *"       "prod_merchant_schdule_mig" POST "$BASE_URL/merchants/schedules/migrate"                                      $LIVE_AUTH

# Refund
add_cron "0 3 * * *"        "nb_refunds_prod"                POST "$BASE_URL/refunds/excel"                             "method=netbanking"              $LIVE_AUTH
add_cron "1-59/10 * * * *"  "authorized_old_refund"          POST "$BASE_URL/payments/refund/authorized"                 ""                              $LIVE_AUTH
add_cron "6-51/15 * * * *"  "order_refund_multiple_aut"      POST "$BASE_URL/orders/payments/refund"                     ""                              $LIVE_AUTH
add_cron "48 3-21/6 * * *"  "batch_processor_prod_live"      POST "$BASE_URL/batches/process"                            ""                              $LIVE_AUTH
add_cron "48 3-21/6 * * *"  "batch_processor_prod_test"      POST "$BASE_URL/batches/process"                            ""                              $TEST_AUTH
add_cron "7 10,22 * * *"    "gateway_create_refund_rec"      POST "$BASE_URL/refunds/billdesk/create_record"             ""                              $LIVE_AUTH
add_cron "58 7,19 * * *"    "freecharge_create_refund_rec"   POST "$BASE_URL/refunds/wallet_freecharge/create_record"    ""                              $LIVE_AUTH
# add_cron "33 1-19/6 * * *"  "freecharge_validate_refund_rec" POST "$BASE_URL/refunds/wallet_freecharge/validate"         ""                              $LIVE_AUTH
# add_cron "9,39 * * * *"     "refund_gateway_refunded_txns"   POST "$BASE_URL/refunds/gateway_refunded/transaction"       ""                              $LIVE_AUTH
add_cron "*/15 * * * *"     "virtual_account_refund_excess"  POST "$BASE_URL/virtual_accounts/refund/excess"             ""                              $LIVE_AUTH

# Gateway File
add_cron "15 3 * * *"       "gateway_file_refunds_prod"           POST "$BASE_URL/gateway/files"              "type=refund&targets[]=hdfc&targets[]=icici&targets[]=paylater_icici&targets[]=icici_emi"                                                            $LIVE_AUTH
add_cron "25 3 * * *"       "gateway_file_emi_prod"               POST "$BASE_URL/gateway/files"              "type=emi&targets[]=indusind&targets[]=kotak&targets[]=axis&targets[]=rbl&targets[]=scbl"               $LIVE_AUTH
add_cron "00 1 * * *"       "gateway_file_sbi_emi_prod"           POST "$BASE_URL/gateway/files"              "type=emi&targets[]=sbi"                                                                                $LIVE_AUTH
add_cron "30 3 * * *"       "gateway_file_axis_corp_prod"         POST "$BASE_URL/gateway/files"              "type=combined&targets[]=axis&sub_type=corporate"                                                       $LIVE_AUTH
add_cron "31 3 * * *"       "gateway_file_axis_ncorp_prod"        POST "$BASE_URL/gateway/files"              "type=combined&targets[]=axis&sub_type=non_corporate"                                                   $LIVE_AUTH
add_cron "32 3 * * *"       "gateway_file_combined_prod"          POST "$BASE_URL/gateway/files"              "type=combined&targets[]=indusind&targets[]=federal&targets[]=allahabad&targets[]=sib&targets[]=canara" $LIVE_AUTH
add_cron "00 9 * * *"       "gateway_file_combined_csb_prod"      POST "$BASE_URL/gateway/files"              "type=combined&targets[]=csb"                                                                           $LIVE_AUTH
add_cron "00 9 * * *"       "gateway_file_emandate_register_hdfc" POST "$BASE_URL/gateway/files"              "type=emandate_register&targets[]=hdfc"                                                                 $LIVE_AUTH

add_cron "10 9 * * *"       "gateway_file_emandate_debit_axis"    POST "$BASE_URL/gateway/files"              "type=emandate_debit&targets[]=axis"                                                                    $LIVE_AUTH
add_cron "15 9 * * *"       "gateway_file_emandate_debit_hdfc"    POST "$BASE_URL/gateway/files"              "type=emandate_debit&targets[]=hdfc"                                                                    $LIVE_AUTH
add_cron "20 9 * * *"       "gateway_file_emandate_debit_enach"   POST "$BASE_URL/gateway/files"              "type=emandate_debit&targets[]=enach_rbl"                                                               $LIVE_AUTH
add_cron "20 9 * * *"       "gateway_file_debit_enach_nb"         POST "$BASE_URL/gateway/files"              "type=emandate_debit&targets[]=enach_npci_netbanking"                                                   $LIVE_AUTH


# Invoice
add_cron "*/10 * * * *"     "invoice_expire_bulk_test"       POST "$BASE_URL/invoices/expire"                            ""                              $TEST_AUTH
add_cron "*/10 * * * *"     "invoice_expire_bulk_live"       POST "$BASE_URL/invoices/expire"                            ""                              $LIVE_AUTH

# Payment Link
add_cron "*/10 * * * *"     "payment_link_expire_cron_test"  POST "$BASE_URL/payment_links/expire"                       ""                              $TEST_AUTH
add_cron "*/10 * * * *"     "payment_link_expire_cron_live"  POST "$BASE_URL/payment_links/expire"                       ""                              $LIVE_AUTH

# Merchant Invoice
add_cron "10 0 1 * *"       "merchant_create_invoice"        POST "$BASE_URL/merchants/invoice/create"                   ""                              $LIVE_AUTH

# Nodal Transfer
add_cron "30 16 * * *"      "nodal_transfer_icici_nb"        POST "$BASE_URL/nodal/transfer"                             "gateway=netbanking_icici"      $LIVE_AUTH
add_cron "0 1 * * *"        "nodal_transfer_icici_upi"       POST "$BASE_URL/nodal/transfer"                             "gateway=upi_icici"             $LIVE_AUTH
add_cron "0 10 * * *"       "nodal_transfer_icici_fd"        POST "$BASE_URL/nodal/transfer"                             "gateway=first_data"            $LIVE_AUTH

# Subscription
add_cron "0 */2 * * *"      "subscriptions_charge"           POST "$BASE_URL/subscriptions/charge/invoices"              ""                              $LIVE_AUTH
add_cron "0 * * * *"        "subscriptions_auth_retry"       POST "$BASE_URL/subscriptions/retry"                        ""                              $LIVE_AUTH
add_cron "*/10 * * * *"     "subscriptions_expire"           POST "$BASE_URL/subscriptions/expire"                       ""                              $LIVE_AUTH
add_cron "*/10 * * * *"     "subscriptions_cancel"           POST "$BASE_URL/subscriptions/cancel/due"                   ""                              $LIVE_AUTH

# DSP Blackrock
add_cron "0 15 * * *"       "dsp_report_today"               GET  "$BASE_URL/reports/transaction/dsp"    "mail=1&email=dummy@dspblackrock.com&day=today"      $LIVE_AUTH
add_cron "0 1 * * *"        "dsp_report_yesterday"           GET  "$BASE_URL/reports/transaction/dsp"    "mail=1&email=dummy@dspblackrock.com&day=yesterday"  $LIVE_AUTH

# Daily Recon Summary
add_cron "30 22 * * *"      "daily_recon_summary"            GET "$BASE_URL/daily_recon_summary"                         ""                              $LIVE_AUTH

# Merchant Es Sync
add_cron "*/15 * * * *"      "merchant_es_sync_live"         POST "$BASE_URL/merchant/sync_es/bulk"                         ""                           $LIVE_AUTH

# Merchant Salesforce Poc Update
add_cron "0 9,13,17 * * *"      "merchant_salesforce_poc"       POST "$BASE_URL/admin/poc_update"                              ""                           $LIVE_AUTH

add_cron "*/60 9-22 * * *"      "salesforce_poc_with_time"       POST "$BASE_URL/admin/poc_update_with_time"                    ""                          $LIVE_AUTH

#penny testing retry for every 2 hour
add_cron "0 */2 * * *"      "retry_penny_testing_cron"       POST "$BASE_URL/merchants/retry_penny_testing"                 ""                           $LIVE_AUTH

# Daily: Dynamic netbanking URL update in status cake
# add_cron "0 0 * * *"        "dynamic_netbanking_url_update"  POST "$BASE_URL/payment/netbanking/statuscake/urlsync"      "driver=statuscake"             $LIVE_AUTH

# Banking account statement
# add_cron "0 * * * *"        "banking_account_statement"      POST  "BASE_URL/banking_account_statement/process"        ""                               $LIVE_AUTH

# Install the generated crontab
crontab $TMP_CRONTAB
