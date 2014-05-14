#RazorPay API

[![Build Status](https://api.shippable.com/projects/536e6f0a16866c6d01fea8ae/badge/master)](https://www.shippable.com/projects/536e6f0a16866c6d01fea8ae)

##Set up instructions

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite api.razorpay.com.conf`
* `chmod -R o+wx app/storage/`
* Copy over `app/config/sample.database.php` to `app/config/database.php` and add db name & password.
* `php artisan migrate` (Creates tables)
* `php artisan db:seed` (Optional)
