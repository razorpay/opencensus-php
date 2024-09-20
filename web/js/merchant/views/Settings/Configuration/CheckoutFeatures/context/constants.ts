import { AccountConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context/types';

import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';

import { AccountLocale } from './types';

export const ACTIONS = {
  SET_CONFIG: 'SET_CONFIG',
  SET_VALUES: 'SET_VALUES',
  SET_IS_SAVING: 'SET_IS_SAVING',
  SET_IS_LOADING: 'SET_IS_LOADING',
  SET_LAST_SAVED: 'SET_LAST_SAVED',
  SET_VALUE_MODIFIED: 'SET_VALUE_MODIFIED',
  SET_EMAIL_REQUIRED_MODAL_OPEN: 'SET_EMAIL_REQUIRED_MODAL_OPEN',
} as const;

export const CHECKOUT_FEATURE_FIELDS = {
  LOCALE: 'locale',
  EMAIL: 'email',
  EMAIL_OPTIONAL_CHECKOUT: 'emailOptionalCheckout',
  SHOW_EMAIL_ON_CHECKOUT: 'showEmailOnCheckout',
  CUSTOM_MESSAGE: 'customMessage',
  IS_DESKTOP_PREVIEW: 'isDesktopPreview',
  FLASH_CHECKOUT: 'flashCheckout',
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

export const CHECKOUT_FEATURE_INITIAL_VALUES: {
  [CHECKOUT_FEATURE_FIELDS.LOCALE]: {
    id: string;
    languageCode: string;
  };
  [CHECKOUT_FEATURE_FIELDS.EMAIL_OPTIONAL_CHECKOUT]: string;
  [CHECKOUT_FEATURE_FIELDS.SHOW_EMAIL_ON_CHECKOUT]: string;
  [CHECKOUT_FEATURE_FIELDS.EMAIL]: string;
  [CHECKOUT_FEATURE_FIELDS.IS_DESKTOP_PREVIEW]: boolean;
  [CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE]: {
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
  [CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]: boolean;
} = {
  [CHECKOUT_FEATURE_FIELDS.LOCALE]: {
    id: '',
    languageCode: 'en',
  },
  [CHECKOUT_FEATURE_FIELDS.EMAIL_OPTIONAL_CHECKOUT]: '',
  [CHECKOUT_FEATURE_FIELDS.SHOW_EMAIL_ON_CHECKOUT]: '',
  [CHECKOUT_FEATURE_FIELDS.EMAIL]: EmailLessCheckoutConfigOptions.NO,
  [CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE]: {
    isEnabled: false,
    configs: CUSTOM_MESSAGE_INITIAL_CONFIG,
  },
  [CHECKOUT_FEATURE_FIELDS.IS_DESKTOP_PREVIEW]: true,
  [CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]: false,
};

const CONFIG_INITIAL_STATE: { accountConfig: AccountConfig; locale: AccountLocale } = {
  accountConfig: {},
  locale: {},
};

export const INITIAL_STATE = {
  config: CONFIG_INITIAL_STATE,
  values: CHECKOUT_FEATURE_INITIAL_VALUES,
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

export const CUSTOM_MESSAGE_FEATURE_FLAG = 'message_banner_enabled';
