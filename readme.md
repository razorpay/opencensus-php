# RazorPay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb) [![Codacy Badge](https://www.codacy.com/project/badge/8d5f8e7504b24c71999d884569725575)](https://www.codacy.com) [![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

##Set up instructions (for development)

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite api.razorpay.com.conf`
* `chmod -R o+wx app/storage/`
* `php composer.phar install` to install project dependencies
* Create 2 databases (one is for live and another for test accounts). (`api-live`, `api-test` are sample names)
* Copy over `.env.sample.php` to `.env.dev.php` and provide both database usernames and password
* Copy over `bootstrap\sample.environment.php` to `bootstrap\environment.php`. This specifies the `dev` environment for local development.
* `php artisan rzp:dbr --install --seed` (Creates tables and seeds them)

#Testing

* Create two seperate databases for testing (separate from the development ones). (sample names: `api-testing-live`, `api-testing-test`)
* Copy over `.env.sample.php` to `.env.testing.php` and provide the database information
* For above step, if you have sqlite set-up. Google how to do that with laravel.
* Install php unit `sudo apt-get install phpunit`
* Run `phpunit` in api root. Preferably run as `phpunit --debug` for better view of tests when running manually.
* In case you are on 12.04, see [this question](http://stackoverflow.com/questions/1528717/phpunit-require-once-error) on how to fix the PHPUnit install.
