import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';

import { AccountConfig, AccountLocale } from './types';

export const ACTIONS = {
  SET_CONFIG: 'SET_CONFIG',
  SET_VALUES: 'SET_VALUES',
  SET_IS_SAVING: 'SET_IS_SAVING',
  SET_IS_LOADING: 'SET_IS_LOADING',
  SET_LAST_SAVED: 'SET_LAST_SAVED',
  SET_VALUE_MODIFIED: 'SET_VALUE_MODIFIED',
  SET_EMAIL_REQUIRED_MODAL_OPEN: 'SET_EMAIL_REQUIRED_MODAL_OPEN',
} as const;

export const CHECKOUT_CONFIG_FIELDS = {
  LOCALE: 'locale',
  LOGO: 'logo',
  LOGO_RAW: 'logoRaw',
  LOGO_RECT: 'logoRect',
  LOGO_RECT_RAW: 'logoRectRaw',
  COLOR: 'color',
  EMAIL: 'email',
  EMAIL_OPTIONAL_CHECKOUT: 'emailOptionalCheckout',
  SHOW_EMAIL_ON_CHECKOUT: 'showEmailOnCheckout',
  CUSTOM_MESSAGE: 'customMessage',
  BRAND_NAME: 'brandName',
  IS_DESKTOP_PREVIEW: 'isDesktopPreview',
} as const;

export const CUSTOM_MESSAGE_BANNER_SCREENS = {
  CONTACT: 'contact',
  ADDRESS: 'address',
  SHIPPING: 'shipping',
  PAYMENT: 'payment',
} as const;

export const CUSTOM_MESSAGE_BANNER_SCREEN_LABELS = {
  [CUSTOM_MESSAGE_BANNER_SCREENS.CONTACT]: 'Contact',
  [CUSTOM_MESSAGE_BANNER_SCREENS.ADDRESS]: 'Address',
  [CUSTOM_MESSAGE_BANNER_SCREENS.SHIPPING]: 'Shipping',
  [CUSTOM_MESSAGE_BANNER_SCREENS.PAYMENT]: 'Payment',
};

const CUSTOM_MESSAGE_INITIAL_CONFIG = [
  {
    name: CUSTOM_MESSAGE_BANNER_SCREENS.CONTACT,
    label: CUSTOM_MESSAGE_BANNER_SCREEN_LABELS[CUSTOM_MESSAGE_BANNER_SCREENS.CONTACT],
    bannerMessageText: '',
    bannerBackgroundColor: '#528FF0',
    bannerTextColor: '#FFFFFF',
    hidden: false,
  },
];

export const CHECKOUT_CONFIG_INITIAL_VALUES: {
  [CHECKOUT_CONFIG_FIELDS.LOCALE]: {
    id: string;
    languageCode: string;
  };
  [CHECKOUT_CONFIG_FIELDS.LOGO]: string;
  [CHECKOUT_CONFIG_FIELDS.LOGO_RAW]: null | File;
  [CHECKOUT_CONFIG_FIELDS.LOGO_RECT]: string;
  [CHECKOUT_CONFIG_FIELDS.LOGO_RECT_RAW]: null | File;
  [CHECKOUT_CONFIG_FIELDS.COLOR]: string;
  [CHECKOUT_CONFIG_FIELDS.EMAIL_OPTIONAL_CHECKOUT]: string;
  [CHECKOUT_CONFIG_FIELDS.SHOW_EMAIL_ON_CHECKOUT]: string;
  [CHECKOUT_CONFIG_FIELDS.EMAIL]: string;
  [CHECKOUT_CONFIG_FIELDS.BRAND_NAME]: string;
  [CHECKOUT_CONFIG_FIELDS.IS_DESKTOP_PREVIEW]: boolean;
  [CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE]: {
    isEnabled: boolean;
    configs: {
      name: string;
      label: string;
      bannerMessageText: string;
      bannerBackgroundColor: string;
      bannerTextColor: string;
      hidden: boolean;
    }[];
  };
} = {
  [CHECKOUT_CONFIG_FIELDS.LOCALE]: {
    id: '',
    languageCode: 'en',
  },
  [CHECKOUT_CONFIG_FIELDS.LOGO]: '',
  [CHECKOUT_CONFIG_FIELDS.LOGO_RAW]: null,
  [CHECKOUT_CONFIG_FIELDS.LOGO_RECT]: '',
  [CHECKOUT_CONFIG_FIELDS.LOGO_RECT_RAW]: null,
  [CHECKOUT_CONFIG_FIELDS.COLOR]: '#528FF0',
  [CHECKOUT_CONFIG_FIELDS.EMAIL_OPTIONAL_CHECKOUT]: '',
  [CHECKOUT_CONFIG_FIELDS.SHOW_EMAIL_ON_CHECKOUT]: '',
  [CHECKOUT_CONFIG_FIELDS.EMAIL]: EmailLessCheckoutConfigOptions.NO,
  [CHECKOUT_CONFIG_FIELDS.BRAND_NAME]: '',
  [CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE]: {
    isEnabled: false,
    configs: CUSTOM_MESSAGE_INITIAL_CONFIG,
  },
  [CHECKOUT_CONFIG_FIELDS.IS_DESKTOP_PREVIEW]: true,
};

const CONFIG_INITIAL_STATE: { accountConfig: AccountConfig; locale: AccountLocale } = {
  accountConfig: {},
  locale: {},
};

export const INITIAL_STATE = {
  config: CONFIG_INITIAL_STATE,
  values: CHECKOUT_CONFIG_INITIAL_VALUES,
  isValueModified: false,
  isEmailRequiredModalOpen: false,
  isSaving: false,
  isLoading: false,
};

export const CONTEXT_INITIAL_STATE = {
  values: INITIAL_STATE.values,
  isValueModified: INITIAL_STATE.isValueModified,
  isEmailRequiredModalOpen: INITIAL_STATE.isEmailRequiredModalOpen,
  isSaving: INITIAL_STATE.isSaving,
  isLoading: INITIAL_STATE.isLoading,
};

export const CUSTOM_MESSAGE_FEATURE_FLAG = 'message_banner_disabled';
