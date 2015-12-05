# Razorpay dashboard

## Set up instructions for development

*  Copy over `dashboard.razorpay.dev.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite dashboard.razorpay.dev.conf`
* `sudo chmod -R o+wx app/storage/`
* Copy over `.env.sample.php` to `.env.dev.php` and add db name & password.
* Copy over `bootstrap\environment.sample.php` to `bootstrap\environment.php`. Specify `dev` environment for local development.
* Run `php composer.phar install` to install laravel
* Run `php artisan migrate --seed` to migrate and seed the db. If you face problem regarding null fields, turn off strict SQL mode.
* `npm install`
* `npm install -g grunt-cli`
* `cp app/config/grunt.sample.json app/config/grunt.json`
* `grunt`


## Setup instructions for testing

* copy over `.env.sample.php` to `.env.testing.php` and add db name & password
* Create a `$HOME/.selenium` directory
* Download the latest selenium server jar file from `http://www.seleniumhq.org/download/` and download it in the `~/.selenium` directory.
* Make sure you have firefox installed.

# Selenium

If you can't get selenium to work, make sure `API_MOCK` is true in `.env.testing.php`
