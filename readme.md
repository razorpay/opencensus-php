# Razorpay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb) [![Codacy Badge](https://www.codacy.com/project/badge/8d5f8e7504b24c71999d884569725575)](https://www.codacy.com) [![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

## Set up instructions ( for development )

#### Pre-requisites

* Install [composer](https://getcomposer.org/download/) PHP package manager

### ( Vagrant / Homestead )

* Install Virtualbox & Vagrant
* Add laravel/homestead box. (Manual download recommended.)
* Clone Homestead repo & follow setup instructions.
* Map api to /path/to/api in folders [ ~/.homestead/Homestead.yaml ], with "nfs" enabled.
* Map api.razorpay.dev to /path/to/api/public in sites [ ~/.homestead/Homestead.yaml ]
* Follow Common Instructions
* Follow Common Test Setup Instructions

### (Using Docker for Local Development)
## Pre setup instructions
* Download phpunit.phar file in the root api folder from https://phar.phpunit.de/phpunit.phar
* symlink phpunit.phar to phpunit in the root api folder:
```
ln -s phpunit-5.7.9.phar phpunit
```

## Common Instructions for docker

## Install docker
[Docker installation and Hello World!](https://docs.docker.com/engine/getstarted/step_one/)

## Mac users
* Please use `Docker for Mac` and do not use `Docker Toolbox for the Mac`
* Increase Docker memory to 6GB and number of cpus to 4

## Install docker-compose
[Install Docker Compose](https://docs.docker.com/compose/install/)

## Run docker-compose
[Create a github PAT](https://help.github.com/articles/creating-an-access-token-for-command-line-use/), if you do not have one.

```
GIT_TOKEN=<PAT>
export GIT_TOKEN
```
or,
add it to your `.bashrc`/`.bash_profile`

## Using Docker for Local Development
Note: Change the http port/mysql port in the `docker-compose.dev.yml` before doing the following
```
docker-compose -f docker-compose.dev.yml up -d --build
```

Check that docker actually works:
```
docker ps
```
See that your container actually works using the above.

You should be able to access the app at:
http://localhost:28080/ (or any other port that you have changed in `docker-compose.dev.yml`)

## For Deploying docker containers
First run (or, if code has changed):
```
docker-compose up -d --build
```

Subsequent runs:
```
docker-compose up
```

to take down your local cluster,
```
docker-compose down
```

in case you want to delete the existing containers
```
docker-compose rm
```

in case you want to delete all the containers and the corresponding images
```
docker rm $(docker ps -a -q)
docker rmi $(docker images -q -a)
rm ~/Library/Containers/com.docker.docker/Data/com.docker.driver.amd64-linux/Docker.qcow2
```

The above will take care of building a `Containerized api app` from your
local file-system, spin up `mysql:5.6` container and establish connection
to run the app locally.

You should be able to access the app at:
http://api.razorpay.dev:28080/

## Running unit tests using dockerized containers
```
docker exec api_api_1 /app/phpunit --debug
```

Note: the name api_api_1 can be got from `docker ps` command

## Containerization Issues

Please file issues regarding Containerization on the local `api`
issue-tracker and tag @razorpay/devops

### ( Ubuntu )

* Copy over `api.razorpay.com.conf` to `/etc/apache2/sites-available/` and update the directory location where your project lies.
* `sudo a2ensite api.razorpay.com.conf`
* Follow Common Instructions
* Follow Common Test Setup Instructions

### ( OSX )
*  Copy the contents of `api.razorpay.com.conf` to `httpd-vhosts.conf` and update the directory location where your project lies.
*  In `/etc/hosts`, add `api.razorpay.dev` to the list of domains that loopback to your own machine.
* Follow Common Instructions
* Follow Common Test Setup Instructions
* Install coreutils `brew install coreutils --with-default-names`
* ~~Create a symbolic link for date util `sudo ln -s /usr/local/opt/coreutils/libexec/gnubin/date /usr/bin/date`~~

### Common Instruction

* `chmod -R o+wx storage/`
* `php composer.phar install` to install project dependencies
* Create 2 databases (one is for live and another for test accounts). (`api_live`, `api_test` are sample names)
* Copy over `environment/.env.sample` to `environment/.env.dev` and provide both database usernames and password
* Copy over `environment/env.sample.php` to `environment/env.php`. This specifies the `dev` environment for local development.
* `php artisan rzp:dbr --install --seed` (Creates tables and seeds them)
* Set up pre-commit hooks - `cp scripts/git-hooks/pre-commit .git/hooks/`
* Install phpcs - http://tedshd.logdown.com/posts/246406-php-install-phpcsphp-codesniffer

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

### Setup git hooks

* Run `cp scripts/git-hooks/pre-commit .git/hooks/`

### Setup crons

* Run `./scripts/crontab.sh` to set up all crons that exist on prod.
* Consider commenting out the ones you don't actually need with `crontab -e`.

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
