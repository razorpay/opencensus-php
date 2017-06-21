mysql -u root --execute="
drop database if exists api_live;
drop database if exists api_test;
drop database if exists api_testing_live;
drop database if exists api_testing_test;
create database api_live;
create database api_test;
create database api_testing_live;
create database api_testing_test;
"
php artisan rzp:dbr --install --seed
APP_ENV=testing php artisan rzp:dbr --install

'cin=don1458795625|name=vinay kumar gupta|address=22 delhi|email=vin@gmail.com|phone=9911568475|MerchantDate=15012016-132611|MerchantAmt=1.00|remark=bill_payments|RU=https://www.abc.com|ITC=recharge|checksum= 098379a52e2f2efd401d3a3928358794'
