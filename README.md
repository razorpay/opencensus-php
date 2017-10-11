# Razorpay dashboard

#### Pre-requisites

* Install [composer](https://getcomposer.org/download/) PHP package manager
* Install [`node`](https://github.com/creationix/nvm) (`v6` or above)
* Install [`yarn`](https://yarnpkg.com/en/docs/install)

## Set up instructions for development

* Instructions for setup via docker are available at README-docker.md
*  Copy over `dashboard.razorpay.in.conf` to `/etc/apache2/sites-available/`.
*  Edit the vhost to point to correct directory
* `sudo a2ensite dashboard.razorpay.in.conf`
* `sudo chmod -R o+wx storage/`
* Copy over `environment/env.sample.php` to `environment/env.php`
* Copy `environment/.env.example` to `environment/.env.dev` and edit it accordingly
* Make sure `SECURE_SESSION=false` in `.env.dev`
* Run `composer install` to install laravel
* Run `php artisan migrate --seed` to migrate and seed the db. If you face problem regarding null fields, turn off strict SQL mode.
* Make sure you have redis installed (used for session management and caching).
* Make sure you are running the latest node (only 6 and above are supported)
* Install Yarn if it's not pre-installed (https://yarnpkg.com/en/docs/install)
* `yarn install`
* `yarn global add gulp`
* `gulp`
* `gulp watch`
* Setup the following integrations in your editor:
    - [editorconfig](http://editorconfig.org/#download)
    - [prettier](https://github.com/prettier/prettier#editor-integration). The config is documented in `package.json`. We use `--single-quote` and enable semicolons.

- Open <http://dashboard.razorpay.in> and login as `test@razorpay.com/123456`.
- To sign in as an admin, open <http://dashboard.razorpay.in/admin> after setting OAUTH_MOCK=true in your .env.dev. (If you would like to use the oauth flow in dev environment then add an entry with you razorpay email to admins table in local database or change the code to use any email already in your database.)

## Setup instructions for testing

* Copy `environment/.env.example` to `environment/.env.testing` and edit it accordingly
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

# Docs

To generate documentation for our PHP codebase, run the following:

```bash
curl -L https://github.com/ApiGen/ApiGen/releases/download/v4.1.0/apigen-4.1.0.phar -o apigen && chmod +x apigen
./apigen generate -d ./docs -s ./app
```

The documentation will be generated in the docs directory.
