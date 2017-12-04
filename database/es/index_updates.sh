
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
