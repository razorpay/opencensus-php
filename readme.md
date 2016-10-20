# Razorpay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb) [![Codacy Badge](https://www.codacy.com/project/badge/8d5f8e7504b24c71999d884569725575)](https://www.codacy.com) [![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

## Set up instructions ( for development )

#### Pre-requistics

* Install [composer](https://getcomposer.org/download/) PHP package manager

### ( Vagrant / Homestead )

* Install Virtualbox & Vagrant
* Add laravel/homestead box. (Manual download recommended.)
* Clone Homestead repo & follow setup instructions.
* Map api to /path/to/api in folders [ ~/.homestead/Homestead.yaml ], with "nfs" enabled.
* Map api.razorpay.dev to /path/to/api/public in sites [ ~/.homestead/Homestead.yaml ]
* Follow Common Instructions
* Follow Common Test Setup Instructions

### ( Ubuntu )

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/` and update the directory location where your project lies.
* `sudo a2ensite api.razorpay.com.conf`
* Follow Common Instructions
* Follow Common Test Setup Instructions

### ( OSX )
*  Copy the contents of `api.razorpay.com.conf` to `httpd-vhosts.conf` and update the directory location where your project lies.
* Follow Common Instructions
* Follow Common Test Setup Instructions
#### OSX - Extra steps

* Install coreutils `brew install coreutils --with-default-names`
~~* Create a symbolic link for date util `sudo ln -s /usr/local/opt/coreutils/libexec/gnubin/date /usr/bin/date`~~

### Common Instruction

* `chmod -R o+wx storage/`
* `php composer.phar install` to install project dependencies
* Create 2 databases (one is for live and another for test accounts). (`api_live`, `api_test` are sample names)
* Copy over `environment/.env.sample` to `environment/.env.dev` and provide both database usernames and password
* Copy over `environment/env.sample.php` to `environment/env.php`. This specifies the `dev` environment for local development.
* `php artisan rzp:dbr --install --seed` (Creates tables and seeds them)

### Common Test Setup Instructions

* Create two seperate databases for testing (separate from the development ones). (sample names: `api_testing_live`, `api_testing_test`)
* Copy over `environment/.env.sample` to `.env.testing` and provide the database information
* For above step, if you have sqlite set-up. Google how to do that with laravel.
* Install php unit `sudo apt-get install phpunit`
* Disable Xdebug ( Some Tests fail with Xdebug enabled ): `sudo php5dismod xdebug`
* Restart Webserver
* Run `phpunit` in api root. Preferably run as `phpunit --debug` for better view of tests when running manually.
* In case you are on 12.04, see [this question](http://stackoverflow.com/questions/1528717/phpunit-require-once-error) on how to fix the PHPUnit install.
* In case you see this error about missing tables in api_testing database, run the following on api root: `APP_ENV=testing php artisan rzp:dbr --install`

TIP: Change the values of `RUN_FIXTURES` and `RUN_FIXTURES_ONCE` in `.env.testing` file to `false` after running the tests once. This makes sure you don't run fixtures everytime and hence the tests will run faster. If you add/change/delete any fixtures or clear your test db then change them to `true` once, run tests and then change them back to `false`.

### Code Coverage Instructions

* Install `php70-xdebug`
* Run `phpunit --coverage-html [Directory to save coverage]`
* You can also generate coverage in other formats. Visit [PHPUnit CodeCoverage](https://phpunit.de/manual/current/en/code-coverage-analysis.html) for more info.

# Docs

To generate documentation, run the following:

    curl -L https://github.com/ApiGen/ApiGen/releases/download/v4.1.0/apigen-4.1.0.phar -o apigen && chmod +x apigen
    ./apigen generate -d ./docs

The documentation will be generated in the docs directory.


# Email Templates

All email templates are saved as `.email` files, which are then compiled to
blade templates before committing. Run the following command to regenerate:

	php artisan email:gen

If you have created a new email template, make sure you edit the
`GenerateEmailTemplate.php` file to add the template in the templates array.


# Editor Configuration

Make sure that you install the plguin for your editor from <http://editorconfig.org/>.

This will ensure that your editor respects our coding style. You can find the styles
themselves at [.editorconfig](.editorconfig) file in the root of this repo
