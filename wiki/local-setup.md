<header>
  <h1 align="center">Local Setup</h1>
  <details>
    <summary align="center"><b>Changelog</b></summary>

| Version | Date         | Creators             | Reviewers | Remarks                               |
| ------- | ------------ | -------------------- | --------- | ------------------------------------- |
| 1.0     | Dec 15, 2020 | `Sandesh Damkondwar` |           | Initial Draft                         |
| 2.0     | Dec 21, 2022 | `Ritesh Ganjewala`   |           | Segregate Backend and Frontend Setups |

  </details>
</header>

The scope of this document is for local setup only. If you have already done the local setup and want to use the development environment, check out [using the devstack](./dashboard-devstack.md) guide.

- [1. Prerequisites](#1-prerequisites)
- [2. Common Setup](#2-common-setup)
- [3. Two sides of a coin](#3-two-sides-of-a-coin)
- [4. For Frontend](#4-for-frontend)
- [5. For Backend](#5-for-backend)

### 1. Prerequisites

**1.1. Generate GitHub Token**

- Generate a Personal Access Token on GitHub by visiting this link
- Check all the scopes
- Click on Generate token
- Copy the token.
- From Enable SSO, click the Authorize button next to the Razorpay logo.
- Add the generated GitHub token in your `~/.bashrc` or `~/.zshrc` (keep this token handy, we will need it during backend setup)

  ```bash
  # At the end of your ~/.bashrc or ~/.zshrc file

  export GITHUB_ACCESS_TOKEN="<YOUR_TOKEN>"
  ```

- Run `source ~/.bashrc` or `source ~/.zshrc` based on the file you added your token.

**1.2. Generate NPM Token**

- Open <https://registry.razorpay.com/>.
- Click `Login with GitHub`
- Click on the info icon in the header to see the token or run the below command on the chrome dev console to get the token

  ```js
  localStorage.getItem('npm');
  ```

- Run `source ~/.bashrc` or `source ~/.zshrc` based on the file where you added your npm token.

### 2. Common Setup

**2.1. Clone the dashboard repo**

```bash
git clone git@github.com:razorpay/dashboard.git
```

**2.2. Setup Environment Variables**

- Change `environment/env.php` to this, if the file does not exist then create this file -

  ```php
  <?php

  return 'dev';
  ```

- Create a new file `environment/.env.dev` and add the below content

  ```
  APP_ENV=func
  SHIELD_STAGE=staging
  # make it to true if you want to see laravel debugbar

  APP_DEBUG=false
  APP_KEY=base64:9ns2+1GeO0Zu1RrFUSxlYhJ84kGf750OgThB3kie6kY=
  APP_LOG=daily

  CACHE_DRIVER=redis
  REDIS_HOST=127.0.0.1
  REDIS_PASSWORD=null
  REDIS_PORT=6379

  # BASE_URL='http://dashboard.razorpay.in:38080'

  QUEUE_DRIVER='sync'
  AWS_QUEUE_URL=''

  # Experimental

  # SERVER_NAME="app.razorpay.com"

  API_URL="https://api-6.func.razorpay.in/v1/"
  API_AUTH_PASS="DASHBOARD_AUTH_PASS_STAGING"

  API_MOCK=false

  API_GUEST_AUTH_PASS = 98ecf73943943453ec52b64d65563933d2b4142cd605144043026a833cd3fd06

  # AWS Bucket for storing activation documents uploaded

  AWS_ACTIVATION_BUCKET=rzp-1018-nonprod-qa-common

  # Region to be used just for buckets

  AWS_BUCKET_REGION=ap-south-1

  #Your AWS Access Key IDg
  AWS_ACCESS_KEY_ID=(empty)

  # Your AWS Secret Access Key

  AWS_SECRET_ACCESS_KEY=(empty)

  AWS_REGION=ap-south-1
  S3_MOCK=true

  # Should mail be faked, set true in testing/development See mail.php for details

  MAIL_PRETEND=true
  MAIL_DRIVER=mailgun

  # Should contact form submit details be sent to slack

  # Set false in testing/development

  # This is also used for Sorting Hat integration

  # Slack Key is not used for Sorting Hat

  SLACK_ENABLE=false
  SLACK_KEY=''

  NOCAPTCHA_SECRET=''

  # Creevey is the creenshot service

  CREEVEY_TOKEN='token_for_creevey'
  CREEVEY_MOCK=true

  MAILCHIMP_LIST_ID=list_id
  MAILCHIMP_API_TOKEN=xxxx-yyyy-zzzz
  MAILCHIMP_MOCK=true

  ZAPIER_MOCK=true
  CRON_PASS='RANDOM_CRON_PASS'

  SECURE_SESSION=(false)

  OAUTH_MOCK=false

  # The following ID/Secret work for dashboard.razorpay.in/admin. Copy these to your env.dev and use

  # for a development environment.

  OAUTH_CLIENT_ID = dummy
  OAUTH_CLIENT_SECRET = dummy

  AUTH_SERVICE_URL = https://auth.func.razorpay.in
  AUTH_SERVICE_PASS = dummy

  CDN_DASHBOARD_URL=''

  ENCRYPTION_CIPHER='AES-256-CBC'
  LJ_KEY='6z9XZ6qi4zo6lBu082ptIMAHg6XlOHJS'

  # client id for google auth in merchant dashboard

  MERCHANT_OAUTH_CLIENT_ID='551184003361-itmg24ie0j0ar24g9u2p9pveq30ri0qo.apps.googleusercontent.com'

  MERCHANT_OAUTH_CLIENT_ID_EPOS=''
  MERCHANT_OAUTH_CLIENT_ID_ANDROID=''
  MERCHANT_OAUTH_CLIENT_ID_IOS=''

  GRAPHQL_SERVER_URL='http://localhost:8888/'
  ```

### 3. Two sides of a coin

There are different setups for frontend and backend, you can follow either of them based on what you want to setup and that should be enough _or if you are curious enough, just go ahead and do both._

![](https://i.redd.it/v69pvxl9bga41.jpg 'Adieu Friend')

### 4. For Frontend

- If you are currently using yarn as package manager, please refer to this migration guide to setup PNPM
  [yarn to pnpm Migration Guide](https://docs.google.com/document/d/1j1Ct-n8fvDAKMvKNrnvJ9Wf2M4svCMIvu6B07l9FqLQ/edit?usp=sharing)

**4.1. Install nodejs**

- Visit <https://nodejs.org/en/> to download and install node.

**4.2. Install pnpm**

- Run this in your terminal (this will be required for installing commit hooks) -

  ```sh
  sudo npm i -g pnpm
  ```

**4.3. Install dependencies**

> **AVOID** running npm install until and unless you are adding new packages or updating it.

- Run this in your dashboard root directory -

  ```sh
  pnpm || pnpm install --frozen-lockfile
  ```

**4.4. Start bundling the FE source**

- This builds the static folder, generates the code and keeps it in public/dist. Run this in your dashboard root directory -

  ```
  pnpm start
  ```

  **Phew!!**
  That was a lot of work, right? Yeah but that's not it. Follow this guide to create your personal dev environment and see your code running. See you there buddy

### 5. For Backend

**5.1. Install PHP**

- For installing PHP on your machine, run these commands on your terminal -

  ```sh
  brew install php@7.2
  brew link php@7.2 --force # to forcefully override local php
  brew services start php@7.2 # php@7.3 if 7.3 is installed
  ```

**5.2. Install composer**

- For installing composer, run these commands on your terminal -

  ```sh
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

  php composer-setup.php --filename=composer

  rm composer-setup.php

  mv composer /usr/local/bin/composer
  ```

**5.3. Install PHP dependencies**

- Run this in your dashboard root directory -

  ```sh
  composer install
  ```

- Paste the GitHub token when prompted while running the composer install.

**5.4. Permission setup**

- Give permission to `storage` folder recursively

  ```sh
  sudo chown -R $USER storage
  ```

**5.5. Redis setup**

- This will help in caching the data. This will surely help in running things faster on a local machine. This step is optional but recommended to install and start the Redis server before running the PHP server

  ```sh
  brew install redis
  brew services start redis

  # start the redis server if not started
  redis-server
  ```

**5.6. Laravel app key setup**

- Generate auth key for the laravel

  ```sh
  php artisan key:generate
  ```

- Replace the generated app key in `environment/.env.dev` -

  ```
  APP_KEY=base64:9ns2+11234512345123451234512345ThB3kie6kY=
  ```

**5.7. Start the dashboard backend server**

- This will start your backend server on localhost:8000.

  ```sh
  php artisan serve
  ```

- To start the server on port 80: (optional)

  ```sh
  sudo php artisan serve --port 80
  ```

**5.8. Setup the domain (optional)**

- Add [dashboard.razorpay.in](http://dashboard.razorpay.in) in /etc/hosts

  ```
  127.0.0.1 dashboard.razorpay.in
  ```

  Now you can consume frontend on localhost:8000 and [dashboard.razorpay.in:8000](http://dashboard.razorpay.in:8000)

**5.9. Login using the following credentials**

```
Email: test@razorpay.com
Password: r@zorp@y123
```
