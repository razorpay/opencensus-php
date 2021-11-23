# Razorpay API

[![Documentation Link](https://img.shields.io/badge/docs-api-orange.svg)](https://cc.razorpay.com/api/docs/index.html)

## Set up instructions via Devstack( for development )

- [Refer devstack runbook](https://docs.google.com/document/d/1fc9EKjGi1UcSZIEQ5c6p3r0ns5r6mlmbPCdeDek16Co/edit#heading=h.5l8ydtht277p)

## Setup remote debugger with XDebug2

```
NOTE: This guide is only for PHPSTORM IDE
```

- [Video Insructions available here](https://razorpay.slack.com/archives/C024Y4GAW8N/p1635921707136200?thread_ts=1634804876.002200&cid=C024Y4GAW8N)

1. Add the following config to your [devspace.yaml](./devspace.yaml) inside `dev`

  ```yaml
  replacePods:
    - labelSelector:
        name: ${APP_NAME}-${DEVSTACK_LABEL}
      replaceImage: c.rzp.io/razorpay/api:devstack
      namespace: ${NAMESPACE}
  ports:
    - labelSelector:
        name: ${APP_NAME}-${DEVSTACK_LABEL}
      namespace: ${NAMESPACE}
      reverseForward:
        - port: 9000
          remotePort: 9000
  ```
[Refer sample yaml config](https://github.com/razorpay/api/blob/7b1b3049efc5ea9e6f58a25dd34717a99dc5a522/devspace.yaml)

2. Ensure your IDE is listening to port 9000 by -
   Preferences>PHP>Debug>Xdebug>Xdebug ports
3. Turn on `Start listening for PHP Debug connections` (telephone button on top nav bar)
4. Run `devspace dev --no-warn`
5. Hit any API endpoint
6. You will receive an incoming debug connection. Please note the server name(`$YOUR_SERVER_NAME`). Press accept.
7. Go to Preferences>PHP>Server>$YOUR_SERVER_NAME
8. In the Files Directory, ensure root of the project file is mapped to /app. Click `Apply` and `OK` to save changes.
9. You have successfully integrated Xdebug with devstack

### Solving common issues while using devstack
[Refer doc](https://docs.google.com/document/d/1W3G1RrE7ai57K0vPNC0d8bU7vTbhj9S4jA9DPD7BzgA/edit#)

```
NOTE: Please reach out to #platfrom-devstack if you face any issues
```

