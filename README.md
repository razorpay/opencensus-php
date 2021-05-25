# Razorpay dashboard

The production dashboard uses the following:

* PHP 7.1
* Alpine Linux 3.7

Builds are done using Drone. See the `.drone.yml` file for details on these.

### Pre-requisites

* Install [composer](https://getcomposer.org/download/) PHP package manager
* Install [`node`](https://github.com/creationix/nvm) (`v12`)

Before running `npm install`, we have to setup some tokens to be able to fetch packages from our private registries-

**Setup for private packages from [Razorpay Registry](https://registry.razorpay.com/)** -
- Login to [registry.razorpay.com](https://registry.razorpay.com/)
- Copy the token. You'll see this on the screen
`npm config set //registry.razorpay.com/:_authToken "LONG_HASHED_TOKEN_STRING"` the string after `authToken` is your token
- Open `~/.bashrc` or `~/.zshrc` in your editor and add this line 
  ```
  export NPM_RAZORPAY_TOKEN="<YOUR_TOKEN>"
  ```
- Run `source ~/.bashrc` or `source ~/.zshrc` based on in what file you added your token.

**Setup for private packages from [Razorpay's GitHub Registry](https://github.com/orgs/razorpay/packages)** -

* Generate a Personal Access Token on GitHub by [visiting this link](https://github.com/settings/tokens/new)
  - Check all the scopes
  - Click on Generate token
  - Copy the token.
  - From Enable SSO, click `Authorize` button next to Razorpay logo.
* Open `~/.bashrc` or `~/.zshrc` in your editor and add this line 
  ```
  export GITHUB_ACCESS_TOKEN="<YOUR_TOKEN>"
  ```
* Run `source ~/.bashrc` or `source ~/.zshrc` based on the file you added your token.

# Setup instructions with Docker (for local development)

#### Pre-requisites

* Setup Razorpay API locally. Check [Razorpay API Docker](https://github.com/razorpay/api/blob/master/readme-docker.md)
* Once set up, ensure `Docker for mac` is running.

#### Setup docker env vars

* Create `environment/env.php` based on `environment/env.sample.php` making it return 'dev_docker'
* Create `enviroment/.env.dev_docker` using `environment/.env.defaults` as the template

Note - if you are going to be using RazorpayX, then configure BANKING_SERVICE_URL with the URL of your local RazorpayX to the env to allow CORS.

#### Setup Dashboard/Building Container

Now, build the containers:

```
$ make build
```

The above will take care of building a `Containerized dashboard app` from your
local file-system, spin up `mysql:5.6` container and establish connection
to run the app locally.

Once this is done, run `npm install` in the main folder followed by `npm run build`.

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

#### Without Docker Local Dashboard setup (only for apache 2.4 and node 8)

follow apache setup if setting up for first time 
[apche_setup.txt](https://github.com/razorpay/dashboard/files/3431506/apche_setup.txt)

Change the permission of `dashboard/storage`

```
$ sudo chmod -R o+wx storage/
```

Copy over `environment/env.sample.php` to `environment/env.php`

Copy `environment/.env.example` to `environment/.env.dev` and edit it accordingly, sample 
[env.dev.txt](https://github.com/razorpay/dashboard/files/3428395/env.dev.txt)

Make sure `SECURE_SESSION=false` in `.env.dev`

Run `composer install` to install laravel

Run `php artisan migrate --seed` to migrate and seed the db. If you face problem regarding null fields, urn off strict SQL mode.
Make sure you have redis installed by running the following (used for session management and caching).

```
$ brew install nvm
```

```
$ brew services restart redis
```

Make sure you are running the node 12 using [`nvm`](https://github.com/nvm-sh/nvm)

```
$ nvm install 12.18.2
```
Start node server to serve merchant dashboard

```
$ npm start

```

Start node server to serve all dashboards (merchant, merchantLA, pokedex, razorx, newAuth) 

```
$ cd web && npm run start:all

```
Start node server to serve a specific dashboard in dev mode

```
$ cd web && STAGE=development npm run build:{dashboard}

```

if you don't have api key generated in `.env.dev` file, run following in dashboard folder to generate api key  [more details](https://stackoverflow.com/questions/33700580/laravel-5-application-key)

```
$ php artisan key:generate
```

Setup the following integrations in your editor:	local file-system, spin up `mysql:5.7` container and establish connection
[editorconfig](http://editorconfig.org/#download)	to run the app locally.
[prettier](https://github.com/prettier/prettier#editor-integration). The config is documented in `package.json`. We use `--single-quote` and enable semicolons.	

Open <http://dashboard.razorpay.in> and login as `test@razorpay.com/123456`.

#### Connecting to mysql:

You need to connect to the API DB and not dashboard DB. Either SSH into the API DB container or connect externally. If you are SSHing, the port is 3306

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

Look at the value `MYSQL_PASSWORD` inside `docker-compose-dev.yml` in API's repository to get the password.

* Login to the dashboard `http://dashboard.razorpay.in:38080/admin`

# Docs

To generate documentation for our PHP codebase, run the following:

```bash
curl -L https://github.com/ApiGen/ApiGen/releases/download/v4.1.0/apigen-4.1.0.phar -o apigen && chmod +x apigen
./apigen generate -d ./docs -s ./app
```


The documentation will be generated in the docs directory.

# RazorX / Splitz dashboard setup

To run RazorX / Splitz dashboard you'll need to use Redirector extension.
- Create following rule in redirector
```
{
  "description": "",
  "exampleUrl": "https://betacdn.np.razorpay.in/dashboard/dist/razorx-entry.js",
  "exampleResult": "http://localhost:8000/dist/razorx-entry.js",
  "error": null,
  "includePattern": "https://*cdn.np.razorpay.in/dashboard/dist/razorx-entry.js",
  "excludePattern": "",
  "patternDesc": "",
  "redirectUrl": "http://localhost:8000/dist/razorx-entry.js",
  "patternType": "W",
  "processMatches": "noProcessing",
  "disabled": false,
  "grouped": false,
  "appliesTo": [
      "script"
  ]
}
```
- Start ecstatic server in **public** folder using following command
```
ecstatic --cache 0 -H 'Access-Control-Allow-Origin: *'
```
- Start node server in **web** folder using this command ```STAGE=development REDIRECTOR=true node tools/build.js --project=razorx```
- Go to https://beta-admin-dashboard.stage.razorpay.in/razorx and turn on redirector.

