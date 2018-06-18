# Razorpay dashboard

The production dashboard uses the following:

- PHP 7.1
- Alpine Linux 3.7

Builds are done using Drone. See the `.drone.yml` file for details on these.

#### Pre-requisites

* Install [composer](https://getcomposer.org/download/) PHP package manager
* Install [`node`](https://github.com/creationix/nvm) (`v6` or above)
* Install [`yarn`](https://yarnpkg.com/en/docs/install)

# Setup instructions with Docker (for local development)

#### Pre-requisites

* Setup Razorpay API locally. Check [Razorpay API Docker](https://github.com/razorpay/api/blob/master/readme-docker.md)
* Once set up, ensure `Docker for mac` is running.

#### Setup Dashboard/Building Container

Now, build the containers:

```
$ make build
```

The above will take care of building a `Containerized dashboard app` from your
local file-system, spin up `mysql:5.6` container and establish connection
to run the app locally.

You should be able to access the app at:
`http://dashboard.razorpay.in:38080/`

#### Shutting down/Pausing the container

```
$ make down
```

#### Bringing the container back after it has been shut down/paused

```
$ make up
```

#### Cleaning up dashboard container images

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
$ make test AT="--filter <mytestname> --stop-on-failure"
```

#### Connecting to mysql:

Available Databases:
* dashboard

```
$ mysql -u root -p -P33306 -h 127.0.0.1 api_live
```

Look at the value of `DB_LIVE_PASSWORD` in `docker-compose.dev.yml` file for the password. You can also use tools like sequelpro etc with the
above configuration. Do note that the mysql port is going to be `23306`.

#### Post Install

* Login to the API database and either seed a new admin user or change an existing one for self. Below describes the later. Look out
  for the user with email `rishabh.pugalia@razorpay.com` and update with your email, name and username etc. Do this
  in both `api_live` and `api_test` dbs. For instance:

```
mysql -u api_user -p -P23306 -h 127.0.0.1
mysql> use api_live;
...
mysql>select * from admins;
....
mysql>update admins set email = 'someemail@razorpay.com', name = 'Some Name', username='someusername' where id = 'some_id';
...
mysql>use api_test;
...
mysql>update admins set email = 'someemail@razorpay.com', name = 'Some Name', username='someusername' where id = 'some_id';

```

* Login to the dashboard `http://dashboard.razorpay.in:38080/admin`

# Docs

To generate documentation for our PHP codebase, run the following:

```bash
curl -L https://github.com/ApiGen/ApiGen/releases/download/v4.1.0/apigen-4.1.0.phar -o apigen && chmod +x apigen
./apigen generate -d ./docs -s ./app
```

The documentation will be generated in the docs directory.
