import {
  AccountLocale,
  AccountConfig,
  MerchantCheckoutPaymentConfig,
  MerchantCheckoutPaymentMethodDetails,
  SelectedPaymentOption,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import {
  AVAILABLE_BORDER_STYLE,
  AVAILABLE_GRAPHICS,
  AVAILABLE_TITLE_STYLE,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

export const ACTIONS = {
  SET_CONFIG: 'SET_CONFIG',
  SET_VALUES: 'SET_VALUES',
  SET_IS_SAVING: 'SET_IS_SAVING',
  SET_IS_LOADING: 'SET_IS_LOADING',
  SET_LAST_SAVED: 'SET_LAST_SAVED',
  SET_VALUE_MODIFIED: 'SET_VALUE_MODIFIED',
  SET_EMAIL_REQUIRED_MODAL_OPEN: 'SET_EMAIL_REQUIRED_MODAL_OPEN',
  SET_IS_SAVING_TITLE_MODAL_CHANGE: 'SET_IS_SAVING_TITLE_MODAL_CHANGE',
} as const;

export const CHECKOUT_EDITOR_FIELDS = {
  LOCALE: 'locale',
  EMAIL: 'email',
  IS_PAYMENT_CONFIGURATION_CHANGED: 'isPaymentConfigurationChanged',
  IS_CONFIG_SET_AS_DEFAULT_INITIALLY: 'isConfigSetAsDefaultInitially',
  EMAIL_OPTIONAL_CHECKOUT: 'emailOptionalCheckout',
  SHOW_EMAIL_ON_CHECKOUT: 'showEmailOnCheckout',
  CUSTOM_MESSAGE: 'customMessage',
  IS_DESKTOP_PREVIEW: 'isDesktopPreview',
  FLASH_CHECKOUT: 'flashCheckout',
  MANDATORY_SUMMARY_PAGE: 'mandatorySummaryPage',
  SHOW_FINAL_PRICE: 'showFinalPrice',
  LOGO: 'logo',
  LOGO_RAW: 'logoRaw',
  LOGO_RECT: 'logoRect',
  LOGO_RECT_RAW: 'logoRectRaw',
  WORDMARK: 'wordmark',
  WORDMARK_RAW: 'wordmarkRaw',
  COLOR: 'color',
  BRAND_NAME: 'brandName',
  BORDER_STYLE: 'borderRadius',
  FONT_FAMILY: 'fontFamily',
  SIDEBAR_GRAPHIC: 'sidebarGraphic',
  TITLE_STYLE: 'titleStyle',
  RTB_ENABLED: 'rtb_enabled',
  FESTIVAL_THEME: 'festivalTheme',
  SELECTED_PAYMENT_CONFIG: 'selectedPaymentConfig',
  ALL_PAYMENT_CONFIGS: 'allPaymentConfigs',
  PREVIEW_SCREEN: 'previewScreen',
  API_KEY: 'apiKey',
  KEYLESS_HEADER: 'keylessHeader',
  PAYMENT_CONFIG_SCREEN: 'paymentConfigScreen',
  SELECTED_PAYMENT_OPTION: 'selectedPaymentOption',
  ALL_PAYMENT_METHOD_DETAILS: 'allPaymentMethodDetails',
  CURRENT_EXPANDED_CUSTOM_BLOCK: 'currentExpandedCustomBlock',
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

export const EMPTY_LOGO = 'EMPTY_LOGO';
export const EMPTY_WORDMARK = 'EMPTY_WORDMARK';

export const PAYMENT_CONFIG_INITIAL_VALUES = {
  checkout_config: {
    display: {},
  },
  is_default: false,
  name: '',
  type: '',
  config_id: '',
  created_at: '',
  updated_at: '',
  is_deleted: false,
};

export const DEFAULT_PAYMENT_CONFIG: MerchantCheckoutPaymentConfig = {
  name: 'Standard payment options',
  config_id: 'default',
  is_default: false,
  checkout_config: {
    display: {
      hide: [],
      sequence: [],
      preferences: {
        show_default_blocks: true,
      },
    },
  },
  type: 'checkout',
};

export enum STANDARD_PAYMENT_BLOCK_NAMES {
  CARDS = 'card',
  UPI = 'upi',
  NETBANKING = 'netbanking',
  EMI = 'emi',
  WALLET = 'wallet',
  PAYLATER = 'paylater',
  COD = 'cod',
}

export enum PREVIEW_SCREEN {
  METHODS = 'METHODS',
  HOME = 'HOME',
}

export enum PAYMENT_CONFIG_SCREEN {
  CONFIG_LIST = 'CONFIG_LIST',
  CONFIG_DETAILS = 'CONFIG_DETAILS',
}

export const DEFAULT_STANDARD_BLOCKS_SEQUENCE = [
  STANDARD_PAYMENT_BLOCK_NAMES.UPI,
  STANDARD_PAYMENT_BLOCK_NAMES.CARDS,
  STANDARD_PAYMENT_BLOCK_NAMES.EMI,
  STANDARD_PAYMENT_BLOCK_NAMES.NETBANKING,
  STANDARD_PAYMENT_BLOCK_NAMES.WALLET,
  STANDARD_PAYMENT_BLOCK_NAMES.PAYLATER,
  STANDARD_PAYMENT_BLOCK_NAMES.COD,
];

export const MAP_SLUG_TO_BLOCKNAMES = {
  [PaymentMethodsFields.CARDS]: STANDARD_PAYMENT_BLOCK_NAMES.CARDS,
  [PaymentMethodsFields.NETBANKING]: STANDARD_PAYMENT_BLOCK_NAMES.NETBANKING,
  [PaymentMethodsFields.UPI]: STANDARD_PAYMENT_BLOCK_NAMES.UPI,
  [PaymentMethodsFields.WALLET]: STANDARD_PAYMENT_BLOCK_NAMES.WALLET,
  [PaymentMethodsFields.EMI]: STANDARD_PAYMENT_BLOCK_NAMES.EMI,
  [PaymentMethodsFields.PAYLATER]: STANDARD_PAYMENT_BLOCK_NAMES.PAYLATER,
};

export const CARD_NETWORKS_TO_SLUG = {
  mastercard: 'MasterCard',
  visa: 'Visa',
  amex: 'American Express',
  rupay: 'RuPay',
  diners: 'Diners Club',
  maestro: 'Maestro',
  bajaj: 'Bajaj Finserv',
};

export const CHECKOUT_EDITOR_INITIAL_VALUES: {
  [CHECKOUT_EDITOR_FIELDS.LOCALE]: {
    id: string;
    languageCode: string;
  };
  [CHECKOUT_EDITOR_FIELDS.EMAIL]: {
    isEnabled: boolean;
    value: string;
  };
  [CHECKOUT_EDITOR_FIELDS.IS_DESKTOP_PREVIEW]: boolean;
  [CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE]: {
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
  [CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]: boolean;
  [CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]: boolean;
  [CHECKOUT_EDITOR_FIELDS.SHOW_FINAL_PRICE]: boolean;
  [CHECKOUT_EDITOR_FIELDS.LOGO]: string;
  [CHECKOUT_EDITOR_FIELDS.LOGO_RAW]: null | File;
  [CHECKOUT_EDITOR_FIELDS.WORDMARK]: string;
  [CHECKOUT_EDITOR_FIELDS.WORDMARK_RAW]: null | File;
  [CHECKOUT_EDITOR_FIELDS.LOGO_RECT]: string;
  [CHECKOUT_EDITOR_FIELDS.LOGO_RECT_RAW]: null | File;
  [CHECKOUT_EDITOR_FIELDS.COLOR]: string;
  [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: string;
  [CHECKOUT_EDITOR_FIELDS.EMAIL_OPTIONAL_CHECKOUT]: string;
  [CHECKOUT_EDITOR_FIELDS.SHOW_EMAIL_ON_CHECKOUT]: string;
  [CHECKOUT_EDITOR_FIELDS.BRAND_NAME]: string;
  [CHECKOUT_EDITOR_FIELDS.IS_DESKTOP_PREVIEW]: boolean;
  [CHECKOUT_EDITOR_FIELDS.FONT_FAMILY]: string;
  [CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]: {
    enabled: boolean;
    svg: string;
  };
  [CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]: string;
  [CHECKOUT_EDITOR_FIELDS.RTB_ENABLED]: boolean;
  [CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME]: boolean;
  [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: MerchantCheckoutPaymentConfig;
  [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_CONFIGS]: Array<MerchantCheckoutPaymentConfig>;
  [CHECKOUT_EDITOR_FIELDS.PREVIEW_SCREEN]: string;
  [CHECKOUT_EDITOR_FIELDS.API_KEY]: string;
  [CHECKOUT_EDITOR_FIELDS.KEYLESS_HEADER]: string;
  [CHECKOUT_EDITOR_FIELDS.PAYMENT_CONFIG_SCREEN]: PAYMENT_CONFIG_SCREEN;
  [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_OPTION]: SelectedPaymentOption;
  [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: MerchantCheckoutPaymentMethodDetails;
  [CHECKOUT_EDITOR_FIELDS.CURRENT_EXPANDED_CUSTOM_BLOCK]: string;
} = {
  [CHECKOUT_EDITOR_FIELDS.LOCALE]: {
    id: '',
    languageCode: 'en',
  },
  [CHECKOUT_EDITOR_FIELDS.EMAIL]: {
    isEnabled: false,
    value: EmailLessCheckoutConfigOptions.OPTIONAL,
  },
  [CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE]: {
    isEnabled: false,
    configs: CUSTOM_MESSAGE_INITIAL_CONFIG,
  },
  [CHECKOUT_EDITOR_FIELDS.IS_DESKTOP_PREVIEW]: true,
  [CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]: false,
  [CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]: false,
  [CHECKOUT_EDITOR_FIELDS.SHOW_FINAL_PRICE]: false,
  [CHECKOUT_EDITOR_FIELDS.LOGO]: EMPTY_LOGO,
  [CHECKOUT_EDITOR_FIELDS.LOGO_RAW]: null,
  [CHECKOUT_EDITOR_FIELDS.WORDMARK]: EMPTY_WORDMARK,
  [CHECKOUT_EDITOR_FIELDS.WORDMARK_RAW]: null,
  [CHECKOUT_EDITOR_FIELDS.LOGO_RECT]: '',
  [CHECKOUT_EDITOR_FIELDS.LOGO_RECT_RAW]: null,
  [CHECKOUT_EDITOR_FIELDS.COLOR]: '#2950DA',
  [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: AVAILABLE_BORDER_STYLE.ROUNDED,
  [CHECKOUT_EDITOR_FIELDS.EMAIL_OPTIONAL_CHECKOUT]: '',
  [CHECKOUT_EDITOR_FIELDS.SHOW_EMAIL_ON_CHECKOUT]: '',
  [CHECKOUT_EDITOR_FIELDS.BRAND_NAME]: '',
  [CHECKOUT_EDITOR_FIELDS.FONT_FAMILY]: 'Tasa',
  [CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]: {
    enabled: false,
    svg: AVAILABLE_GRAPHICS.NONE,
  },
  [CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]: AVAILABLE_TITLE_STYLE.LOGO_TEXT,
  [CHECKOUT_EDITOR_FIELDS.RTB_ENABLED]: true,
  [CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME]: true,
  [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_CONFIGS]: [],
  [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]: PAYMENT_CONFIG_INITIAL_VALUES,
  [CHECKOUT_EDITOR_FIELDS.PREVIEW_SCREEN]: PREVIEW_SCREEN.HOME,
  [CHECKOUT_EDITOR_FIELDS.API_KEY]: '',
  [CHECKOUT_EDITOR_FIELDS.KEYLESS_HEADER]: '',
  [CHECKOUT_EDITOR_FIELDS.PAYMENT_CONFIG_SCREEN]: PAYMENT_CONFIG_SCREEN.CONFIG_LIST,
  [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_OPTION]: {
    name: '',
    isCustomBlock: false,
  },
  [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: {},
  [CHECKOUT_EDITOR_FIELDS.CURRENT_EXPANDED_CUSTOM_BLOCK]: '',
};

const CONFIG_INITIAL_STATE: {
  accountConfig: AccountConfig;
  locale: AccountLocale;
  selectedPaymentConfig: MerchantCheckoutPaymentConfig;
} = {
  accountConfig: {},
  locale: {},
  selectedPaymentConfig: {},
};

export const INITIAL_STATE = {
  config: CONFIG_INITIAL_STATE,
  values: CHECKOUT_EDITOR_INITIAL_VALUES,
  isValueModified: false,
  isSaving: false,
  isLoading: false,
  isSavingTitleModalChange: false,
  isPaymentConfigChanged: false,
};

export const CONTEXT_INITIAL_STATE = {
  values: INITIAL_STATE.values,
  isValueModified: INITIAL_STATE.isValueModified,
  isPaymentConfigChanged: INITIAL_STATE.isPaymentConfigChanged,
  isSaving: INITIAL_STATE.isSaving,
  isLoading: INITIAL_STATE.isLoading,
  isSavingTitleModalChange: INITIAL_STATE.isSavingTitleModalChange,
};

export const CUSTOM_MESSAGE_FEATURE_FLAG = 'message_banner_disabled';
