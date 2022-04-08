
# Code documentation - Payment Buttons

Payment Buttons enable merchants to embed button(s) in their webpage to collect payments without any redirections. Merchants can configure a Payment Button in the dashboard with price fields and UDF fields. The dashboard generates a script which has to be pasted wherever the button is to be rendered in the merchant's website.

## Top-Level Folder structure

- PaymentButton - Folder with all code related to UI when Payment Buttons Tab is selected. 
	- List 
	- Details 
	- Create
	- components
- SubscriptionButton - Folder with all code related to UI when Subscriptions Buttons Tab.
	- List 
	- Details 
	- Create
	- components
- QuickGuide - Sort of a banner loaded in the list view which describes how payment buttons can be used in a stepper fashion.
- Onboarding - Code related to the onboarding flow which get's triggered when the user enters the product for the first time/Clicks on *Need help? Take a tour*. 
- index.js - Main entry file (loaded via merchant/routes/Content.js).
- utils.js - consists of util functions used throughout the product to set and get items from the localStorage. 
- components - consists of all the modals and the success page.

## Flows

- **index.js**
  - Gets loaded for the main payment buttons route. (/paymentbuttons -> check routes/Content.js)
  - Announcement Banners are added on top.
  - the `<tabbed-container>` consists of the NavLinks which point to the respective routes (They form the tabs). The content consists of the respective `<Route>` tag mentioning the component to load (List view of Payment button/Subscription Button in our case) when a particular path is hit. The content is wrapped within an Error Boundary. 
  - The `<tabbed-container>` also consists  of `<QuickGuide>` component and the `<TestModeBanner>` which are shown if applicable.
  - If the `showOnboarding` check is true, the onboarding flow in rendered first. 
- **Onboarding** - Code related to the onboarding flow which get's triggered when the user enters the product for the first time/Clicks on *Need help? Take a tour*. 
  - **data.js**- Static content to be shown in the onboarding flow. 
  - **index.js**
    - We use `<Onboarding>` and the  `<Slider>` to show Slides which give a brief about Payment Buttons and how they can be used
    - functions from `Onboarding` are used to determine whether certain elements of the onboarding flow are to be shown. 
- **QuickGuide** - Sort of a banner loaded in the list view which describes how payment buttons can be used in a stepper fashion.
  - **data.js**
    - Static content to be shown in the quick guide. 
    - We decide which content to show based on the status prop passed (if status is 'done' or not) which is decided based on certain actions done by the user (key stored in localStorage). *Note: Except for one place, looks like all content is same for both the cases.* 
  - **index.js**
    - We use `<QuickStepGuide>`, `<QuickStep>` components to show a stepper view of the process of using Payment Buttons.
    - We export a utility function, to check if quickGuide is to be shown or not.
- **PaymentButton** - Contains code for the Listing Page, Details page and Creation/Edit flow. 
  - **List** - folder consists of all code related to the list view of Payment buttons 
	  - **index.js**
		  - Responsible for rendering three main components. First is the action CTAs shown on the top right, consisting of the help links and the *Create Payment Button* CTA. Second is the list of payment buttons. And the third is the set of search filters which can be applied on the list of payment buttons.
		  - Common component `<HeaderActions>` is used to render the CTAs. 
		  - `<ListFilters>` component  is used to render all the search filters. 
		  - Common component `<DataTable>` is used to render the list of all payment buttons. 
		  - The file also consists of some functions and components which are passed to the DataTable component. 
		  - The component extends the `ListContainer` class which handles the API calls and filtering logic. 
	  - **ListFilter.js** - consists of the common component `<ListFilter>` with the required filters passed as children. This is the component used in `List/index.js`.
	- **track/index.js** - file consists of all analytics events code related to payment buttons list view. 
  - **Details**- when a merchant clicks on  one of the payment button ids in the list view, they are redirected to the details view of a payment button which consists of particular details of that button. 
	  - **index.js** 
		  - consists of code for fetching the payment button details and the payments data for the particular payment button id. Also consists of all the functions related to actions like deactivating the page, api call for updating payment button etc. which are ultimately passed as props to the `<Details>` component along with all the data fetched from the APIs. 
		  - Also handles other cases of showing a loader when the entity is being fetched and an error page if data for the particular button id is not found. 
	  - **Details.js** - responsible for constructing the box on the top with all the details of the payment button like button ID, status etc. (It is collapsed by default and can be expanded to view more info) and the CTAs for duplicating the page, opening the settings modal and getting the code. Consists of all the toggle functions for the modals and also the `<PaymentsList>` component. *Note - lot of components reused from Payment Pages*.
	  - **PaymentsList** - Folder consists of all the code related to the Payment list view shown below the details box. Built by using the common components `<EntityTable>` and `<ListFilter>`. *Note - some features are driven by the `#paymentbuttons` hash in the url.* 
	  - **track** - file consists of all analytics events code related to payment buttons details view. 
  - **Create** - this is the flow which comes into picture when the CTA *Create a Payment Button* is clicked, or an already existing page is being edited or duplicated. 
	  - **index.js**  
		  - consists of logic to open the templates screen if it is a new button being created or fetch the details of the existing page if the edit or duplicate flow is triggered.
		  - consists of function to open different settings modals.
		  - consists of functions to save the payment button, payment button settings and receipt settings. These are passed on to the respective children components if required. 
		  - creates the basic structure of the create flow with the help of children components.  
	  - **components** 
		  - **Form** - consists of all the steps/forms required to be filled-in before creating a payment button. Based on the template selected, specific forms are shown to be filled in a step-wise manner. Also consists of other components common to all the forms.
		  	- **ButtonDetails** - This is the first form/step where details of the button are filled in. Details include Button Label, Button theme, Button Type etc. Based on the button type selected, further forms/steps to be filled in are decided. 
			- **AmountDetails** - consists of code related to the step where all the amount details are collected. Consists of components like the base modal, the advanced options and the types of amount fields like Dynamic Amount, Fixed Amount etc.  
			- **DonationAmountDetails** - this form or step is shown only for button type `Donation Button`. Folder consists of code related to amount fields like Preset Amount and Main Amount where maximum and minimum amount can be set. 
			- **CustomerDetails** - step where the end user's data is collected through fields set up. Folder consists of the code for the base modal etc. 
			- **ReviewAndCreate** - this is the final step where the preview of both the button and form with the configurations made our shown for review before the final creation. 
			- **components** - folder consisting of components common to all the forms like current dropdown, editor modal etc. 
			- **index.js** - based on the type of button, decides the steps to be shown. Also controls the overall flow and the current step to be shown. 
		  - **Preview** - consists of code related to the component responsible for showing a live preview of the payment button being built. Previews are shown for all the steps - How the button looks with the configured theme and label and how the form looks with the configured customer details and amount details. Four button themes are available. Dark, Light, Outline and Brand Color. If brand color is chosen, we use `color.js` from the static repo to figure out the text color based on whether the brand color is dark or light. The color of the payment form is always the color of the brand color (same as checkout as finally checkout takes over while making the payment). *Note - CSS files are separate for the preview and the actual button. Don't forget to maintain consistency between the two.*
		  - **Templates** - consists of code related to the *Select Template* screen shown at the start which let's the user choose a template for the payment button. 
		  - **Sidebar.js** - code related to the progress bar shown on the left side of the screen. Based on the template selected, specific steps are shown. Gives information on the current step, completed steps and the steps left. 
		  - **Topbar.js** - component which renders the bar at the top with the title and the action buttons to open the settings' modals. 
	- **constants** - consists of all constants/enums.
	- **track** - file consists of all analytics events code related to payment buttons creation flow.
  - **components**
	  - **GetCodeModal.js** - all code related to the modal which displays the payment button script generated. Used in the details view and list view
	  - **SettingsModal.js** - code related to the settings modal where the payment button settings can be altered. Used in the list view and creation flow.
	  - **SuccessView.js** - The success screen shown after a payment button has been successfully created or edited. 
	  - **SuccessModal.js** - [deprecated] - was replaced by *SuccessView.js*.
- **SubscriptionButton** - [Skipping this for now as the two product flows are to be merged soon] *Note: the flow is almost same as PaymentButtons though*.  
- **utils.js** - consists of util functions used throughout the product to set and get items from the localStorage. 