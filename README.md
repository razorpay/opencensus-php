# Razorpay dashboard

## Set up instructions for development

*  Copy over `dashboard.razorpay.dev.conf` to `/etc/apache2/sites-available/`.
*  Edit the vhost to point to correct directory
* `sudo a2ensite dashboard.razorpay.dev.conf`
* `sudo chmod -R o+wx storage/`
* Copy over `environment/env.sample.php` to `environment/env.php`
* Copy `env`
* Run `php composer.phar install` to install laravel
* Run `php artisan migrate --seed` to migrate and seed the db. If you face problem regarding null fields, turn off strict SQL mode.
* `npm install`
* `npm install -g grunt-cli`
* `cp config/grunt.sample.json config/grunt.json`
* `grunt`
* `grunt watch`

- Open <http://dashboard.razorpay.dev> and login as `test@razorpay.com/123456`.

## Setup instructions for testing

* copy over `.env.sample.php` to `.env.testing.php` and add db name & password
* Create a `$HOME/.selenium` directory
* Download the latest selenium server jar file from `http://www.seleniumhq.org/download/` and download it in the `~/.selenium` directory.
* Make sure you have firefox installed.

## Homestead specific instructions

* Install XQuartz (Mac Only)
* Add this to Homestead.yaml : `configure.ssh.forward_x11 = true`
* Ensure Selenium is available in /home/vagrant/.selenium
* `sudo apt-get update`
* `sudo apt-get install openjdk-7-jre xvfb firefox`
* Run selenium server manually : `java -jar ~/.selenium/selenium-server.jar`
* Run tests : `xvfb-run phpunit`

# Selenium

If you can't get selenium to work, make sure `API_MOCK` is true in `.env.testing.php`.

# Docs

To generate documentation for our PHP codebase, run the following:

```bash
curl -L https://github.com/ApiGen/ApiGen/releases/download/v4.1.0/apigen-4.1.0.phar -o apigen && chmod +x apigen
./apigen generate -d ./docs -s ./app
```

The documentation will be generated in the docs directory.
