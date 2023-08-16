import isEmpty from 'lodash/isEmpty';

export const getInitialSettings = (initialSettings, currentSettings) =>
  isEmpty(currentSettings) ? initialSettings : [...currentSettings];

export const getGCAnalytics = (giftCardEnabled, value) => (giftCardEnabled ? !!value : null);
