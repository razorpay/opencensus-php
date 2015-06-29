# Razorpay dashboard

## Set up instructions for development

*  Copy over `dashboard.razorpay.dev.conf` to `/etc/apache2/sites-available/`.
* `sudo a2ensite dashboard.razorpay.dev.conf`
* `sudo chmod -R o+wx app/storage/`
* Copy over `.env.sample.php` to `.env.php` and add db name & password.
* Run `php artisan migrate --seed` to migrate and seed the db.
* Run `php composer.phar install` to install laravel
* `npm install`
* `npm install -g grunt-cli`
* `cp app/config/grunt.sample.json app/config/grunt.json`
* `grunt`


## Setup instructions for testing

* copy over `.env.sample.php` to `.env.testing.php` and add db name & password
* Create a `$HOME/.selenium` directory
* Download the latest selenium server jar file from `http://www.seleniumhq.org/download/` and download it in the `~/.selenium` directory.
* Make sure you have firefox installed.