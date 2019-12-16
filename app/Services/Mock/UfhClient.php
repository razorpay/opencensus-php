<?php

namespace RZP\Services\Mock;

class UfhClient
{
    public function getFiles(): array
    {
        return [
            "entity" => "collection",
            "count" => 4,
            "items" => [
                [
                    "id"  =>  "file_Cv85RXWqCe1giL",
                    "type" =>  null,
                    "comments" =>  null,
                    "name" =>  "Razorpay_testing_ufh",
                    "location" =>  "Razorpay_testing_ufh.txt",
                    "created_at" =>  1563453306,
                    "bucket" =>  "test_bucket",
                    "bucket_config_name" =>  "refund_bucket_config",
                    "mime" =>  "text/plain",
                    "display_name" =>  null,
                    "region" =>  "us-east-1",
                    "extension" =>  "txt",
                    "merchant_id" =>  "12345678901234",
                    "entity_id" =>  "1",
                    "entity_type" =>  "refunds",
                    "is_compressed" =>  false,
                    "is_encrypted" =>  true,
                    "size" =>  106,
                    "store" =>  "local",
                    "status" =>  "uploaded",
                    "metadata" =>  [],
                    "upload_url" =>  null
                ],
                [
                    "id" =>  "file_Cv81hhOdHqseik",
                    "type" =>  null,
                    "comments" =>  null,
                    "name" =>  "rzp_07042019_v5",
                    "location" =>  "rzp_07042019_v5.txt",
                    "created_at" =>  1563453094,
                    "bucket" =>  "test_bucket",
                    "bucket_config_name" =>  "refund_bucket_config",
                    "mime" =>  "text/plain",
                    "display_name" =>  null,
                    "region" =>  "us-east-1",
                    "extension" =>  "txt",
                    "merchant_id" =>  "12345678901234",
                    "entity_id" =>  "1",
                    "entity_type" =>  "refunds",
                    "is_compressed" =>  false,
                    "is_encrypted" =>  true,
                    "size" =>  93,
                    "store" =>  "local",
                    "status" =>  "uploaded",
                    "metadata" =>  [],
                    "upload_url" =>  null
                ],
                [
                    "id" =>  "file_CMhj47egJENFxU",
                    "type" =>  "Freecharge-report",
                    "comments" =>  null,
                    "name" =>  "RZP_22042019",
                    "location" =>  "RZP_22042019.txt",
                    "created_at" =>  1555936910,
                    "bucket" =>  "test_bucket",
                    "bucket_config_name" =>  "report_bucket_config",
                    "mime" =>  "text/plain",
                    "display_name" =>  null,
                    "region" =>  "us-east-1",
                    "extension" =>  "txt",
                    "merchant_id" =>  "12345678901234",
                    "entity_id" =>  null,
                    "entity_type" =>  null,
                    "is_compressed" =>  false,
                    "is_encrypted" =>  true,
                    "size" =>  123,
                    "store" =>  "local",
                    "status" =>  "uploaded",
                    "metadata" =>  [],
                    "upload_url" =>  null
                ],
                [
                    "id" =>  "file_DR5YCmx7sGkyWf",
                    "type" =>  "zomato-report",
                    "comments" =>  null,
                    "name" =>  "nb_hdfc_test_row",
                    "location" =>  "nb_hdfc_test_row.csv",
                    "created_at" =>  1570431259,
                    "bucket" =>  "api-settlement",
                    "bucket_config_name" =>  null,
                    "mime" =>  "text/csv",
                    "display_name" =>  "Netbanking_hdfc_7_oct",
                    "region" =>  "us-east-1",
                    "extension" =>  "csv",
                    "merchant_id" =>  "12345678901234",
                    "entity_id" =>  "log_Abcde12345ABCD",
                    "entity_type" =>  "log",
                    "is_compressed" =>  false,
                    "is_encrypted" =>  true,
                    "size" =>  128,
                    "store" =>  "s3",
                    "status" =>  "failed",
                    "metadata" =>  [
                        "Content-Disposition" =>  "attachment; filename=Netbanking_hdfc_7_oct.csv"
                    ],
                    "upload_url" =>  null
                ],
            ]
        ];
    }

    // Returns single file entity
    public function getFileById(string $id): array
    {
        return [
            "id"  =>  $id,
            "type" =>  null,
            "comments" =>  null,
            "name" =>  "Razorpay_testing_ufh",
            "location" =>  "Razorpay_testing_ufh.txt",
            "created_at" =>  1563453306,
            "bucket" =>  "test_bucket",
            "bucket_config_name" =>  "refund_bucket_config",
            "mime" =>  "text/plain",
            "display_name" =>  null,
            "region" =>  "us-east-1",
            "extension" =>  "txt",
            "merchant_id" =>  "12345678901234",
            "entity_id" =>  "1",
            "entity_type" =>  "refunds",
            "is_compressed" =>  false,
            "is_encrypted" =>  true,
            "size" =>  106,
            "store" =>  "local",
            "status" =>  "uploaded",
            "metadata" =>  [],
            "upload_url" =>  null
        ];
    }
}
