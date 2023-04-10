<header>
  <h1 align="center">HMR Development Guide</h1>
  <details>
    <summary align="center"><b>Changelog</b></summary>

| Version | Date         | Creators           | Reviewers            | Remarks             |
| ------- | ------------ | ------------------ | -------------------- | ------------------- |
| 1.0     | Apr 07, 2023 | `Indra Teja`       | `Sandesh Damkondwar` | Initial Draft       |

  </details>
</header>

Tired of endless scrolling on instagram/reddit because you have to wait for at least 10-15 seconds (10 seconds if you're lucky) to see your change on dev? 

Your prayers are finally answered. 

(🥁🥁🥁 in background) *HMR* is now enabled on Dashboard. You don't believe it? Try the setup and see it for yourself.

- [1. Redirector setup](#1-redirector-setup)
- [2. Development using HMR](#2-development-using-hmr)

Assuming you're ready with frontend installation.

### 1. Redirector setup

- Install the [Redirector Extension on Chrome](https://chrome.google.com/webstore/detail/redirector/ocgpenflpmgnfapjedencafcfakcekcd)

- Add the following config in the Redirector extension.
  ```
    Redirect: https://dashboard.dev.razorpay.in/dist/merchant-entry.js
    to:	https://localhost:8080/public/dist/merchant-entry.js
  ```
  ![](./assets/redirector-config.png)

### 2. Development using HMR

**2.1. Start Dashboard server**
  ```
  yarn serve
  ```

**2.2. Enable Redirector**

  ![](./assets/redirector-extension.png)

**2.3. Accept invalid SSL certificates**

- Login on https://dashboard.dev.razorpay.in, merchant entry file won't load  due to SSL invalid error. Open dev tools and find the merchant-entry request in network tab.

- Open merchant entry file link in a new tab and you'll see this error.
  ![](./assets/ssl-invalid-certificate.png)

- Click on Advanced > Proceed to localhost (unsafe), you'll be navigated to the merchant entry file.

- Refresh the dashboard, merchant-entry call will be successful now. But, all the modules will fail to load due to SSL error. Open any one of those modules in a new tab and accept the invalid SSL certificate.

- Refresh dashboard again and VOILAAAA, welcome to the HMR world :raised_hands:.
