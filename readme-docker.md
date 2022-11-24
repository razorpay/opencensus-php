# Razorpay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb) [![Codacy Badge](https://www.codacy.com/project/badge/8d5f8e7504b24c71999d884569725575)](https://www.codacy.com) [![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

## Set up instructions via Docker( for development )


#### Pre-requisites

##### Install PHP, composer
If you have both PHP and composer already installed, go to *Verfiy installation* step.

First install Brew on your MAC

- Setup Brew: `ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"`
- `brew update`
- Install PHP 8.1.+ `brew install php@8.1`
- Finally, install composer 2: `brew install composer`


###### Verify installation
- Run `$ php -v`, and check if php 8.1+ is picked up. If a lower version is picked up, adjust the path.
     - Unlink any old versions of php using `brew unlink php@<old_version_here>`
     - Link the path to 8.1 `brew link php@8.1`
- Run `php -info | grep -i GMP` to check if `php-gmp` extension is installed. Installing php using the above command gets gmp installed with it. If not, run `$ brew install gmp` and re-run php info command.
- Run `php -info | grep -i rdkafka` to check if extension of kafka is installed. If not, run `brew install librdkafka` and then `sudo pecl install rdkafka`.

###### Debug installation quirks
    1. To debug any issue with any package, you can run `brew info php@<version>`.
    2. If you are getting seemingly unrelated errors, make sure to update bash/zsh: `brew upgrade bash` and `brew upgrade zsh`.
    3. If `brew install php@8.1` fails due to any permission issues, run
        `sudo chown -R $(whoami) <parent dir of problematic directory>` and retry installation.
    4. If you are getting error `PHP Startup: Unable to load dynamic library 'rdkafka.so'` then first get the location of `rdkafka.so` file by running command
        `find / -name "rdkafka.so" -print -quit 2>/dev/null`, then move this file to dir that is set as `ext_dir` which you can get by running `pecl config-show | grep 'ext_dir'`.

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

##### Login to Harbor
1. Go to harbor.razorpay.com and login via OIDC provider
2. On the page, go to top right hand corner and click on your user profile
3. Copy user name and password
4. Do a docker login on your terminal with `docker login c.rzp.io`

Refer Docker Local Login section here https://docs.google.com/document/d/1VFUAftTGptxRJ2od6_UbNSdNi4Obo_b2ZNlcv90D1jI


###### Mac Users
Run Docker for Mac while signed-in as this user. If it still says access denied while pulling docker images, login via the console as well using `docker login`.

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
local file-system, spin up `mysql:5.7` container and establish connection
to run the app locally.

You should be able to access the app at:
`http://api.razorpay.in:28080/`

In case you usually run `make build` for test setup and run tests outside of api container, then
redis-cluster which spins up by default won't work. It returns the docker's internal IPs for nodes which are not
accessible outside of docker. For this you need to run one extra cmd `make redis-cluster`. This will
stop the redis cluster started by docker-compose and run another redis-cluster which returns node IPs of host.
This also means now your server running inside docker can't access rest of nodes of redis-cluster since they now return host IPs.

In case you want to run tests outside of docker & server inside docker, then you should uncomment last container in docker-compose.dev.yml
After doing so, `make build` will start 2 redis-clusters running in docker setup. one just for running tests (accessible outside of docker @ 0.0.0.0:7000) & one for serving web requests.

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

#### Running tests from PHPStorm

1. Make sure the `version` in docker-compose.dev.yml is set to 2.2 or above
2. Follow the instructions of changing Dockerfile.dev/docker-compose.dev.yml to enable XDebug
3. Go to the Edit Configurations dropdown, just left to the green play button on top right of PHPStorm
4. Select Templates -> PHPUnit
5. In the Preferred Coverage Engine, select XDebug
6. In the Interpreter Section, click the `…` to create a new interpreter config with the below config
    ```
    Name : api
    Server : Docker
    Configuration file(s) : ./docker-compose.dev.yml
    Service : api
    Environment Variables : APP_ENV=testing_docker
    Lifecycle : Connect to existing container
    PHP Executable : php
    ```
7. Once the above config is created, use this newly created config as the Interpreter
8. Your Dockerfile.dev should have the following changes, above EXPOSE 80 line
    ```
    RUN apk add --no-cache php7-xdebug
    RUN echo 'zend_extension=/usr/lib/php81/modules/xdebug.so' > /etc/php81/conf.d/xdebug.ini
    RUN echo 'xdebug.client_port=9000' >> /etc/php81/conf.d/xdebug.ini
    RUN echo 'xdebug.mode=debug' >> /etc/php81/conf.d/xdebug.ini
    RUN echo 'xdebug.discover_client_host=0' >> /etc/php81/conf.d/xdebug.ini
    RUN echo "xdebug.start_with_request=off" >> /etc/php81/conf.d/xdebug.ini
    RUN echo "xdebug.idekey=PHPSTORM" >> /etc/php81/conf.d/xdebug.ini
    ```
9. Your docker-compose.dev.yml should have the following ENV variables
    ```
    XDEBUG_CONFIG: remote_host=docker.for.mac.host.internal
    PHP_IDE_CONFIG: serverName=Docker
    ```
10. You will see a green play button to the left of your tests, just click it, PHPStorm will run your tests.

##### Optional
 Add the following to your `.env.testing_docker`:
```
RUN_FIXTURES                                   = (true)
RUN_FIXTURES_ONCE                              = (true)
TRUNCATE_DATABASE                              = (true)
```
After running one test, you can edit them all to false. This will speed up test execution on your pc.
###### NOTE:
* You need to make these 3 variables `true` everytime you run `make build`. Then, just run one test, and then make them `false` again.
* Or, you can leave them all true. This will slow down the first test, but still speed up the rest of the tests on your pc.
* To speed up tests further refer: [Doc Link](https://docs.google.com/document/d/1H7RHIJ-od7sHk3FXZCy7QeTPPpFoQpUywc91U6ApPVg/edit)



#### Connecting to mysql:

Available Databases:
* api_live
* api_test
* api_testing_live
* api_testing_test

Note: you need to SSH into the mysql api docker container, or connect to the IP address of this same container.
The port is 3306 when you are SSHing

```
$ mysql -u api_user -p -P23306 -h 127.0.0.1 api_live
```

Look at the value of `DB_LIVE_PASSWORD` in `docker-compose.dev.yml` file for the password. You can also use tools like sequelpro etc with the
above configuration. Do note that the mysql port is going to be `23306`.


#### Containerization Issues

Please file issues regarding Containerization on the local `api`
issue-tracker and tag @razorpay/devops


#### Steps for Upgrading from PHP 7.2 to 8.1
```
1. Install php 8.1 and link to the newer version
    a. brew install php@8.1
    b. Unlink any old versions of php using brew unlink php@<old_version_here>
    c. Link the path to 8.1 brew link php@8.1
2. Upgrade composer (composer self-update --2)
3. Run command  composer update -W to get the latest vendors (one time)
4. Run make build after adding your GIT_TOKEN in docker-compose.dev.yml
5. Replace the content of .env.dev_docker form here
6. Try running the test case(docker exec --env APP_ENV=testing_docker -it razorpay-api vendor/bin/phpunit --filter <test case name>)
```
PS : Ref to [slack thread](https://razorpay.slack.com/archives/C0434UWEE4U/p1665561435959949) for known failures and fixes.
