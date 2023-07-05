# Razorpay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb) [![Codacy Badge](https://www.codacy.com/project/badge/8d5f8e7504b24c71999d884569725575)](https://www.codacy.com) [![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

## Set up instructions ( for development )

#### Pre-requisites

* Install [composer](https://getcomposer.org/download/) PHP package manager

Note: Please ensure that you upgrade composer 1.x.x to 2.x.x, as the old version of composer is incompatible with our codebase, and causes issues during ``composer install``.  ``composer self-update --2`` to upgrade.

There are 3 Ways to set up, you can choose either:

  ### 1.( Docker )

  * [Refer Docker](readme-docker.md)


  ### 2.( Vagrant / Homestead )

  * Install Virtualbox & Vagrant
  * Add laravel/homestead box. (Manual download recommended.)
  * Clone Homestead repo & follow setup instructions.
  * Map api to /path/to/api in folders [ ~/.homestead/Homestead.yaml ], with "nfs" enabled.
  * Map api.razorpay.in to /path/to/api/public in sites [ ~/.homestead/Homestead.yaml ]
  * Follow Common Instructions
  * Follow Common Test Setup Instructions


  ### 3.a( Local - Ubuntu )

  * Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/` and update the directory location where your project lies.
  * `sudo a2ensite api.razorpay.com.conf`
  * Install redis ( arch-linux : `pacman -S redis`) and enable its service (`systemctl enable --now redis.service`)
  * Follow Common Instructions
  * Follow Common Test Setup Instructions

  ### 3.b( Local - OSX )
  *  Copy the contents of `api.razorpay.com.conf` to `httpd-vhosts.conf` and update the directory location where your project lies.
  * Follow Common Instructions
  * Follow Common Test Setup Instructions
  * Install coreutils `brew install coreutils --with-default-names`
  * ~~Create a symbolic link for date util `sudo ln -s /usr/local/opt/coreutils/libexec/gnubin/date /usr/bin/date`~~

  ### 4. Devstack

  * [Refer Devstack](readme-devstack.md)

### Common Instruction

*PHP*: Please make sure you have the `gmp`, `bcmath` extensions installed. This is on top of what [laravel requires](https://laravel.com/docs/5.5/installation#server-requirements):

- PHP >= 8.1 and PHP< 8.2

  If PHP is already installed and the version is different than 8.1.xx, do the following:

  1. Check the current version by running `php --version`.
  2. Using homebrew you can downgrade/upgrade your version by running `brew install php@< desired version>`.

  3. You can uninstall your previous php version by `brew uninstall php@< current version>`.
  4. If you don’t want to uninstall, you can unlink the previous version by running `brew unlink php@< version number>`.
  5. After unlinking you can link the version you want to use  by `brew link php@< desired version>`.

For our use, the desired version must be 8.1.XX.

Imp: If you still see some other version installed then you might need to add the php $PATH to your ~/.bash_profile,
for this Visit [this link](https://stitcher.io/blog/php-81-upgrade-mac).

Note: If you face the following error {dyld Library not loaded..} ->
Visit [this link](https://stackoverflow.com/questions/57851117/homebrew-upgrade-drops-php-dyld-library-not-loaded-usr-local-opt-libpsl-lib).
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension

* `chmod -R o+wx storage/`
* `composer install` to install project dependencies # Google online on how to install composer globally.

  If you face `composer unexpected error` after running `composer install` -> Visit [this](https://stackoverflow.com/questions/26691681/composer-unexpectedvalueexception-error-will-trying-to-use-composer-to-install).
* Create 2 databases (one is for live and another for test accounts). (`api_live`, `api_test` are sample names)
* Copy over `environment/.env.sample` to `environment/.env.dev` and provide both database usernames and password
* Copy over `environment/env.sample.php` to `environment/env.php`. This specifies the `dev` environment for local development.
* `php artisan rzp:dbr --install --seed` (Creates tables and seeds them)

   1. If you face `General error: 1273 Unknown collation: 'utf8mb4_0900_ai_ci'` see [this question](https://stackoverflow.com/questions/29916610/1273-unknown-collation-utf8mb4-unicode-ci-cpanel)
* Set up pre-commit hooks - `cp scripts/git-hooks/pre-commit .git/hooks/`
* Install phpcs - http://tedshd.logdown.com/posts/246406-php-install-phpcsphp-codesniffer

**Note**: If you ever called `config:cache` during local development, you can undo this by calling `php artisan config:clear`. Otherwise, you might experience that calling getenv() will not return the desired values.

### Common Test Setup Instructions

* Create three separate databases for testing (separate from the development ones). (sample names: `api_testing_live`, `api_testing_test`, `auth_test`)
* Copy over `environment/.env.sample` to `.env.testing` and provide the database information
* For above step, if you have sqlite set-up. Google how to do that with laravel.
* Install php unit `sudo apt-get install phpunit`
* Disable Xdebug ( Some Tests fail with Xdebug enabled ): `sudo php5dismod xdebug`
* Restart Webserver
* Run `phpunit` in api root. Preferably run as `phpunit --debug` for better view of tests when running manually.
* In case you are on 12.04, see [this question](http://stackoverflow.com/questions/1528717/phpunit-require-once-error) on how to fix the PHPUnit install.
* In case you see this error about missing tables in api_testing database, run the following on api root: `APP_ENV=testing php artisan rzp:dbr --install`
* Run redis-cluster. `make redis-cluster`
* Install `pecl install grpc-1.49.0`

TIP: Change the values of `RUN_FIXTURES` and `RUN_FIXTURES_ONCE` in `.env.testing` file to `false` after running the tests once. This makes sure you don't run fixtures everytime and hence the tests will run faster. If you add/change/delete any fixtures or clear your test db then change them to `true` once, run tests and then change them back to `false`.

### Code Coverage Instructions

* Install `php70-xdebug`
* Run `phpunit --coverage-html [Directory to save coverage]`
* You can also generate coverage in other formats. Visit [PHPUnit CodeCoverage](https://phpunit.de/manual/current/en/code-coverage-analysis.html) for more info.

### Setup git hooks

* Run `cp scripts/git-hooks/pre-commit .git/hooks/`

### Setup crons

* Run `./scripts/crontab.sh` to set up all crons that exist on prod.
* Consider commenting out the ones you don't actually need with `crontab -e`.

### Redis-cluster setup
if you pull master, api will now need redis-cluster to run locally.
* if you use docker setup, and run everything inside  docker, no change is needed.
  just run make build like you always do.
* if you use non-docker setup, then run make redis-cluster to start redis-cluster. (port :7000)
* if you run make build just to setup your local env, and run tests outside of docker,
  then run make redis-cluster after make build.
* if you run tests outside of docker , but use docker web server(0.0.0.0:28080), then uncomment
  last container in docker-compose.dev.yml (https://github.com/razorpay/api/blob/master/docker-compose.dev.yml#L122-L129 ). just run make build after making this change.
* if you get error `No connections available in the pool` in docker setup. run `make build` again.

### Setup distributed tracing

* Set `DISTRIBUTED_TRACING_ENABLED` to 'true' in the respective `environment/.env.<xxx>` file.
* Opencensus lib is already taken care by `composer.json`.
  Install Opencensus extension by running following:

        pear81 config-set php_ini /etc/php81/php.ini
        pecl81 install opencensus-alpha

* Run `jaegertracing/all-in-one:1.18` container on docker.


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


# [Troubleshooting doc](https://docs.google.com/document/d/1TTF0eBD3G38-gVMvujuc8EP4-TmO8XfSH4hKzAih62A/edit?usp=sharing)

Requesting engineers to keep this doc valid by updating the issues you faced when deploying API service, along with solutions.
