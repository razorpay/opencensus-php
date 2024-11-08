# POS App

### Overview

This repository serves as a monorepo containing multiple micro apps built using module federation and micro frontends. Each app is designed to perform a specific function and can be deployed and maintained independently. Our app is the POS micro app, which handles the sales-assisted onboarding process for offline merchants. It is built as a Progressive Web App (PWA), allowing easy installation on mobile devices and is built using React, GraphQL, Blade & modular onboarding.

> For more details on Merchant Dashboard microfrontend you can refer [this](https://docs.google.com/document/d/1m266Oi6iInj6soHQhfBjah5iUVklMswtGH7GAynSWjM/edit#heading=h.nulqbc2eje9r).

### Documentation

Please go through the below mentioned links to get a thorough understanding of the product, vision & tech related to POS onboarding.

1. [POS sales assisted onboarding concept note](https://docs.google.com/document/d/1-P2zN3SmWG2D1cO-XIkbW0GakRlWEyPC5ArQgGx6yb8/edit)
2. mWeb [designs](https://www.figma.com/design/CwXk4uVm6RQeBDob4dYcFL/Assisted-Onboarding?node-id=49-17228&node-type=canvas&t=jYdaflVaZPnlsCR1-0) for assisted onboarding
3. Sales assisted onboarding [FE spec](https://docs.google.com/document/d/1wkynwHWOEIkl_EVm2gbMtxt4b2QEdMvEY127z7eRroE/edit#heading=h.nulqbc2eje9r)
4. Sales assisted [BE spec](https://docs.google.com/document/d/1YjVbDhjkZn6RKc3IuFjhwN6OyRu1M-Z4BOe5kmv7Rqo/edit#heading=h.rd7hpjfh29ai)

### KT Videos & Walk-throughs

1. Unified signup & login - Merchant onboarding journey starts [here](https://drive.google.com/drive/u/1/folders/1Uyw3Eb4pYWhCTT2gADcyf7of6GVHk7yO)
2. [POS micro-frontend](https://drive.google.com/file/d/17Qj4IFMMsP5g_NuhtWkpWRC5IddCSGCG/view)
3. [POS self serve user journey](https://drive.google.com/file/d/1l-TmBv62MsHdZl3XgN32qwF-Il2rvXLa/view)
4. [Sales assisted user journey](https://drive.google.com/file/d/1afP8Cglr46dRuefLQSbrRYH7jBQcbPT9/view)
<!-- TODO -->
5. [Sales assisted code walkthrough](https://drive.google.com/file/d/1ZBR4BoKBLO8rs5lneFjLShIwFN3jLfoc/view)

> More about the [omni-acquisition pod](https://docs.google.com/document/d/1PLzqPn_DWev0oqY4LddPipPUI0Jxa2RKkwVgRp8rdV8/edit#heading=h.7cxxnqlbko29)

### Development

The development for POS frontend can be done via Redirector approach or using Devstack.

> The production dashboard is accessible at [dashboard.razorpay.com](https://dashboard.razorpay.com) & [dashboard.dev.razorpay.in](https://dashboard.dev.razorpay.in/app/dashboard) on devstack. The base route for pos app is `/pos-sales`

> For pos assisted onboarding, we have specific user(s) accounts which are dummy sales agents, you will needs these during development. You can reach out to `@omni-acquisition-fe` over slack for creds.

1. ###### Redirector

   1. This approach utilises the redirector chrome extension for development. You can install it from the [chrome web store](https://chromewebstore.google.com/detail/redirector/ocgpenflpmgnfapjedencafcfakcekcd?hl=en&pli=1).
   2. The idea is pretty straightforward. The extension allows us to replace the actual application files in a particular env(prod or staging) with our local build file(s). To make this work, you need to configure redirector to work accordingly.
   3. For the redirector configuration json, you can reach out to `@omni-acquisition-fe` over slack. Just plug it in and you are good to go.
   4. To start your local build, cd into `apps/pos` and run `pnpm start`. To make redirector work, make sure your local build is up & running.

### Testing

This approach assumes you have a good understanding of how devstack works & and the necessary setup to work with Devstack.

1. You will need the following micro services in order to work seamlessly on devstack: Onboarding service, API, pgos, frontend-graphql, dashboard. Incase there is a requirement for document uploads, you might need UFH as well. Deploy the specific commmit for each service you want to.
2. You should deploy all the above mentioned services on a custom label as the first step. As a next step, you can access dashboard on the same custom label on which the above mentioned services were deployed and view/test your changes. For pos sales agent account(s) on devstack, reach out to `@omni-acquisition-fe` over slack.

> Make sure you deploy API with `enable_edge_base:true` & onboarding service with `ephemeral_db: true`

> Everytime onboarding service is re-deployed, please re-deploy pgos as well.

### Deployments

Deployments org wide are done via Spinnaker. You can read more about it [here](https://spinnaker.io/). The pipleline to deploy our pos micro app can be accessed [here](https://deploy.razorpay.com/#/applications/production-dashboard-microfrontend/executions?pipeline=POS%20Microfrontend%20Deployment%20%5B%20Use%20this%20to%20deploy%20pos%20micro-app%20%5D)

### Github workflows

We rely on Github Actions for our day-to-day CI work. You can read about Github Actions [here](https://docs.github.com/en/actions/about-github-actions/understanding-github-actions). Dashboard repo has numerous workflows which you can checkout inside the `.github` folder. The most prominent workflow's are:

1. `build`: Deals with application build(s). Builds the app(shell & micro apps) and pushes the build to s3 in the approriate bucket.
2. `test`: Deals with running application unit tests. Tests only run for affected micro apps.
3. `deploy-*`: This workflow deploys the actual application, is triggered via Spinnaker.
4. `e2e`: The workflow sets up the infra, dependencies & playwright docker image for running e2e's.
5. `validate`: Deals with code level hygiene checks like Linting, TypeScript checks etc.

> For managing operations across the entire dashboard repo, we use `nx`. This tool helps with CI/CD, managing dependencies across micro apps and many other things. You can read more about it [here](https://nx.dev/)

### Alerting & Monitoring

The pos micro app has it's own setup for frontend related alerting & monitoring. We monitor frontend errors, http requests, client side app performance, gql errors and other metrics like Web vital, latency etc. Additionally, we have alerts in place as well to let us know something's wrong within our systems.

1. Frontend error monitoring via Sentry
   - [Issues](https://rzp.sentry.io/issues/?environment=prod&environment=production&project=4507811108028416&statsPeriod=30d)
   - [Sentry alerts rules](https://rzp.sentry.io/alerts/rules/?environment=prod&environment=production&project=4507811108028416&statsPeriod=30d)
2. Monitoring GraphQL queries

   - The queries/mutations within our app are monitored via Apollo studio. Access it [here](https://studio.apollographql.com/graph/razorpay-graph/variant/production/insights?insightsTab=operations). We mostly look for p95, p99 latency/response times alongside error(s). For creds, reach out to `@omni-acquisition-fe` over slack.
   - Additionally, we also utilise Grafana graphs to better visualise the above gathered data. The data for these graphs is pulled from prometheus. Access it [here](https://vajra.razorpay.com/d/01fnfcqIz/pos-assisted-onboarding?orgId=1)
   - Alerts configured so far for our GrapQL queries/mutations, view [here](https://github.com/razorpay/alert-rules/blob/master/rules/prod-rules/graphql_pos_assisted_onboarding_rules.yaml)

### Miscellaneous

- [POS onboarding journey segregation](https://docs.google.com/document/d/1BNEHuGL-VqvxwQZpk4EbFp2hxdqp3JuSjBwVxYKzTdQ)
