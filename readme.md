#RazorPay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb)

##Set up instructions

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite api.razorpay.com.conf`
* `chmod -R o+wx app/storage/`
* `php composer.phar install` to install project dependencies
*  Copy over `app/config/database.sample.php` to `app/config/database.php` and add db name & password.
* `php artisan rzp:dbr --install --seed` (Creates table and seeds them)

#Testing

* Create two seperate databases for testing.
* Copy over `app/config/testing/database.sample.php` to `app/config/database.php` and add the db names & passwords.
* Install php unit `sudo apt-get install phpunit`
* Run `phpunit` in api root. Preferably run as `phpunit --debug` for better view of tests when running manually.
* In case you are on 12.04, see [this question](http://stackoverflow.com/questions/1528717/phpunit-require-once-error) on how to fix the PHPUnit install.
