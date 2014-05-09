#RazorPay API

##Set up instructions

* Copy over `api.razorpay.com.vhost` to `/etc/apache2/sites-available/`, edit it and rename it to `api.razorpay.com`.
* `sudo a2ensite api.razorpay.com`
* `chmod -R o+wx app/storage/`
* Copy over `app/config/sample.database.php` to `app/config/database.php` and add db name & password.
* `php artisan migrate` (Creates tables)
* `php artisan db:seed` (Optional)