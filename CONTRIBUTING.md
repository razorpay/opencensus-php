<header>
  <h1 align="center">Contributing Guide</h1>
  <details>
    <summary align="center"><b>Changelog</b></summary>

| Version | Date         | Creators             | Reviewers            | Remarks       |
| ------- | ------------ | -------------------- | -------------------- | ------------- |
| 1.0     | Apr 7, 2022  | `Sandesh Damkondwar` |                      | Initial Draft |
| 1.1     | Dec 23, 2022 | `Ritesh Ganjewala`   | `Sandesh Damkondwar` | Refactored    |

  </details>
</header>

- [1. Introduction](#1-introduction)
- [2. Guidelines](#2-guidelines)
  - [2.1. Common checks](#21-common-checks)
  - [2.2. JavaScript](#22-javascript)
  - [2.3. HTML/JSX](#23-htmljsx)
  - [2.4. CSS](#24-css)
  - [2.5. Analytic events](#25-analytic-events)
- [3. Additional tips](#3-additional-tips)
- [4. PR Merge strategy](#4-pr-merge-strategy)
- [5. How to send a PR for review](#5-how-to-send-a-pr-for-review)
- [6. How to onboard as a PR reviewer](#6-how-to-onboard-as-a-pr-reviewer)
- [7. References](#7-references)

### 1. Introduction

This document's goal is to bring all the engineers working on the PG Dashboard into alignment. Every engineer from every pod should be familiar with the common standard procedures we employ on this project.

Every item listed in this guideline and the justification for each standard practice added here should be understood by PR reviewers.

### 2. Guidelines

#### 2.1. Common checks

- If there is a big change in PR:

  Make sure big components are loaded lazily on-demand - References for lazy loading: `web/js/merchant/views/Customers/List.js` and `web/js/merchant/views/Account/Balances/index.js`

  - Make sure lazy-loaded components have **webpackChunkName**, if not present it will be hard to track the payload difference in the bundle analyzer
  - There are 2 ways to lazy-load the components

    - Use `<LazyLoad />` from **react-lazyload** - for loading component when it is in the viewport
    - Use **lazy** from merchant/routes/LazyLoader - dynamic loading using react’s inbuilt lazy function.

- If the SVG icon is present then use it as a Webfont

  - Add the SVG icon in the icons/merchant folder

    ```bash
    web/icons/merchant/offer.svg
    ```

  - Consume it with `<i>` HTML tag -

    ```javascript
    <i className="i i-offer m-r" />
    ```

  > **Note -** colors will be washed off and only one color will be possible to use using the color property on this tag. If you have multiple color icons, please import the SVG file just like any other import and use the `<Image />` component.

- To load the images, add the image inside the `web/dist/css/assets/` folder and import the image with the ES6 import syntax -

  ```typescript
  /* importing an image from assets */
  import BankImage from ‘assets/bank.svg';

  /* consuming the import image */
  <img src={BankImage} />
  ```

  _Reference file_ - `merchant/views/SmartCollect/VirtualAccounts/Create/CreateV2.js`

  > **Note -**
  >
  > - By using the image this way you will be using CDN instead of the dashboard backend
  > - Don’t upload your assets on the static repo

- Use PNG format for images only if transparency is required - PNG files are heavy in size, prefer jpg/jpeg formats. Read more about it [here](https://www.bluearcher.com/blog-item-jpg-vs-png-for-web)

- The file size is not more than 250 lines - ask to breakdown functions and components **_(automated with a warning)_**

- PR size is not more than 600 lines - ask to breakdown the PR with our merge strategy mentioned below **_(automated with a warning)_**

- When used the external library loaded only on demand (on click/other user interactions)
  - the main bundle size is small when rendered
  - breakages in this component are not going to break the whole dashboard
- ~~Screenshots are added if there is a new page/tab/components/redesign of an existing component is done~~
- Screenshots are mandatory even if there is a UI change or not. This brings up confidence in reviewing the pull request

  The benefit of adding a screenshot is that the PR reviewer can quickly and readily understand the context, and they can also point out any aspects that are lacking if one of the various perspectives is absent from your PR.

  - If there are UI changes make sure to post the screenshot of the mobile as well as the desktop version of the component/page/tab
  - If the changes are related to tech OKR, post the screenshot of tools/GitHub actions/analytics events on the dashboard/logs, response time improvement on the sentry dashboard, API response change screenshot, etc.
  - Add the links to the analytics dashboard/GitHub action/DevStack/sumologic logs/vajra on the PR for letting the PR reviewer test this change on their own.

- Handled responsiveness for desktop & mobile and screenshots present if there is a new page/tab/components/redesign of existing component is done
- Don’t hardcode prod/stage URLs, use relative URLs
- Make sure there are null checks
- If new routes are added there is a team owner mentioned in the `web/js/common/new-ui/ErrorBoundary/constants.tsx`
- If any widgets [(eg. slider, announcement banner, banner, nav icons, etc)](https://docs.google.com/document/d/1jj3LZRgDrd8AQCTDZ6r7uAIbLPLu_PaaBuxRLcngmjg/edit) are present on the homepage ask PR reviewers to review from the **Platform Growth** team - they are the POC for all the widgets on the home page - slack handle - @dashboard-widgets-team

#### 2.2. JavaScript

- All The variable names that are used in the code and method names should be written in **camelCase** and constants should be written in **UPPER_CASE**

  ```javascript
  /* variables */
  let bankName, bankSlug;

  /* methods/functions should also be in camelCase */
  const getPropertyCount = () => {};

  /* constants should be in UPPER_CASE */
  const API_TIMEOUT = 300;
  const MAX_RETRIES = 3;
  ```

- No Abbreviations in variable names `i, j, k` etc. A good variable and function name makes the code more readable.

- Method/Function declared in used arrow function Use `‘single quote‘` instead of `”double quote”` in JS **_(check needs to be automated)_**
- No global variables are created/used until and unless it’s coming from **ENV** variables
- No legacy/WD (working draft) APIs are used, if used by any chance, then add conditional checks and have a fallback mechanism.

- Check [caniuse](https://caniuse.com/) and make sure the support for new API is above 99%

- Make sure to have a catch block if you are directly using the `merchantFetch` call in the component. Make sure error messages have been handled with `showNotification` or Alert component
- Use className instead of class **_(check needs to be automated)_**

#### 2.3. HTML/JSX

- Class names are separated via '-'.

  ```javascript
  <div class="nav-header"></div>
  ```

- HTML tag id are written in **camelCase**

  ```javascript
  <div id="navHeader"></div>
  ```

- Use `classList` function instead of using inline conditions while declaring `className`
- Use double quotes in JSX
- Don’t define components inside the components
- Components are hooks and not class-based **_(check needs to be automated)_**
- Used the `useCallback` on functions declared inside the FC which are doing heavy lifting work
- If functions like the below which is not depending on state and props, keep those functions outside the component definition

  ```javascript
  const onReferClick = () => {
    trackEvents({
      objectName: 'Refer Now',
      actionName: 'clicked',
      screen: 'home page',
      toCleverTap: true,
    });
  };
  ```

- All the component names are written in **PascalCase** **_(check needs to be automated)_**
- ~~Every Component which doesn't need any state operation or doesn't get updated on the page for some user action/API should be made PureComponent/ Pure Functions (Stateless Component).~~ **[WIP]**

- Stateless Components are memoized using `React.memo` Learn more about [stateless/pure components here](https://blog.logrocket.com/what-are-react-pure-functional-components/#:~:text=A%20React%20component%20is%20considered,are%20treated%20as%20pure%20components.)
- `ErrorBoundary` is added to protect the app/component from crashing the page when complex components are added, `resetOnProps`, `rank` and `team` name is also mentioned on `ErrorBoundary`

  ```javascript
  <ErrorBoundary resetOnProps rank={Ranks.P1} team={Teams.BANKING}>
    <!-- Your component goes here -->
  </ErrorBoundary>
  ```

- Props should be de-structured while accepting functional components

  ```javascript
  /* correct */
  const HelloComponent = ({ isDisabled, value }) => {
    // ...
  };

  /* incorrect */
  const HelloComponent = (props) => {
    const { isDisabled, value } = props;
    // ...
  };
  ```

#### 2.4. CSS

- Formatting is done properly on CSS **_(check needs to be automated)_**
- Consistency in CSS/Stylus rules (not a mix of CSS & Stylus) - follow the stylus syntax if you are using .styl files - <https://stylus-lang.com/docs/css-style.html>
- `!important` is not used until necessary **_(check needs to be automated)_**
- No inline CSS **_(check needs to be automated)_**
- The nesting level is not more than 4 levels **_(check needs to be automated)_**
- Missed using CSS variables when needed
- ~~No need of~~ `px/rem/em` ~~identifier when the value is 0 **_(check needs to be automated)_**~~
- When a hex code for color is getting used in many places always use variables as we are using scss or `.styl` files
- When using colors, use hex codes instead of color names/rgb/rgba. **_(check needs to be automated)_**

  ```scss
  /* correct */
  $tabColor = #FFF

  /* incorrect */
  $tabColor = white;
  ```

- Avoided duplicate hardcoded values & create the variables.
- Has created the `.styl` file inside the components directory if it is limited to that component. Adding that `.styl` file to the `merchant` directory will end up becoming part of the main bundle.

  ![](/_static/file/e70c9ec51a3a4e116a6f20e807d201ee.png)

#### 2.5. Analytic events

- Keep events name in this format `${object} ${action}` e.g. -`SignUP CTA Clicked` [Check this guide on how event names will be structured internally](https://github.com/razorpay/frontend-universe/tree/master/packages/universe-utils#analytics-service)
- Don’t use `_` in event names. New standard practices from the platform will throw errors once we migrate 100% to the platform analytics tool.
- Use `analyticsTrack` from `common/utils/analytics` for sending track events, it can take care of sending events to Segment as well as Lumberjack. Event drops are a common problem at the moment, this should help in catching dropped events. Other mechanisms of tracking events will be deprecated in the future.

### 3. Additional tips

- To keep the file size smaller, continue to break down and break reusable components into separate files. Install the [React Refactor VS Code extension](https://marketplace.visualstudio.com/items?itemName=planbcoding.vscode-react-refactor) for extracting JSX code parts to new functional components.
- Install the [Import Cost VS Code extension](https://marketplace.visualstudio.com/items?itemName=wix.vscode-import-cost) to keep yourself aware of what cost you are adding when importing a specific third-party library and utility function.
- Install the [Stylelint extension](https://marketplace.visualstudio.com/items?itemName=stylelint.vscode-stylelint) for linting styles locally

### 4. PR Merge strategy

Making the code review simple is the author's duty. The reviewers can't go into the problem's background as deeply as you did. As a result, you must communicate the code modifications in an approachable manner. One strategy is to break your PR into multiple smaller PRs with the below strategy.

**_Documentation of merge strategy_** - [PR Merge Strategy](./wiki/pr-strategy.md)

Don't lose your cool if someone declines to review your code because it is too large. To make it more reviewable, a PR author should invest some time and effort.

### 5. How to send a PR for review

- Create PR using the above PR merge strategy if you are planning to work on big changes, else create a simple feature branch. Follow the [git branch naming convention](https://dev.to/couchcamote/git-branching-name-convention-cch) for your branches.
- Once all individual PRs are ready, get those PRs reviewed internally within the team

Share your PRs on `#payments-dashboard-pr-reviews` slack channel and tag `@dashboard-pr-reviewers` for reviewing the PR

### 6. How to onboard as a PR reviewer

- Get yourself tagged on the `@dashboard-pr-reviewers`
- Call out to @dashboard-pr-reviewers saying you will be reviewing the PRs
- Go through the above guidelines first
- Get a good understanding of the reason behind all those checks. If you are not aware of the reasons, ask questions to `@payments-pr-specialists` for the reasons, and we will clarify the reasons in this doc.
- Do participate in cross pods PR reviews, and identify common issues.
- Tag `Sandesh Damkondwar` & `@payments-pr-specialists` once you have reviewed the PR
- Once PR reviews cover all the areas, we will be onboarding you as a PR reviewer

### 7. References

- PG Dashboard Stakeholders: [Stakeholders & Teams - Merchant Dashboard](https://docs.google.com/spreadsheets/d/1-hshDqv5KMEx9SUmchM-hSfJxddsnH_lpi3AAwhyMEk/edit#gid=2097416948)
- Stylus linting adoption and troubleshooting: [Merchant Dashboard CSS Guide](./wiki/css-guide.md)
- Loading static images: [Refer to this slack thread](https://razorpay.slack.com/archives/C01N2CDSB7H/p1662098085154049)
- Lazy loading when the component is in the viewport: [Dynamic lazy-loading of components](./wiki/lazyloading-components.md)
