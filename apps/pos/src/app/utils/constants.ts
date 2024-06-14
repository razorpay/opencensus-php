import { MerchantOnboardingSteps, RouteType } from '../types';
import { getUrlFromRouteName } from './helpers';

// constants related to POS module as a whole
export const MODULE_NAME = 'assisted-pos-onboarding';
export const MODULE_BASEURL = '/app/pos-sales';

export const module_routes: RouteType = {
  dashboard: {
    root: '',
  },
  merchants_onboarding: {
    root: 'assisted_onboarding',
    nested_routes: {
      merchant_phone: {
        root: '',
      },
    },
  },
  devices: {
    root: 'devices',
    nested_routes: {
      device_selection: {
        root: '',
      },
      cart: {
        root: 'cart',
      },
    },
  },
};

// constants related to the merchant onboarding steps
export const merchant_onboarding_steps: MerchantOnboardingSteps = [
  {
    title: 'Business Details',
    completed: false,
    cta_link: getUrlFromRouteName('devices', module_routes),
    steps: [
      {
        title: 'Basic Details',
      },
      {
        title: 'Business Address',
      },
      {
        title: 'Bank Details',
      },
      {
        title: 'Business Documents',
      },
      {
        title: 'GST Details',
      },
      {
        title: 'Identity Proof',
      },
      {
        title: 'Shop Images',
      },
      {
        title: 'Additional Details',
      },
    ],
  },
  {
    title: 'Device Selection & Ordering',
    completed: false,
    cta_link: module_routes.devices.root,
    steps: [
      {
        title: 'Device Selection',
      },
      {
        title: 'Ordering Devices',
      },
    ],
  },
  {
    title: 'Payment Methods & Services Selection',
    completed: false,
    cta_link: module_routes.devices.root,
    steps: [
      {
        title: 'Payment Methods & Pricing',
      },
      {
        title: 'VAS & pricing',
      },
    ],
  },
  {
    title: 'Agreement Signing',
    completed: false,
    cta_link: module_routes.devices.root,
    steps: [
      {
        title: 'Agreement Signing',
      },
    ],
  },
];

export const priceAddons = {
  gstPrecentage: 18,
  shippingFee: 0,
};
