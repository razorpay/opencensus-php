# Razorpay dashbaord

# Setup instructions with Docker (for local development)

#### Pre-requisites

* Setup Razorpay API locally. Check [Razorpay API Docker](https://github.com/razorpay/api/blob/master/readme-docker.md)
* Once set up, ensure `Docker for mac` is running.
* Add an entry in `/etc/hosts` for dashboard.razorpay.test to point to localhost
```
127.0.0.1   dashboard.razorpay.test  dashboard.razorpay.test
```

#### Setup Dashboard/Building Container

Now, build the containers:

```
$ make build
```

The above will take care of building a `Containerized dashboard app` from your
local file-system, spin up `mysql:5.6` container and establish connection
to run the app locally.

You should be able to access the app at:
`http://dashboard.razorpay.test:38080/`

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

* Login to the dashboard `http://dashboard.razorpay.test:38080/admin`

#### Containerization Issues

Please file issues regarding Containerization on the local `api`
issue-tracker and tag @razorpay/devops

### TODO

* Instructions to run selenium based tests 
* Instructions to generate docs

