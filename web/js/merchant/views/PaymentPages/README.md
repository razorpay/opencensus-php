# Code documentation - Payment Pages

Payment pages allows merchants to sell their products/services, by helping them build a landing page that integrates into Razorpay's payment gateway

## Folder structure

- QuickGuide -> Banner shown on Listing Page (it is closable)
- PaymentPages -> Renders the listing, details, create/edit, success views
  - Details
  - List
  - Success 
  - Wysiwyg -> used for create/edit flows
  - components
    - Modals -> all modals go here
- Onboarding -> Initial onboarding flow shown to user
- index.js -> Main entry file (loaded via merchant/routes/Content.js)

## Flows

- `index.js`
  - Gets loaded for main payment pages route. (/paymentpages -> check routes/Content.js)
  - Any specific banners for Payment Pages are added on top
  - Followed by DashboardBanner component (which fetches banners based on current route and displays banner asynchonously)
  - QuickGuide banner component is shown if applicable
  - We render a tab link for payment pages (sub products are usually added here)
  - The common Test mode banner is shown
    - It is visible in test mode, hidden in live mode (also has a toggle)
  - We wrap Payment Pages product with an ErrorBoundary & add routing for the list view
- `QuickGuide`
  - data.js
    - Static Content to be shown in onboarding flow
    - We decide which content to show based on the status of payment page (if status is 'done' or not)
  - index.js
    - We use a QuickStepGuide, QuickStep component to show a stepper view of the process of using Payment Pages
    - These are internally using StepGuide, Step components
    - It uses `title` & `content` attributes from data.js
    - We export a utility function, to check if quickGuide is to be shown or not
- `PaymentPages` -> Contains code for Details page, Listing page, Success page and create/edit page
  - `List/index.js`
    - Renders the `<tabbed-container>` tabs on the left, and the children of `<HeaderAction>` on the right side using `react-tether`
      - `<tabbed-container>`'s content come from paymentpages/index.js file.
    - Displays the search filters for the Payment pages list view
    - Displays the List (takes a loader and the data)
    - Displays the pagination component (shows navigation buttons only if applicable)
    - On mount -> We fetch list of payment pages data (store the total length) & intialize onboarding flow (if required)
      - We check localstorage & payment pages length to know if its enabled or not. If not, we show onboarding flow
      - If its enabled, but we manually requested for the onboarding flow (then we open the onboarding flow)
      - We render table, only if user has edit permissions and loader is off and length of list is greater than 0
    - on props change, we update totalLength and loading variables
    - This component extends ListContainer. Which means it can access methods from ListContainer.
  - `List/List.js`
    - Renders the table header
    - If rows.length is 0, we show empty data. Else we render rows inside a `<tbody>` tag
    - We render price fields using `<Amount>` component
    - If price fields are more than 2, we show 2 fields intially & add a `+ ${number} more` button -> shown via popover
      - We show the Item name & quantities sold in a table view
    - Based on the status, we show the label (tag) -> Active, Inactive
  - `Wysiwyg`
    - index.js
      - Renders the header for payment pages -> Page title(for Create/edit) & actionButtons (for modals & close button), adds the components for various modals (Page Settings, Receipt Settings, Shiprocket settings, Template selection screen)
      - before mount -> we call `fetchEntity`, and also check if we're duplicating a Payment Page (fetch id from query string). We also prefetch the share icons
        - `fetchEntity` 
          - It calls `this.props.fetchPaymentPage` -> Which updates redux state in creation PP phase, and calls an API (if edit phase)
          -  If id doesn't exist (i.e. create PP stage), fetchEntity updates redux with default values (it resets formItems). Else an API call is done and redux is updated with the data fetched for that particular id
          - In edit phase, as the `this.props.fetchPaymentPage` returns a promise, we open the relevant modal once the API resolves[receipt settings, page settings, shiprocket enable/disable] (if passed via queryParams)
      - on mount -> we fetch merchant details (merchant Terms and conditions), loadInitialFormItems (adds email, phone field in FORM_ITEMS in redux) & load color.js & wysiwyg.js(only if this script fails, we show an error message)
      - On props change
        - if id changed, we fetchEntity details & close all modals & open template selection screen
        - TBD (unsure what the code does)
      - on state change [also checks if id exists]
        - if theme has changed -> then toggle theme (if light mode -> add 'light' class & remove 'dark' class & visa versa) 
        - if theme doesn't exist -> we set light theme
      - on clicking close button, if page was modified (isPageDirty), we show a warning (to discard changes or not). If user wants to discard changes, we direct them to listing page
      - When wysiwyg.js and merchant tnc data fetched, we mount our svelte sub apps (refer Svelte.js)
        - ![Payment Pages helper image](https://i.imgur.com/77ywj4o.png)
        - Since we render content via ReactDOM.render, we need to undo this on unmount (using `unmountComponentAtNode`)
      - on selecting the template, we close modal and focus on the title's input box
      - Payment Receipt
        - We have a toggle method, to open/close the modal
        - On save, we update redux
      - Payment Settings
        - same as payment receipt
      - On clicking save & publish
        - We seperate FORM_ITEMS into udf_schema (user defined fields) & payment page items (price fields)
        - We do error handling for various user inputs & focus on the element if possible
        - We manually form the request payload (new keys need to be added here)
          - template_type is sent during creation phase
          - We delete uneditable fields in goal_tracker before sending the API call
        - On success, we redirect to the success page
        - Else, we show an error toast message
      - Shiprocket
        - To be refactored shortly/skipping code documentation
    - Svelte.js
      - This component is mounted only once
      - On inital render, we initialise the svelte app on `wysiwyg-root` DOM node
        - We setup templateData similar to how its done in hosted pages and call renderApp with this data
        - renderApp function is written in the wysiwyg.js
      - On unmount, we destroy the svelte instance
    - Success
      - We use a custom header similar to the create/edit flow
      - We render Page Settings, Receipt settings modal here (on success, we update redux)
        - on save, we make an API call to update the data in DB
      - In an iframe, we load the Payment Page preview
      - This has an overlay which redirects to the URL (on hover). Therefore user can't click on it
      - Based on the saved settings (for 80g, receipts, shiprocket, etc), we show different content on success page
      - on mount ->  we fetch entity details (id taken from the URL)
    - Details
      - Gets loaded via routing written in routes/Content.js
      - Displays the details of a particular Payment Page ID
      - on mount -> sets loaders & fetches details of the payment page & list of payments made on the particular payment page
      - on id change -> we fetch entity details & list of payments details again
      - `Details/V3`
        - We render a breadcrumb like header on top
        - Below this, we render a card which shows the details of the payment page
        - The card's title shows the name of the payment page & action buttons (share, duplicate, settings, edit) [panel-heading]
        - We render each details inside a `EntityDetailRow` component (takes a label & a value) [label/value can be a function or a string]
        - We allow merchant to activate/deactivate a payment page (we use 2 different methods for this)
        - We allow merchant to change expiry time of Payment Page (if they have edit access)
        - We allow merchant to add multiple notes (title, description) [key value pairs]
        - If shiprocket is enabled for the page, then we show a link to disable shiprocket on edit page [panel-body]
      - We render donation goal tracker's widget for the page (if feature is enabled)
      - We show price fields added to the form & update stock button(name, revenue, price, units sold)
      - We can specify `no limit`, or add our stock here [item-details]
      - We have a button to show more/show less (handled via classNames)
      - If test mode, we show a banner
      - We display transactions summary, a table for the page (PaymentsList) & download report button
      - PaymentsList
        - This component extends ListContainer (inherits the methods of it)
        - Renders the filters for the table, and the list of payments' table component
- OnBoarding
  - We have 2 Slides
  - First one shows the Product Name and a description using `<Landing>` component
  - Second one shows 3 product features (taken from `data.js`)
  - We show slider dots below the slider and also a skip button (which skips the tour)
  - We pass nextBtn logic for the last slide (which enables the feature/updates localstorage)




  
  
