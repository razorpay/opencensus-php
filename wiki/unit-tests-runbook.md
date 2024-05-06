<header>
  <h1 align="center">Unit Tests Runbook</h1>
  <details>
    <summary align="center"><b>Changelog</b></summary>

| Version | Date         | Creators             | Reviewers            | Remarks       |
| ------- | ------------ | -------------------- | -------------------- | ------------- |
| 1.0     | Jul 22, 2022 | `Shivam Kumar Singh` | `Sandesh Damkondwar` | Initial Draft |

  </details>
</header>

- [1. Background \& Introduction](#1-background--introduction)
  - [1.1. Why write tests?](#11-why-write-tests)
  - [1.2. What to use in writing tests?](#12-what-to-use-in-writing-tests)
  - [1.3. Why not use Enzyme for testing?](#13-why-not-use-enzyme-for-testing)
- [2. Running tests in the Dashboard repo](#2-running-tests-in-the-dashboard-repo)
- [3. Current Setup](#3-current-setup)
- [4. Writing Unit Tests](#4-writing-unit-tests)
- [5. Mocking API in components {#mocking-api-in-components}](#5-mocking-api-in-components-mocking-api-in-components)
- [6. Mocking API errors](#6-mocking-api-errors)
- [7. Testing Reducers and connected components](#7-testing-reducers-and-connected-components)
- [8. Mocking Modules and Utilities](#8-mocking-modules-and-utilities)
- [9. Tests folder structure](#9-tests-folder-structure)
- [10. FAQs {#faqs}](#10-faqs-faqs)
- [11. Action Items](#11-action-items)
- [12. Appendix {#appendix}](#12-appendix-appendix)
- [13. Notes](#13-notes)

### 1. Background & Introduction

#### 1.1. Why write tests?

1. Currently, our dashboard codebase has modest UT coverage in only a few modules, and as a lot of people from different teams work on the same repo, it’s very easy to introduce unintended regression bugs. Writing tests will ensure that we’re not introducing new bugs with a new release.
2. We have plans of refactoring the entire codebase with TypeScript, react-query, etc. Again, with refactoring comes increased chances of issues and bugs. Currently, devs test the changes manually to see any issues, but a lot of edge cases will be slipping under the radar. Writing tests ensure that our refactors are 100% bug-free.

#### 1.2. What to use in writing tests?

We are using [React Testing Library (RTL)](https://testing-library.com/docs/react-testing-library/intro/) and [Jest](https://jestjs.io/) to write unit and integration tests for your modules.

If you are new to React testing, go through the course[^1] [Testing React Applications, v2](https://frontendmasters.com/courses/testing-react/) first to get an overview of how to think of testing in terms of RTL.

#### 1.3. Why not use Enzyme for testing?

Before RTL, Enzyme was used as a de-facto framework for testing React apps, but there are quite a few pitfalls that RTL addresses.

1. **Presentation vs Implementation**: Testing with Enzyme usually meant testing the implementation of a component (testing state, props etc) but writing tests this way results in the tests being brittle and intolerant to refactoring. However, RTL only cares about what the user sees (DOM Nodes as opposed to component instances), hence we test the presentational aspect of our components, not how they were implemented.
2. **Implementation Utilities of Enzyme**: While we can follow these guidelines using Enzyme itself, enforcing this is harder because of all the extra utilities that Enzyme provides (utilities that facilitate testing implementation details).

### 2. Running tests in the [Dashboard](http://github.com/razorpay/dashboard/) repo

We have already set up the test runner using Jest.

- To run and watch all tests, run this in the root directory -

  ```bash
  pnpm test:jest-watch
  ```

- To run individual tests, run -

  ```bash
  pnpm test:jest -t filename
  ```

For more CLI options, see [here](https://jestjs.io/docs/cli)

### 3. Current Setup

All our tests are governed by the configuration files `web/jest.config.js` and `web/js/common/services/test/setupTests.js` Taking a closer look at them -

1. `web/jest.config.js` - All the config related to jest, check [Configuring Jest](https://jestjs.io/docs/configuration)

   1. **Setting coverage -** While testing a module, we can specify the least amount of coverage we want -

      ```javascript
      // An object that configures minimum threshold enforcement for coverage results

      coverageThreshold: {
        global: {
          statements: 47,
          branches: 35,
          functions: 38,
          lines: 48,
        },
        './js/merchant/views/onboarding/': {
          statements: 72,
          branches: 58,
          functions: 68,
          lines: 72,
        },
        './js/merchant/views/Transactions/Payments/': {
          statements: 90,
          branches: 90,
          functions: 90,
          lines: 90,
        },
      },
      ```

2) `web/js/common/services/test/setupTests.js` - This is where we write our global [mocks](https://jestjs.io/docs/manual-mocks), start the [MSW](https://mswjs.io/) server and put all common **_beforeAll_** code.

   ```javascript
   // Global mocks
   jest.mock('merchant/utils/ajax');
   jest.mock('merchant/views/TicketSupport/utils.js', () => ({
     CreateTicketEmitter: jest.fn(),
   }));
   jest.mock('common/utils/analytics', () => ({
     ...jest.requireActual('common/utils/analytics'),
     analyticsTrack: jest.fn(),
   }));
   jest.mock('common/services/tracking/segment', () => ({
     ...jest.requireActual('common/services/tracking/segment'),
     analyticsTrack: jest.fn(),
   }));
   ```

   _We are mocking all the modules which are common to our tests that need to be mocked, e.g. analytics._

Apart from these two, we use `web/js/common/services/test/test-utils.tsx` to export all test utilities from RTL and also wrap RTL’s render with our providers, store, etc. Any utility which is global should be kept here and exported.

```javascript
const waitForLoadingToFinish = (): Promise<void> =>
  waitForElementToBeRemoved(screen.queryAllByTestId('spinner'));

const delay = (time = 1000): Promise<void> => new Promise((r) => setTimeout(r, time));
```

_Utilities used across the project_

### 4. Writing Unit Tests

```javascript
// merchant/views/Transactions/Payments/components/__tests__/mocks/fixtures/PaymentRefund.js

import PaymentRefund from 'merchant/views/Transactions/Payments/components/PaymentRefund';

export const defaultProps = {
  payment: {
    disputes: {
      items: [],
    },
  },
  refunds: {},
  openRefundModal: jest.fn(),
  onToggleClick: jest.fn(),
};

export const App = (props) => {
  return <PaymentRefund {...defaultProps} {...props} />;
};
```

```javascript
// merchant/views/Transactions/Payments/components/__tests__/PaymentRefund.test.js
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import { refund } from 'merchant/views/Transactions/Refunds/__tests__/mocks/fixtures';
import { App } from 'merchant/views/Transactions/Payments/components/__tests__/mocks/fixtures/PaymentRefund';

// describe message should be sentence case or  component/util name
describe('PaymentRefund', () => {
  // recommended to always start test messages with `should` and should be in lowercase
  test('should not render payment refund details when there is no payment status', () => {
    const { container } = render(<App card={null} />);
    expect(container.firstChild).toBeEmptyDOMElement();
  });

  // avoid repeating your tests
  test.each(['created', 'authorized', 'failed'])(
    'should render payment refund details when payment status is %s',
    (paymentStatus) => {
      render(
        <App
          payment={{
            status: paymentStatus,
          }}
        />,
      );
      expect(screen.getByText('Not Applicable')).toBeInTheDocument();
      expect(screen.getByText('Only captured payments can be refunded.')).toBeInTheDocument();
    },
  );

  // use describe blocks as much as possible to test different scenarios/sections
  describe('When payment status is captured', () => {
    const payment = {
      status: 'captured',
      refund_status: 'partial',
      disputes: {
        items: [],
      },
    };
    test('should render partial payment refund details', () => {
      render(
        <App
          refunds={{
            items: [refund, refund],
          }}
          payment={payment}
        />,
      );
      expect(screen.getByText('Partially refunded in')).toBeInTheDocument();
      expect(screen.getByText('2 refunds')).toBeInTheDocument();
    });

    test('should render when there is no refunds issued yet', () => {
      render(
        <App
          payment={{
            ...payment,
            refund_status: null,
          }}
        />,
      );
      expect(screen.getByText('No refunds issued yet')).toBeInTheDocument();
    });
  });
});
```

### 5. Mocking API in components {#mocking-api-in-components}

While there are [multiple](https://www.loupetestware.com/post/mocking-api-calls-with-jest) [ways](https://www.npmjs.com/package/jest-mock-axios) to mock API requests, we follow a slightly different approach.

Instead of mocking the API requests themselves, we can intercept them using something like [MSW](https://mswjs.io/) which then responds using stubbed responses.

We store the mock handlers in `web/mocks/handlers.js`, which we use to instantiate our MSW server in `setupTests.js`

```javascript
rest.get('*/merchant/api/test/settlement/holidays', (req, res, ctx) => {
  return res(
    ctx.status(200),
    ctx.json({
      status_code: 200,
      data: SettlementsDB.holidaysList,
    }),
    ctx.delay(50),
  );
}),
```

### 6. Mocking API errors

We have created a catch-all route for error handlers in `web/mocks/errorHandlers.js`,

```javascript
import { rest } from 'msw';

const returnInternalServerError = (req, res, ctx) =>
  res(ctx.status(500), ctx.json({ status: 500, responseJSON: 'Internal server error' }));

export const errorHandlers = {
  internalServerError: rest.get('*/', (req, res, ctx) => returnInternalServerError(req, res, ctx)),
};
```

which is then called separately for individual tests.

```javascript
test('should show notification on network error', async () => {
  server.use(settlementInfoErrorHandler());

  renderApp();
  await waitForLoadingToFinish();

  checkIfComponentIsEmpty();
  expect(screen.getByText(/Something went wrong/i)).toBeInTheDocument();
  expect(screen.queryByText(/Total credit amount/i)).not.toBeInTheDocument();
  expect(screen.queryByText(/Total debit amount/i)).not.toBeInTheDocument();
});
```

### 7. Testing Reducers and connected components

As we are using redux to dispatch our API requests and store API responses, a lot of the components we would test would be redux-connected components. Two important cases for such components are -

1. **Components making API requests themselves**

   This wouldn’t require any extra work from us, as we are already mocking the requests using MSW. We only need to wait for the loading state to finish and we’ll receive the mock data.

2) **Parent components making API requests**

   While testing a component, which is used only in conjunction with a parent who is responsible for making API requests (and storing the data in the store), we wouldn’t have the data present in the store.

   Hence, we can trigger the API call manually by dispatching the action, which ensures the child component, while tested separately, will have access to the data.

   ```javascript
   let App: React.FC<AppProps> = (props: AppProps) => {
     useEffect(() => {
       props.fetchBreakupDetails?.({
         id: 'settlmenttest',
       });
     }, [props.fetchBreakupDetails]);

     return <SettlementEntities settlementId="settlmenttest" />;
   };
   App = connect(null, { ...SettlementActions })(App);
   ```

   We’re dispatching fetchBreakupDetails manually here to ensure SettlementEntities will have the data

   > **Note -** Once we migrate to [Redux Toolkit](https://redux-toolkit.js.org/), testing will be much easier as we can test the individual slices for any logic/implementation details, while testing components for presentational assertions.

### 8. Mocking Modules and Utilities

Often, we have to mock a utility function because either it’s interacting with a window attribute that jest doesn’t have access to or it’s doing some async operation we want to stub. There is one more use-case for mocks, when there is a cyclical dependency in some module, owing to which the test-runner fails to run.

We can mock such modules in two ways:

1. Writing mock implementations inside of a **mocks** folder inside the **tests** folder.
2. Mocking modules with `jest.mock()` - This way, we can even mock a particular export of a module and keep the rest intact.

> **Note -** For mocks used across the project, keep them in `setupTests.js`

### 9. Tests folder structure

```javascript
__tests__ /
  component1.test.js /
  component2.test.js /
  mocks /
  handlers.js / // or /handlers (can be a file or folder) - For common APIs
  component1.js /
  component2.js /
  fixtures.js / // or /fixtures (can be a file or folder) - For common fixtures
  component1.js /
  component2.js;
```

### 10. FAQs {#faqs}

1.  What is the max file size for the test files?

    The **maximum file size is 400 lines of code**. If it goes beyond then it should be split into different files.

2)  What is the minimum unit test coverage threshold for any test file?

    The **minimum unit test coverage** threshold for any test file is **80%**.

3.  How to spy on dispatched Redux actions?

    Currently, it is not possible to spy on Redux actions if they are created using a shorthand object. Use `bindActionCreators` to spy on the Redux actions.

4)  How to mock API requests?

    Use MSW to mock API requests <https://mswjs.io/docs/>

5.  How to intentionally skip testing certain parts of code?

    Use Istanbul ignore when intentionally skipping testing certain parts of code <https://eloquentcode.com/istanbul-ignore-syntax-for-jest-code-coverage>

6)  How to work with Modals?

    Use the `showModal` option in the `customRender` options to render Modals on the screen.

7.  How to set the custom initial redux state? Use the initialState option in the customRender options for setting the custom redux initial state

    ```javascript
    test('should render bank account hold status', async () => {
      const initialState = {
        settlement: {
          config: {
            features: {
              hold: { status: true, reason: 'xyz reason' },
            },
          },
        },
      };
      render(<App initialState={initialState} />);
      await waitFor(() => {
        expect(
          screen.getByText('Your settlements have been put on hold due to'),
        ).toBeInTheDocument();
        expect(screen.getByText('xyz reason')).toBeInTheDocument();
      });
    });
    ```

8)  How to work with window location?

    Use `jest-location-mock` to mock `window.location` with Jest spies and extend expect. <https://github.com/evelynhathaway/jest-location-mock>

9.  How to simulate user interactions/events?

    Prefer `userEvent` over fireEvent to simulate the user events on the screen/DOM. <https://testing-library.com/docs/ecosystem-user-event>

10) How to create events with fake event data?

    You might need to have events with specific attributes like `scrollX`, `scrollY`, `pageX` & `pageY`, to achieve this you can create a fake `mouseEvent` or a `keyBoard` event and then override the values you want for the event. Refer to the [link](https://github.com/razorpay/dashboard/blob/09824768d7fcd402125ce572231ed9f912b53660/web/js/merchant/views/Settings/Webhooks/__test__/mocks/fixtures/Entity.js#L130), Usage [link](https://github.com/razorpay/dashboard/blob/09824768d7fcd402125ce572231ed9f912b53660/web/js/merchant/views/Settings/Webhooks/__test__/Entity.test.js#L91)

11. When to use `userEvent` setup? `userEvent` Setup API allows you to create an instance of user-event. The methods of this instance share one input device state. This allows to write multiple consecutive interactions and maintain the state and behave just like the described interaction by a real user

12) How to mock any function/component?

    Use `jest.mock` to mock any functions or components. For e.g. -

    ```javascript
    jest.mock('common/ui/SomeComponent', () => () => <div>SomeComponent</div>);
    ```

    <https://jestjs.io/docs/mock-functions>

13. How to spy on any utils?

    Use `jest.spyOn` to spy on the functions or utils. For e.g.

    ```javascript
    const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');
    ```

    <https://jestjs.io/docs/jest-object#jestspyonobject-methodname>

14) How to fake timers (`setTimeout`, `setInterval`, `clearTimeout`, `clearInterval`)?

    Use `jest.useFakeTimers` to fake the timers. [Using Fake Timers | Testing Library](https://testing-library.com/docs/using-fake-timers/)

15. How to mock dates?

    All the jest tests run at UTC (configured in global-setup.js)

    ```javascript
    jest.useFakeTimers('modern').setSystemTime(new Date('2022-01-01'));
    ```

    You can add this in `beforeAll` block. Do not forget to jest.useRealTimers() after your tests run.

16) How to assert analytics track events?

    `analyticsTrack` is globally mocked and it can be asserted directly anywhere. For e.g.

    ```javascript
    import { analyticsTrack } from 'common/utils/analytics';
    expect(analyticsTrack).toHaveBeenCalledWith({ ... });
    ```

17. How to wait for the component updates or async actions to be completed?

    Always use `waitFor` over `act` to wait for the component updates or async actions to be completed. You can also refer to this section - <https://testing-library.com/docs/guide-disappearance>

18) When to use delay?

    Should be the last resort to wait for any async actions to be completed if they can’t be awaited using `waitFor`.

19. Where to find the UT coverage report?

    Locally, it can be found here `web/js/coverage/lcov-report/index.html`. To view the file, open it up in the browser. Remotely, it can be found on SonarQube <https://sonar.razorpay.com/code?id=dashboard>

20) How to run UTs through the command line?

- `pnpm test` - runs all the unit tests.
- `pnpm test:jest-watch-changed` - runs all the unit tests initially and thereon runs only changed files with silent mode.
- `pnpm test:jest-watch` - runs all the unit tests on any changes without silent mode.

> **Note -** To run specific test file(s) use the file name regex with the test commands. For e.g. `pnpm test:jest-watch <payments> | <relative path your test file>`

21. How to query Screen/DOM elements?

    Here is the recommended order for querying the Screen elements -

    - Always prefer [screen queries](https://testing-library.com/docs/queries/about#priority) (query by `data-test-id` should be a last resort)
    - Use container APIs as a last resort for querying the elements

22) How to assert any utils used at different places?

    Use the utils directly to check for the expected value instead of directly using the end result in order to avoid the side effects on other tests if somehow the utils break. For e.g.

    ```javascript
    import { titleCase } from 'common/utils/rzp-utils';
    const onHoldText = screen.getByText(titleCase(status));
    expect(onHoldText).toBeInTheDocument();
    ```

23. How to debug your tests?

- Click on the Run and Debug section in your VS Code's right panel.
- Ensure the Debug Jest Tests is selected in the configuration dropdown menu.
- We're going to run the file in .vscode/launch.json. Please specify the folder/file of your interest in this file.
- Click on the play button
- Now you use the inbuilt VS code debugger to view the contents of your file
- To learn more, refer this [video](https://www.youtube.com/watch?v=96MXwMiNhrk)
- It's recommended to use this debugger only when required, as using this will make your tests slower to run.

### 11. Action Items

- [x] UT template with `customRender` usage
- [x] Make `ConfirmModalProvider` as a default part of `customRender`
- [x] Cleanup of `customRender`
- [x] Move `FakeMouseEvent` to `test-utils`
- [x] Ignore test/mocks from test coverage

### 12. Appendix {#appendix}

1. [Common pitfalls while using RTL](https://kentcdodds.com/blog/common-mistakes-with-react-testing-library)
2. [MX Spotlight - Progressive UT adoption on PG Dashboard](https://docs.google.com/presentation/d/1xgKHh57Kn6a78-KtxZsskbV4PcEktEe2VirfWMREEgA/edit?usp=sharing)

### 13. Notes

1. To get access to Frontend Masters, get yourself added to [frontend@razorpay.com](mailto:frontend@razorpay.com) and obtain the credentials from Google Groups.
2. We use MSW to intercept our API requests and check the **Mocking API** section. Unit testing going forward will be mandatory on all the PRs. In case anyone is interested and wants to adopt the unit testing on the dashboard, please reach out to `Aakash Raina`, `Shivam Kumar Singh`, `Sai Indra Teja Merugu`, `Rishav Loomba`, `Prashant Choudhary`, `Abdul Shafi Ashraf`. Use [#sme-engrs](https://razorpay.slack.com/archives/C01J609L1GT) to discuss/share your queries/ideas with a broader audience.
