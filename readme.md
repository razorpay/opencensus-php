#RazorPay API

##Set up instructions

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite api.razorpay.com.conf`
* `chmod -R o+wx app/storage/`
* Copy over `app/config/database.sample.php` to `app/config/database.php` and add db name & password.
* `php artisan migrate` (Creates tables)
* `php artisan db:seed` (Optional)
