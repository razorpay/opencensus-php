import { SSOConfigType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/types';

// sso avaialble config options
export const WIDGET_TIMER_OPTIONS = [
  {
    label: 'The first page your customers sees',
    value: 'landing_page',
    sublabel: 'schedule when the login widget shows up',
  },
  {
    label: 'When a customer adds their very first item to the cart',
    value: 'add_to_cart',
    sublabel: 'schedule when the login widget shows up',
  },
  {
    label: 'When a customer begins the checkout process',
    value: 'checkout_init',
    subcheckbox: 'Make Login mandatory pre-Checkout',
  },
];

export const CUSTOMER_CONSENT_OPTIONS = [
  { label: 'Single selector for Email, WhatsApp, and SMS.', value: 'single_selector' },
  { label: 'Separate selectors for Email, WhatsApp, and SMS.', value: 'separate_selector' },
  { label: 'Do not ask for consent', value: 'no_consent' },
];

export const EMAIL_FLOW_OPTIONS = [
  {
    label: 'Collect Missing Emails (recommended)',
    value: 'collect_missing_email_flow',
  },
  {
    label: 'Email-Less Customers',
    value: 'email_less_flow',
    toolTipText: 'For new customers dummy email id`s will be created and mapped',
  },
];

// default SSO config
export const DEFAULT_SSO_CONFIG: SSOConfigType = {
  isSSOEnabled: false,
  ssoSettings: {
    loginScreenOptions: [{ type: 'landing_page', delay: 2, mandatory: false }],
    customerConsent: 'single_selector',
    emailFlow: 'collect_missing_email_flow',
  },
  ssoWidget: {
    backgroundColor: '#c89d32',
    buttonColor: '#1b0f04',
    fontFamily: 'Tasa',
    displayText: {
      heading: 'Hello again, snoozer! \n Exciting offer waiting for you',
      carousel: [
        { icon: '🎉', text: 'Enjoy hassle free shopping with the best offers applied for you' },
        { icon: '🤩', text: 'Explore unbeatable prices and unmatchable value' },
        { icon: '🛡️', text: '100% secure & spam free, we will not annoy you, pinky promise!' },
      ],
    },
  },
};

// available SSO suported fonts
export const FONT_FAMILY_NAMES = [
  'Inter',
  'Montserrat',
  'Open Sans',
  'Roboto',
  'Noto Sans',
  'Merriweather',
  'Roboto Slab',
  'Rokkitt',
  'Tasa',
  'Space Grotesk',
  'Syne',
  'Familjen Grotesk',
  'Playfair Display',
];

export const SSO_IFRAME_URL =
  'https://api.razorpay.com/v1/magic/widgets/sso?build=fe0617f1d6388fe4e8fa6a83e883b00a07bdf300&key_id=';

export const SSO_FIRST_TIME_USER_CONFIG = {
  sso_enabled: false,
  sso_settings: {
    login_screen_options: [{ type: 'landing_page', delay: 2 }],
    customer_consent: 'single_selector',
    email_flow: 'collect_missing_email_flow',
  },
  sso_widget: {
    background_color: '',
    button_color: '',
    font_family: '',
    display_text: {
      heading: 'Hello again, snoozer! \n Exciting offer waiting for you',
      carousel: [
        { icon: '🎉', text: 'Enjoy hassle free shopping with the best offers applied for you' },
        { icon: '🤩', text: 'Explore unbeatable prices and unmatchable value' },
        { icon: '🛡️', text: '100% secure & spam free, we will not annoy you, pinky promise!' },
      ],
    },
  },
};
