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
- Install gmp : `brew install php70-gmp`
- Install `mcrypt`: `brew install mcrypt php70-mcrypt`
- Finally, install composer: `brew install composer`

Now if you run `$ php -v`, you will get `PHP 5.5` or something.
This is the default PHP version that is shipped with OSX and cannot be removed.
You just need to edit your path to ensure that `PHP 7.0` is picked up.

`export PATH="$(brew --prefix homebrew/php/php70)/bin:$PATH"`

To debug any issue with any package, you can run `brew info php70` etc.

Also, if you are getting seemingly unrelated errors, make sure to update bash/zsh: `brew upgrade bash` and `brew upgrade zsh`.

If everything is setup correctly, running `$ php -v` should give you 7.0.+.

Note: We will use the phpunit that comes along with composer. We do not explicitly need phpunit to be installed for the docker setup.

##### Install docker
[Docker installation and Hello World!](https://docs.docker.com/engine/getstarted/step_one/)

##### Mac users
* Please use `Docker for Mac` and do not use `Docker Toolbox for the Mac`
* Set the Docker memory to 4GB and number of cpus to 3

##### Linux users
* sudo apt-get install docker
* pip install docker-compose
* sudo usermod -aG docker $(whoami) # Adds yourself to docker group

Now Log out and log back in once after last step.

##### Install docker-compose
[Install Docker Compose](https://docs.docker.com/compose/install/)

##### Login to Dockerhub
Ensure that you have a dockerhub user that is added to the Razorpay Organization.
 - Admin Contact: `nemo@razorpay.com`

###### Mac Users
Run Docker for Mac while signed-in as this user.

###### Linux Users
use the `docker login` command to sign-in with the aforementioned dockerhub user.

## Run docker-compose
[Create a github PAT](https://help.github.com/articles/creating-an-access-token-for-command-line-use/), if you do not have one.

```
GIT_TOKEN=<PAT>
export GIT_TOKEN
```
or,
add it to your `.bashrc`/`.bash_profile`

##### Optional configurations
Note: By default API will run on port 28080 and mysql on 23306. In case you wish to change these params or other ports like for elasticsearch, please modify `docker-compose.dev.yml`

#### Setup API/Building Container

Note: Docker for Mac suffers from heavy performance implications due to the nature of xhyve fs implementation. We need to optimize our current setup to make sure this can be handled. Hence, please run `make init` before proceeding with the below steps:

```
$ make init
```
[Optional Step] : If this fails saying certain files are missing, you can add the folloring in your docker container location :
```
cd <PATH_TO_CONTAINERS>/Containers/com.docker.docker/Data/database/com.docker.driver.amd64-linux/
mkdir disk
touch disk/full-sync-on-flush
touch disk/on-flush
```
For Mac Users, PATH_TO_CONTAINERS is by default ~/Library/

Now, build the containers:

```
$ make build
```

The above will take care of building a `Containerized api app` from your
local file-system, spin up `mysql:5.6` container and establish connection
to run the app locally.

You should be able to access the app at:
`http://api.razorpay.in:28080/`

#### Shutting down/Pausing the container

```
$ make down
```

#### Bringing the container back after it has been shut down/paused

```
$ make up
```

#### Cleaning up api container images

```
$ make clean
```

#### Cleaning up all container images

```
$ make clean-all
```

#### Notes on running tests
On a vanilla mode, to run all the tests do the following:
```
$ make test
```

If you want to pass in specific params(e.g. -filter PaymentTest or --stop-on-failure etc), do the following:
```
$ make test AT="--filter PaymentTest --stop-on-failure"
```

#### Connecting to mysql:

Available Databases:
* api_live
* api_test
* api_testing_live
* api_testing_test

```
$ mysql -u api_user -p -P23306 -h 127.0.0.1 api_live
```

Look at the value of `DB_LIVE_PASSWORD` in `docker-compose.dev.yml` file for the password. You can also use tools like sequelpro etc with the
above configuration. Do note that the mysql port is going to be `23306`.


#### Containerization Issues

Please file issues regarding Containerization on the local `api`
issue-tracker and tag @razorpay/devops
