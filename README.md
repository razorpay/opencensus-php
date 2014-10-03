# Razorpay dashboard

##Set up instructions

* Copy over `dashboard.razorpay.dev.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite dashboard.razorpay.dev.conf`
* `sudo chmod -R o+wx app/storage/`
* Copy over `app/config/database.sample.php` to `app/config/database.php` and add db name & password.
* Run grunt