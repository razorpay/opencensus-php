import { Platform, Plugin } from './types';

export const PLATFORM_TITLE: Record<Platform, string> = {
  [Platform.WEBSITE]: 'Website',
  [Platform.ANDROID]: 'Android App',
  [Platform.IOS]: 'iOS App',
};

export const PLATFORM_CHECK_MAP: Record<Platform, string> = {
  [Platform.WEBSITE]: 'website_present',
  [Platform.ANDROID]: 'android_app_present',
  [Platform.IOS]: 'ios_app_present',
};

export const INTEGRATION_TITLE: Record<Platform, string> = {
  [Platform.WEBSITE]: 'Website',
  [Platform.ANDROID]: 'Android',
  [Platform.IOS]: 'iOS',
};

export const INTEGRATION_GUIDE: Record<Platform, string> = {
  [Platform.WEBSITE]:
    'https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/build-integration/',
  [Platform.IOS]:
    'https://razorpay.com/docs/payments/payment-gateway/react-native-integration/standard/build-integration-ios/',
  [Platform.ANDROID]:
    'https://razorpay.com/docs/payments/payment-gateway/react-native-integration/standard/build-integration-android/',
};

export const NO_PLUGIN_OPTION: Plugin = {
  name: 'None of the above',
};
