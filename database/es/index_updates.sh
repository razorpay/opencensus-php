
#
# References:
# Prod host: http://prod.es-audit.razorpay.vpc:9200
# Beta host: http://beta.es-audit.razorpay.vpc:9200
#

# 16 Nov 2017: Adds user_id mapping in invoice index
# Run on both beta and prod instances (For beta, change host and index names)
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_invoice_test/_mapping/api_invoice_test" -d '{
    "properties": {
        "user_id": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_invoice_live/_mapping/api_invoice_live" -d '{
    "properties": {
        "user_id": {
            "type": "keyword"
        }
    }
}'

# 16 Nov 2017: Re-index invoice index to include user_id in documents
# Run on both beta and prod
php artisan rzp:index test invoice
php artisan rzp:index live invoice


# 08 Dec 2017: Adds merchant_detail.activation_status & merchant_detail.archived_at
#              fields mapping in merchant index
# Run on both beta and prod instances (For beta, change host and index names)
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_test/_mapping/api_merchant_test" -d '{
    "properties": {
        "merchant_detail.activation_status": {
            "type": "keyword"
        },
        "merchant_detail.archived_at": {
            "type": "date",
            "format": "yyyy-MM-dd HH:mm:ss||epoch_millis"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_live/_mapping/api_merchant_live" -d '{
    "properties": {
        "merchant_detail.activation_status": {
            "type": "keyword"
        },
        "merchant_detail.archived_at": {
            "type": "date",
            "format": "yyyy-MM-dd HH:mm:ss||epoch_millis"
        }
    }
}'

# 08 Dec 2017: Re-index merchant index to include newly added fields in documents
# Run on both beta and prod
php artisan rzp:index test merchant
php artisan rzp:index live merchant

# 16 Mar, 2018: Adds merchant_detail.reviewer_id in merchant index
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_test/_mapping/api_merchant_test" -d '{
    "properties": {
        "merchant_detail.reviewer_id": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_live/_mapping/api_merchant_live" -d '{
    "properties": {
        "merchant_detail.reviewer_id": {
            "type": "keyword"
        }
    }
}'

# 16 Mar, 2018: Re-index merchant index to include newly added fields in documents
# Run on both beta and prod
php artisan rzp:index test merchant
php artisan rzp:index live merchant

#11 Oct, 2018, Adds balance and activation flow in merchant index
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_live/_mapping/api_merchant_live" -d '{
    "properties": {
        "merchant_detail.activation_flow": {
            "type": "keyword"
        },
        "balance" : {
            "type" : "integer"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_test/_mapping/api_merchant_test" -d '{
    "properties": {
        "merchant_detail.activation_flow": {
            "type": "keyword"
        },
        "balance" : {
            "type" : "integer"
        }
    }
}'

#11 Oct, 2018, Re-index merchant index to include newly added fields in documents
# Run on both beta and prod
php artisan rzp:index test merchant
php artisan rzp:index live merchant

#26 Oct, 2018, Adds entity_type to invoice index
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_invoice_test/_mapping/api_invoice_test" -d '{
    "properties": {
        "entity_type": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_invoice_live/_mapping/api_invoice_live" -d '{
    "properties": {
        "entity_type": {
            "type": "keyword"
        }
    }
}'

#17 Dec, 2019, Adds recurring to payment index
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_payment_live/_mapping/api_payment_live" -d '{
    "properties": {
        "recurring": {
            "type": "boolean"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_payment_test/_mapping/api_payment_test" -d '{
    "properties": {
        "recurring": {
            "type": "boolean"
        }
    }
}'

#30th April 2020
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_payment_live/_mapping/api_payment_live" -d '{
    "properties": {
        "amount_transferred": {
            "type": "long"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_payment_test/_mapping/api_payment_test" -d '{
    "properties": {
        "amount_transferred": {
            "type": "long"
        }
    }
}'

#25 Sept 2020
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_live/_mapping/api_merchant_live" -d '{
    "properties": {
        "account_code": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_test/_mapping/api_merchant_test" -d '{
    "properties": {
        "account_code": {
            "type": "keyword"
        }
    }
}'

#21 October 2020
curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_payment_live/_mapping/api_payment_live" -d '{
    "properties": {
        "va_transaction_id": {
            "type": "keyword"
        },
        "status": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_payment_test/_mapping/api_payment_test" -d '{
    "properties": {
        "va_transaction_id": {
            "type": "keyword"
        },
        "status": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_virtual_account_test/_mapping/api_virtual_account_test" -d '{
    "properties": {
        "description": {
            "type": "text"
        },
        "status": {
            "type": "keyword"
        },
        "email": {
            "type": "text"
        },
        "contact": {
            "type": "text"
        },
        "name": {
            "type": "text"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_virtual_account_live/_mapping/api_virtual_account_live" -d '{
    "properties": {
        "description": {
            "type": "text"
        },
        "status": {
            "type": "keyword"
        },
        "email": {
            "type": "text"
        },
        "contact": {
            "type": "text"
        },
        "name": {
            "type": "text"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_virtual_account_test/_mapping/api_virtual_account_test" -d '{
    "properties": {
        "vpa": {
            "type": "keyword"
        },
        "account_number": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_virtual_account_live/_mapping/api_virtual_account_live" -d '{
    "properties": {
        "vpa": {
            "type": "keyword"
        },
        "account_number": {
            "type": "keyword"
        }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_test/_mapping/api_merchant_test" -d '{
    "properties": {
          "merchant_business_detail": {
              "properties": {
                  "miq_sharing_date": {
                      "type"   : "date",
                      "format" : "yyyy-MM-dd HH:mm:ss||epoch_millis",
                  },
                  "testing_credentials_date": {
                      "type"   : "date",
                      "format" : "yyyy-MM-dd HH:mm:ss||epoch_millis",
                  }
              }
          }
    }
}'

curl -XPUT "http://prod.es-audit.razorpay.vpc:9200/api_merchant_live/_mapping/api_merchant_live" -d '{
    "properties": {
          "merchant_business_detail": {
              "properties": {
                  "miq_sharing_date": {
                      "type"   : "date",
                      "format" : "yyyy-MM-dd HH:mm:ss||epoch_millis",
                  },
                  "testing_credentials_date": {
                      "type"   : "date",
                      "format" : "yyyy-MM-dd HH:mm:ss||epoch_millis",
                  }
              }
          }
    }
}'
