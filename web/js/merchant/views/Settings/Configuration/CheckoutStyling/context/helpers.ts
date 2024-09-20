import isEmpty from 'lodash/isEmpty';

import { CHECKOUT_CONFIG_INITIAL_VALUES } from './constants';
import { CheckoutConfigState, CheckoutConfigPayload } from './types';

/**
 * Creates a payload for custom message configuration.
 * @param customMessage - The custom message configuration.
 * @param originalConfig - The original checkout configuration state.
 * @returns The payload for custom message configuration, or null if no changes were made.
 */
export const createCustomMessageConfigPayload = (
  customMessage: typeof CHECKOUT_CONFIG_INITIAL_VALUES.customMessage,
  originalConfig: CheckoutConfigState,
) => {
  const { merchantCheckoutConfig } = originalConfig ?? {};
  const { checkout_message_banner } = merchantCheckoutConfig ?? {};

  let hasChanged = false;

  if (!merchantCheckoutConfig) {
    return null;
  }

  if (Boolean(checkout_message_banner?.hide_message_banner) === customMessage.isEnabled) {
    hasChanged = true;
  }

  if (checkout_message_banner && !isEmpty(checkout_message_banner.banner_config)) {
    // check difference in both the arrays
    const messageBannerConfig = Object.keys(checkout_message_banner.banner_config);

    hasChanged =
      hasChanged ||
      messageBannerConfig.some((key) => {
        const originalConfig = checkout_message_banner.banner_config[key];
        const newConfig = customMessage.configs.find((config) => config.name === key);

        if (!newConfig) {
          return true;
        }

        return (
          originalConfig.text !== newConfig.bannerMessageText ||
          originalConfig.text_color !== newConfig.bannerTextColor ||
          originalConfig.background_color !== newConfig.bannerBackgroundColor
        );
      });
  } else if (customMessage.configs.length && customMessage.isEnabled) {
    // Initial state of custom message, when no custom message is set
    hasChanged = true;
  }

  if (hasChanged) {
    return {
      checkout_configuration: {
        checkout_message_banner: {
          ...checkout_message_banner,
          hide_message_banner: !customMessage.isEnabled,
          banner_config: customMessage.configs.reduce((acc, config) => {
            acc[config.name] = {
              text: config.bannerMessageText,
              text_color: config.bannerTextColor,
              background_color: config.bannerBackgroundColor,
              hidden: config.bannerMessageText ? config.hidden : true,
            };

            return acc;
          }, {}),
        },
      },
    };
  }

  return null;
};

/**
 * Checks if the values have changed compared to the original values.
 * @param values - The new values to compare.
 * @param originalValues - The original values to compare against.
 * @returns A boolean indicating whether the values have changed.
 */
export const hasValuesChanged = (
  values: typeof CHECKOUT_CONFIG_INITIAL_VALUES,
  originalValues: CheckoutConfigState,
) => {
  if (!originalValues) {
    return false;
  }

  const { accountConfig } = originalValues;

  // Check if the logo has changed
  if (values.logoRaw || (!values.logoRaw && values.logo === '')) {
    return true;
  }

  // Check if the color has changed
  if (accountConfig?.brand_color !== values.color) {
    return true;
  }

  return false;
};

/**
 * Creates a payload to save the configuration based on the provided values and original values.
 *
 * @param values - The values representing the new configuration.
 * @param originalValues - The original configuration values.
 * @returns The payload object containing the changes to be saved.
 */
export const createPayloadToSaveConfig = (
  values: typeof CHECKOUT_CONFIG_INITIAL_VALUES,
  originalValues: CheckoutConfigState,
) => {
  const { accountConfig } = originalValues ?? {};

  const payload: CheckoutConfigPayload = {};

  if (values.color !== accountConfig?.brand_color) {
    payload.brandColor = {
      brand_color: values.color.replace('#', ''),
      transaction_report_email: accountConfig?.transaction_report_email?.split(',') ?? [],
    };
  }

  if (values.logoRaw) {
    payload.uploadLogo = {
      file: values.logoRaw,
      fileName: 'logo',
    };
  }

  if (accountConfig?.logo_url && !values.logoRaw && !values.logo) {
    payload.removeLogo = {
      ...accountConfig,
      logo_url: null,
    };
  }

  return payload;
};
