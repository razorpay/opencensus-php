#RazorPay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb)

##Set up instructions (for development)

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite api.razorpay.com.conf`
* `chmod -R o+wx app/storage/`
* `php composer.phar install` to install project dependencies
* Create 2 databases.
* Copy over `.env.sample.php` to `.env.dev.php` and provide both database usernames and password
* Copy over `bootstrap\sample.environment.php` to `bootstrap\environment.php`. This specifies the `dev` environment for local development.
* `php artisan rzp:dbr --install --seed` (Creates tables and seeds them)
* Change `APP_DASHBOARD_SECRET`, `HDFC_ID` and `HDFC_PASSWORD` in .env.dev.php to values given by a team member.

#Testing

* Create two seperate databases for testing (separate from the development ones).
* Copy over `.env.sample.php` to `.env.testing.php` and provide the database information
* For above step, if you have sqlite set-up. Google how to do that with laravel.
* Change `APP_DASHBOARD_SECRET`, `HDFC_ID` and `HDFC_PASSWORD` in .env.testing.php to values given by a team member.
* Install php unit `sudo apt-get install phpunit`
* Run `phpunit` in api root. Preferably run as `phpunit --debug` for better view of tests when running manually.
* In case you are on 12.04, see [this question](http://stackoverflow.com/questions/1528717/phpunit-require-once-error) on how to fix the PHPUnit install.
