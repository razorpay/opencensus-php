# Razorpay dashboard

##Set up instructions

*  Copy over `dashboard.razorpay.dev.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite dashboard.razorpay.dev.conf`
* `sudo chmod -R o+wx app/storage/`
* Copy over `.env.sample.php` to `.env.php` and add db name & password.
* Run `php artisan migrate --seed` to migrate and seed the db.
* Run `php composer.phar install` to install laravel
* Run grunt