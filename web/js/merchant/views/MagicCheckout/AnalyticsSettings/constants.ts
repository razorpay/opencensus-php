import lazy from 'merchant/routes/LazyLoader';
import googleAnalyticsIcon from 'assets/google-analytics.png';
import googleAdsIcon from 'assets/google-ads.png';
import facebookAdsIcon from 'assets/facebook-ads.png';

import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const AccountIntegrationPoints = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/common/IntegrationPoints'
    ),
);

const GooggleAnalyticsCredentialsForm = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAnalytics/components/CredentialsForm'
    ),
);

const FacebookAdsCredentialsForm = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/components/FacebookAds/components/CredentialsForm'
    ),
);

const GoogleAdsCredentialsForm = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAds/components/CredentialsForm'
    ),
);

const GoogleAnalytics = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAnalytics'
    ),
);

const GoogleAds = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAds'
    ),
);

const FacebookAds = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/components/FacebookAds'
    ),
);

export const ANALYTICS_PLATFORM = {
  googleAnalytics: {
    key: 'ga4',
    label: 'Google Analytics',
  },
  googleAds: {
    key: 'google_ads',
    label: 'Google Ads',
  },
  facebookAds: {
    key: 'fb',
    label: 'Facebook Ads',
  },
  google: {
    key: 'google',
    label: 'Google account',
  },
};

export const ANALYTICS_SETTINGS_ROUTES = [
  {
    id: 'google-analytics',
    title: ANALYTICS_PLATFORM.googleAnalytics.label,
    className: 'content-container',
    Component: GoogleAnalytics,
  },
  {
    id: 'google-ads',
    title: ANALYTICS_PLATFORM.googleAds.label,
    className: 'content-container',
    Component: GoogleAds,
    condition: (platform: string) => platform !== PLATFORMS.VALUES.WOOCOMMERCE,
  },
  {
    id: 'facebook-ads',
    title: ANALYTICS_PLATFORM.facebookAds.label,
    className: 'content-container',
    Component: FacebookAds,
  },
];

export const GOOGLE_ANALYTICS = {
  tabHeader: 'Google Analytics account',
  inputTableHeader: ANALYTICS_PLATFORM.googleAnalytics.label,
  headerIcon: googleAnalyticsIcon,
};

export const GOOGLE_ADS = {
  tabHeader: 'Google Ads account',
  inputTableHeader: ANALYTICS_PLATFORM.googleAds.label,
  headerIcon: googleAdsIcon,
};

export const FACEBOOK_ADS = {
  tabHeader: 'Facebook Ads account',
  inputTableHeader: ANALYTICS_PLATFORM.facebookAds.label,
  headerIcon: facebookAdsIcon,
};

export const INTEGRATION_TYPE = {
  frontend: 'frontend',
  backend: 'backend',
  both: 'frontend/backend',
};

export const DEFAULT_INTEGRATION_OPTIONS = [
  { label: 'Select Integration', name: '' },
  { label: 'GTag (Frontend)', name: INTEGRATION_TYPE.frontend },
  { label: 'Measurement Protocol (Backend)', name: INTEGRATION_TYPE.backend },
];

export const FACEBOOK_INTEGRATION_OPTIONS = [
  { label: 'Select Integration', name: '' },
  { label: 'Facebook Pixel', name: INTEGRATION_TYPE.frontend },
  { label: 'Facebook Capi', name: INTEGRATION_TYPE.backend },
  { label: 'Both Facebook Pixel & Capi', name: INTEGRATION_TYPE.both },
];

export const INTEGRATION_INFO_TEXT = {
  [ANALYTICS_PLATFORM.googleAnalytics.label]:
    'We recommend to integrate with backend to unlock powerful capabilities and improve performance of your google analytics dashboard.',
  [ANALYTICS_PLATFORM.googleAds.label]:
    'We recommend to integrate with backend to unlock powerful capabilities and improve your advertising performance.',
  [ANALYTICS_PLATFORM.facebookAds.label]:
    'We recommend to integrate with both Facebook Pixel & Capi to unlock powerful capabilities and improve your advertising performance.',
};

export const GOOGLE_ANALYTICS_EVENTS = [
  {
    label: 'Checkout initiated',
    value: 'checkout_initiated',
    infoText: 'Triggered On Click of Magic Button',
  },
  {
    label: 'Add shipping info',
    value: 'add_shipping_info',
    infoText: 'Triggered after user enters shipping info and continues to next screen',
  },
  {
    label: 'Add payment info',
    value: 'add_payment_info',
    infoText: 'Triggered when user chooses payment option',
  },
  {
    label: 'Purchase',
    value: 'purchase',
    infoText: 'Triggered when order is placed',
  },
  {
    label: 'Custom Events',
    value: 'custom_events',
    infoText: 'Custom magic events throughout the customer journey',
  },
];

export const FACEBOOK_ANALYTICS_EVENTS = [
  {
    label: 'Checkout initiated',
    value: 'checkout_initiated',
    infoText: 'Triggered On Click of Magic Button',
  },
  {
    label: 'Add payment info',
    value: 'add_payment_info',
    infoText: 'Triggered when user chooses payment option',
  },
  {
    label: 'Purchase',
    value: 'purchase',
    infoText: 'Triggered when order is placed',
  },
  {
    label: 'Custom Events',
    value: 'custom_events',
    infoText: 'Custom magic events throughout the customer journey',
  },
];

export const GOOGLE_ADS_ANALYTICS_EVENTS = [
  {
    label: 'Conversion event',
    value: 'purchase',
    infoText: 'Triggered when order is placed',
  },
];

export const GOOGLE_ANALYTICS_INTEGRATION_STEPS = [
  {
    header: 'Google Analytics backend integration',
    desc: 'Get complete view of your website, customer and better tracking by doing backend integration.',
    Component: AccountIntegrationPoints,
  },
  {
    header: 'Google Analytics credentials',
    desc: 'Please enter credentials of Google Analytics to integrate backend with us.',
    Component: GooggleAnalyticsCredentialsForm,
  },
];

export const FACEBOOK_ADS_INTEGRATION_STEPS = [
  {
    header: 'Facebook Ads Capi integration',
    desc: 'Boost your Facebook ad campaigns with Capi integration. Gain accurate tracking and powerful insights.',
    Component: AccountIntegrationPoints,
  },
  {
    header: 'Facebook Ads credentials',
    desc: 'Please enter credentials of Facebook Ads to integrate backend with us.',
    Component: FacebookAdsCredentialsForm,
  },
];

export const GOOGLE_ADS_INTEGRATION_STEPS = [
  {
    header: 'Google Ads backend integration',
    desc: 'Unlock accurate tracking, advanced insights. Integrate backend now for enhanced advertising power.',
    Component: AccountIntegrationPoints,
  },
  {
    header: 'Google Ads credentials',
    desc: 'Please enter credentials of Google Ads to integrate backend with us.',
    Component: GoogleAdsCredentialsForm,
  },
];

export const ACCOUNT_LABELS_MAP = {
  [ANALYTICS_PLATFORM.googleAnalytics.key]: {
    platform_user_id: 'Measurement ID',
    access_token: 'API secret token',
  },
  [ANALYTICS_PLATFORM.facebookAds.key]: {
    platform_user_id: 'Pixel ID',
    access_token: 'Access token',
  },
  [ANALYTICS_PLATFORM.googleAds.key]: {
    google_ads_conversion_id: 'Conversion ID',
    google_ads_conversion_label: 'Conversion label',
    platform_user_id: 'Adword account number',
  },
};

export const ENCODED_KEYS = ['access_token', 'google_ads_conversion_label'];

export const DELETE_CONFIRMATION_TEXTS = {
  [ANALYTICS_PLATFORM.googleAnalytics.key]: {
    header: 'Remove google analytics account?',
    desc: "You'll be missing out on the accurate tracking, advanced insights and better ads performance. Are you sure you want to switch to frontend integration?",
    affirmativeLabel: 'Remove account',
    abortLabel: "No don't!",
  },
  [ANALYTICS_PLATFORM.googleAds.key]: {
    header: 'Remove google ads account?',
    desc: "You'll be missing out on the accurate tracking, advanced insights and better ads performance. Are you sure you want to switch to frontend integration?",
    affirmativeLabel: 'Remove account',
    abortLabel: "No don't!",
  },
  [ANALYTICS_PLATFORM.facebookAds.key]: {
    header: 'Remove facebook ads account?',
    desc: "You'll be missing out on the accurate tracking, advanced insights and better ads performance. Are you sure you want to switch to frontend integration?",
    affirmativeLabel: 'Remove account',
    abortLabel: "No don't!",
  },
  [ANALYTICS_PLATFORM.google.key]: {
    header: 'Remove Google account?',
    desc: 'Removing google account will delete all Google ads analytics account as they are associated with it.',
    affirmativeLabel: 'Remove account',
    abortLabel: "No don't!",
  },
};

export const NOTIFICATION_TEXTS = {
  success: {
    addAccount: 'Account added successfully',
    saveEvents: 'Events saved successfully',
    deleteAccount: 'Account deleted successfully',
  },
  error: 'Somthing went wrong, please try again.',
  neutral: `Backend integration is not completed. Please provide the credentials.`,
};

export const INTEGRATION_MODAL_TEXTS = {
  [ANALYTICS_PLATFORM.googleAnalytics.key]: {
    demoVideoInfoText: 'Follow above steps on your GA4 dashboard',
    pointsHeader: 'Go to the settings section',
    videoLink:
      'https://cdn.razorpay.com/static/assets/magic-checkout/ga4-integration-demo-video.mp4',
  },
  [ANALYTICS_PLATFORM.googleAds.key]: {
    demoVideoInfoText: 'Follow above steps on your Google Ads dashboard',
    pointsHeader: 'Go to the settings section',
    videoLink:
      'https://cdn.razorpay.com/static/assets/magic-checkout/googleads-integration-demo-video.mp4',
  },
  [ANALYTICS_PLATFORM.facebookAds.key]: {
    demoVideoInfoText: 'Follow above steps on your Facebook Ads dashboard',
    pointsHeader: 'Go to the FB Capi and Copy ID',
    videoLink:
      'https://cdn.razorpay.com/static/assets/magic-checkout/facebook-integration-demo-video.mp4',
  },
};

export const FB_DEFAULT_EVENTS = {
  checkout_initiated: false,
  add_payment_info: false,
  purchase: false,
  custom_events: false,
};

export const GOOGLE_ADS_DEFAULT_EVENTS = {
  purchase: false,
};

export const GA4_DEFAULT_EVENTS = {
  checkout_initiated: false,
  add_shipping_info: false,
  add_payment_info: false,
  purchase: false,
  custom_events: false,
};
