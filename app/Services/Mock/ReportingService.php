<?php

namespace RZP\Services\Mock;

use RZP\Services\Reporting as BaseReportingClient;

class ReportingService
{
    public function getReportingConfig(): array
    {
        return [
            "entity" => "collection",
            "count" => 3,
            "items" => [
                [
                  "id" => "config_D5RAgPWrrUgP9K",
                  "consumer" => "100000Razorpay",
                  "report_type" => "merchant",
                  "type" => null,
                  "scheduled" => false,
                  "name" => "SBI Bulk Refunds",
                  "description" => "SBI Bulk Refunds",
                  "template" => [
                    "raw_sql" => [
                        "query" => ""
                    ],
                    "file_meta" => [
                        "filename" => "RAZORPAY_{checksum}",
                      "delimiter" => "|",
                      "extension" => "txt"
                    ]
                  ],
                  "pipeline_params" => null,
                  "emails" => null,
                  "created_by" => "100000Razorpay",
                  "status" => null,
                  "created_at" => 1565703916,
                  "updated_at" => 1565703916
                ],
                [
                    "id" => "config_CC39ZQphE0ox5U",
                  "consumer" => "100000Razorpay",
                  "report_type" => "merchant",
                  "type" => "transactions",
                  "scheduled" => false,
                  "name" => "RX Account Statement",
                  "description" => "RX Account Statement",
                  "template" => [
                    "formats" => [
                        "date" => "d/m/Y H =>i =>s"
                    ],
                    "fields_map" => [
                        "tax" => [
                            "payouts.tax"
                        ],
                      "utr" => [
                            "payouts.utr"
                        ],
                      "mode" => [
                            "payouts.mode"
                        ],
                      "amount" => [
                            "transactions.amount"
                        ],
                      "currency" => [
                            "transactions.currency"
                        ],
                      "source_id" => [
                            "transactions.entity_id"
                        ],
                      "created_at" => [
                            "transactions.created_at"
                        ],
                      "source_type" => [
                            "transactions.type"
                        ],
                      "account_number" => [
                            "balance.account_number"
                        ],
                      "transaction_id" => [
                            "transactions.id"
                        ],
                      "closing_balance" => [
                            "transactions.balance"
                        ],
                      "fees (tax inclusive)" => [
                            "payouts.fees"
                        ]
                    ],
                    "output_fields" => [
                        "transaction_id",
                        "account_number",
                        "amount",
                        "currency",
                        "closing_balance",
                        "utr",
                        "mode",
                        "source_type",
                        "source_id",
                        "fees (tax inclusive)",
                        "tax",
                        "created_at"
                    ],
                    "attach_to_email" => true
                  ],
                  "pipeline_params" => [
                    "file_push" => [
                        "channel" => "beam",
                      "job_name" => "rzp_test_beam",
                      "protocol" => "sftp"
                    ],
                  ],
                  "emails" => null,
                  "created_by" => "100000Razorpay",
                  "status" => null,
                  "created_at" => 1553610628,
                  "updated_at" => 1553610628
                ],
                [
                    "id" => "config_C1eAjMzFDEU074",
                  "consumer" => "10000000000000",
                  "report_type" => "merchant",
                  "type" => "fund_accounts",
                  "scheduled" => false,
                  "name" => "RX fund accounts",
                  "description" => "RX fund_accounts",
                  "template" => [
                    "formats" => [
                        "date" => "d/m/Y H =>i =>s"
                    ],
                    "file_meta" => [
                        "filename" => "RZP_FUND_ACCOUNTS_{date=Ymd}"
                    ],
                    "fields_map" => [
                        "contact_id" => [
                            "contacts.id"
                        ],
                      "contact_name" => [
                            "contacts.name"
                        ],
                      "contact_type" => [
                            "contacts.type"
                        ],
                      "contact_email" => [
                            "contacts.email"
                        ],
                      "contact_notes" => [
                            "contacts.notes"
                        ],
                      "contact_active" => [
                            "contacts.active"
                        ],
                      "contact_mobile" => [
                            "contacts.contact"
                        ],
                      "fund_account_id" => [
                            "fund_accounts.id"
                        ],
                      "fund_account_ifsc" => [
                            "bank_accounts.ifsc_code"
                        ],
                      "fund_account_name" => [
                            "bank_accounts.beneficiary_name"
                        ],
                      "fund_account_type" => [
                            "fund_accounts.account_type"
                        ],
                      "contact_created_at" => [
                            "contacts.created_at"
                        ],
                      "fund_account_active" => [
                            "fund_accounts.active"
                        ],
                      "fund_account_number" => [
                            "bank_accounts.account_number"
                        ],
                      "contact_reference_id" => [
                            "contacts.reference_id"
                        ],
                      "fund_account_created_at" => [
                            "fund_accounts.created_at"
                        ],
                      "fund_account_vpa_address" => [
                            "vpas.address"
                        ]
                    ],
                    "output_fields" => [
                        "contact_id",
                        "contact_name",
                        "contact_type",
                        "contact_mobile",
                        "contact_email",
                        "contact_reference_id",
                        "contact_active",
                        "contact_notes",
                        "contact_created_at",
                        "fund_account_id",
                        "fund_account_type",
                        "fund_account_ifsc",
                        "fund_account_number",
                        "fund_account_name",
                        "fund_account_vpa_address",
                        "fund_account_active",
                        "fund_account_created_at"
                    ],
                    "attach_to_email" => true
                  ],
                  "pipeline_params" => [
                    "file_push" => [
                        "channel" => "beam",
                      "job_name" => "rzp_test_beam",
                      "protocol" => "sftp"
                    ]
                  ],
                  "emails" => null,
                  "created_by" => "BDRXQLRCjCiQ6U",
                  "status" => null,
                  "created_at" => 1551339252,
                  "updated_at" => 1551339252
                ]
            ]
        ];
    }
}
