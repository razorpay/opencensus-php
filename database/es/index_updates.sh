
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
