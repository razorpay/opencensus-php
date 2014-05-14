#RazorPay API

##Set up instructions

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite api.razorpay.com.conf`
* `chmod -R o+wx app/storage/`
* Copy over `app/config/database.sample.php` to `app/config/database.php` and add db name & password.
* `php artisan migrate` (Creates tables)
* `php artisan db:seed` (Optional)

#Testing

* Do a `php composer.phar install` to get new packages.
* Create a seperate db for testing. Copy over `app/config/testing/database.sample.php` to `app/config/database.php` and add db name & password.
* Install php unit `sudo apt-get install phpunit`
* Run `phpunit` in api root. Preferably run as `phpunit --debug` for better view of tests when running manually.
* To test individual cards do `phpunit --filter testCard12` and so on for card 0 to 12.