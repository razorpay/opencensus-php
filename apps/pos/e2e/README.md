# Writing e2e's for POS micro app

> POS micro app resides within dashboard and our e2e's run on top of the common e2e's setup and infra. As a prerequisite it's imperitive you go through dashboard's e2e setup and understand how e2e's work on dashboard. You can refer the following links for getting a thorough understanding.

1. [E2E guidelines & troubleshooting](https://docs.google.com/document/d/1ovQ-3GZOaslYzI07MloLNKyNga1-Wj-g_2_zq6N5-uE/edit?usp=sharing)
2. [E2E debugging & KT session](https://drive.google.com/file/d/1Ja3OnST96NggAiTgP5Pw_zmKspR7VBDO/view?usp=drive_link)
3. [Ownership & resolution SLAs](https://docs.google.com/document/d/1OHpbStsroaF3Fu3Frfz_0tnZ99c0Zhv7OMFPWOgTwN0/edit)

The above two resources should get you started on writing & debugging e2e's on dashboard. Additionally, you should also go through Playwright [documentation](https://playwright.dev/docs/intro) to understand how to fully utilize the tool to write e2e's.

> [!TIP]
> For any e2e failures it's expected you do an initial level of debugging and come up with data points. Additionally, you should also drop a msg in `#dashboard-e2e-stability` channel & tag the owner of the testcase(s) and hold them accountable for failed tests and alongside cc `@dashboard_ci_cd_devs` group.

### Folder structure

1. POS e2e's reside under `pos/e2e` folder. The `suites` folder has the test suites. The suites folder is further divided into module level sub folders. To add a new test, check if the module it belongs to already exists and add test(s) under the same module otherwise you can add a new module level folder inside suites.
2. Every module level folder is further divided into a `.spec.ts` file & a `mocks` folder. .spec.ts file contains the actual testcase whereas the mocks folder contains a `fixtures.ts` & a `handlers.ts` file.
   - Every `fixtures.ts` file contains mock data needed to run your tests. All module level mocks should reside the fixtures file.
   - Every `handlers.ts` file contains HTTP request handlers which help in mocking requests in our tests. All module specific request handlers should reside here. Refer to an existing handlers file for more context.
   - Mock data getting consumed in request handlers should reside inside the fixtures file.

The said folder structure has to be maintained through out.

### Mocking API requests

For POS app, we are utilizing request mocking to mock our network requests. What this means is that actual network requests are never made to the backend instead all requests are served via our mock handlers.

To achieve this, we are using a package `playwright-msw` which inturn utilises `msw` as a tool to help in request mocking. It's advised that you read more about msw [here](https://mswjs.io/docs/) and get yourself well acquainted with the tool.

> The request mocking setup is primarily done to enable GraphQL mocking since the app is built majorly with gql. To work with rest HTTP requests with Playwright, refer [these](https://playwright.dev/docs/mock)

##### To get started with GraphQL request mocking in your tests, refer `possales.spec.ts`.

1.  Notice the use of `await worker.use(queryMocks.SalesOnboardedMerchants);`
2.  worker.use() takes handler(s) as arguments. The handler(s) have to be written inside a handlers.ts file and imported within the test file.
3.  MSW uses graphql operation name to match and intercept gql requests.
4.  Handlers use the ` graphql.query` API to match and intercept requests. To write handlers, refer this code snippet.

```js
export const queryMocks = {
    YOUR_QUERY_NAME: graphql.query('YOUR_QUERY_NAME', () => {
        return HttpResponse.json({
        data: {
            OPERATION_NAME: mockData,
        },
    });
})
```

5. Incase of a mutation, you can use `graphql.mutation` API instead.

##### To get started with REST API request mocking in your tests, refer [this](https://playwright.dev/docs/mock).

1. To mock requests on per test basis, use the `page.route()` API to match & intercept specific requests.

### Miscellaneous

1. Credentials for user accounts that run e2e's reside inside `.env.devstack` file. Should you need to add another login(not recommended) you can add the associated creds for that account in the same file. The same creds are referenced inside `playwright/constants/constants.js`, for POS add the creds inside `getPosCredentials` function.

### Questions/Doubts

For any issues & questions regarding POS e2e's you can reach out to `@omni-acquisition-fe` or drop a msg in `#omnichannel-acquisition`
