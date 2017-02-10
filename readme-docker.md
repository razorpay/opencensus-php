# Razorpay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb) [![Codacy Badge](https://www.codacy.com/project/badge/8d5f8e7504b24c71999d884569725575)](https://www.codacy.com) [![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

## Set up instructions via Docker( for development )


#### Pre-requisites

##### Install [composer](https://getcomposer.org/download/), php7 and phpunit

First install Brew on your MAC

- Setup Brew: `ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"`
- `brew update`
- `brew tap homebrew/dupes`
- `brew tap homebrew/php`
- Install PHP 7.0.+ `brew install php70`
- Install `mcrypt`: `brew install mcrypt php70-mcrypt`
- Finally, install composer: `brew install composer`

Now if you run `$ php -v`, you will get `PHP 5.5` or something.
This is the default PHP version that is shipped with OSX and cannot be removed.
You just need to edit your path to ensure that `PHP 7.0` is picked up.

`export PATH="$(brew --prefix homebrew/php/php70)/bin:$PATH"`

To debug any issue with any package, you can run `brew info php70` etc.

Also, if you are getting seemingly unrelated errors, make sure to update bash/zsh: `brew upgrade bash` and `brew upgrade zsh`.

If everything is setup correctly, running `$ php -v` should give you 7.0.+.

To install phpunit, either follow the instructions [here](http://www.newmediacampaigns.com/page/install-pear-phpunit-xdebug-on-macosx-snow-leopard)
or simply download phpunit.phar and symlink it as follows:

```
$ curl -o phpunit-5.6.phar https://phar.phpunit.de/phpunit-5.6.0.phar
$ chmod 755 phpunit-5.6.phar
$ mv phpunit-5.6.phar /usr/local/bin
$ ln -s /usr/local/bin/phpunit-5.6.phar phpunit
```

##### Install docker
[Docker installation and Hello World!](https://docs.docker.com/engine/getstarted/step_one/)

##### Mac users
* Please use `Docker for Mac` and do not use `Docker Toolbox for the Mac`
* Increase Docker memory to 6GB and number of cpus to 4

##### Linux users
* sudo apt-get install docker
* pip install docker-compose
* sudo usermod -aG docker $(whoami) # Adds yourself to docker group

Now Log out and log back in once after last step.

##### Install docker-compose
[Install Docker Compose](https://docs.docker.com/compose/install/)

## Run docker-compose
[Create a github PAT](https://help.github.com/articles/creating-an-access-token-for-command-line-use/), if you do not have one.

```
GIT_TOKEN=<PAT>
export GIT_TOKEN
```
or,
add it to your `.bashrc`/`.bash_profile`

##### Optional configurations
Note: By default API will run on port 28080 and mysql on 23306. In case you wish to change these params or other ports like for elastic search, please modify `docker-compose.dev.yml`

#### Setup API/Building Container

```
$ make build
```

The above will take care of building a `Containerized api app` from your
local file-system, spin up `mysql:5.6` container and establish connection
to run the app locally.

You should be able to access the app at:
`http://api.razorpay.dev:28080/`

#### Shutting down the container

```
$ make down
```

#### Bringing the container back after it has been shut down

```
$ make down
```

#### Cleaning up all the container images

```
$ make clean
```

#### Running unit tests using dockerized containers
```
$ docker exec api_api_1 /app/phpunit --debug
```

Note: the name api_api_1 can be got from `docker ps` command

#### Containerization Issues

Please file issues regarding Containerization on the local `api`
issue-tracker and tag @razorpay/devops





