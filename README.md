# Razorpay dashboard

The production dashboard uses the following:

- PHP 7.1
- Alpine Linux 3.7

Builds are done using Drone. See the `.drone.yml` file for details on these.

# Setup instructions with Devstack (for local development)

### Prerequisites

- For cloning the repo and initial setup follow [PG Dashboard Setup Guide](https://docs.google.com/document/d/1baW2jhf7fSSbTL5OgeSdXWwWVNAdLg675GhtKO0EWgU/edit#heading=h.x3wtpynpvz5b)
- For setting up devstack, follow [Devstack Dev Runbook](https://docs.google.com/document/d/1fc9EKjGi1UcSZIEQ5c6p3r0ns5r6mlmbPCdeDek16Co/edit?usp=sharing)

### Using devstack for Dashboard repo

- For getting onboarded to dashboard devstack and understanding the various use-cases, follow [Dashboard Guide for Development & Testing](https://docs.google.com/document/d/1Wk-SxvuZjPMHToQo3K2jXY0aTpvqXrF8UtuGnIaluds/edit?usp=sharing)

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

```json
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
  "appliesTo": ["script"]
}
```

- Start ecstatic server in **public** folder using following command

```bash
ecstatic --cache 0 -H 'Access-Control-Allow-Origin: *'
```

- Start node server in **web** folder using this command

```bash
STAGE=development REDIRECTOR=true node tools/build.js --project=razorx
```

- Go to https://beta-admin-dashboard.stage.razorpay.in/razorx and turn on redirector.

# Deployment Process

https://docs.google.com/document/d/1__-n3Ap8vLKCBLFt8Z2FyxvpQR9GfkimyEPm3C0Cslo/edit
