# Razorpay API

[![wercker status](https://app.wercker.com/status/1d1fe880039df1e76548e43566a144bb/m "wercker status")](https://app.wercker.com/project/bykey/1d1fe880039df1e76548e43566a144bb) [![Codacy Badge](https://www.codacy.com/project/badge/8d5f8e7504b24c71999d884569725575)](https://www.codacy.com) [![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

## Set up instructions via Docker( for development )


#### Pre-requisites

##### Install PHP, composer
If you have both PHP and composer already installed, go to *Verfiy installation* step.

First install Brew on your MAC

- Setup Brew: `ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"`
- `brew update`
- Install PHP 7.2.+ `brew install php@7.2`
- Finally, install composer: `brew install composer`

###### Verify installation
- Run `$ php -v`, and check if php 7.2+ is picked up. If a lower version is picked up, adjust the path.
     - Unlink any old versions of php using `brew unlink php@<old_version_here>`
     - Link the path to 7.2 `brew link php@7.2`
- Run `php -info | grep -i GMP` to check if `php-gmp` extension is installed. Installing php using the above command gets gmp installed with it. If not, run `$ brew install gmp` and re-run php info command.

###### Debug installation quirks
    1. To debug any issue with any package, you can run `brew info php@<version>`.
    2. If you are getting seemingly unrelated errors, make sure to update bash/zsh: `brew upgrade bash` and `brew upgrade zsh`.
    3. If `brew install php@7.2` fails due to any permission issues, run
        `sudo chown -R $(whoami) <parent dir of problematic directory>` and retry installation.

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

##### Setup docker env vars
create environment/env.php based on environment/env.sample.php
It should return 'dev_docker', for which should be created as environment/.env.dev\_docker (use .env.defaults as template)


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

