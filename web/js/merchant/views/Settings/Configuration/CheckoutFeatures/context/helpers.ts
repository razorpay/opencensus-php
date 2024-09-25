import isEmpty from 'lodash/isEmpty';
import { AccountConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context/types';

import {
  CHECKOUT_EMAIL_FEATURE_FLAG,
  EmailLessCheckoutConfigOptions,
  getEmailConfigFlags,
} from 'merchant/reducers/config';
import {
  flashCheckoutProps,
  skipCardMandateSummaryProps,
} from 'merchant/views/Settings/Configuration/settings-config-constants';

import { CHECKOUT_FEATURE_FIELDS, CHECKOUT_FEATURE_INITIAL_VALUES } from './constants';
import { CheckoutFeatureState, CheckoutFeaturePayload, ConfigFeatures } from './types';

/**
 * Creates a payload for custom message configuration.
 * @param customMessage - The custom message configuration.
 * @param originalConfig - The original checkout configuration state.
 * @returns The payload for custom message configuration, or null if no changes were made.
 */
export const createCustomMessageConfigPayload = (
  customMessage: typeof CHECKOUT_FEATURE_INITIAL_VALUES.customMessage,
  originalConfig: CheckoutFeatureState,
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

const getFeatureFlag = (features: ConfigFeatures | undefined, featureAPIKey: string) => {
  const featureObj = features?.find((feature) => feature.feature === featureAPIKey);
  return featureObj?.value;
};
const getFlashCheckoutValue = (features: AccountConfig['features']) => {
  const isFeatureFlagSet = getFeatureFlag(features, flashCheckoutProps.featureAPIKey);
  return flashCheckoutProps.isFeatureAPIKeyReversed ? !isFeatureFlagSet : isFeatureFlagSet;
};

const getMandatorySummaryPageValue = (features: AccountConfig['features']) => {
  const isFeatureFlagSet = getFeatureFlag(features, skipCardMandateSummaryProps.featureAPIKey);
  return isFeatureFlagSet;
};

/**
 * Checks if the values have changed compared to the original values.
 * @param values - The new values to compare.
 * @param originalValues - The original values to compare against.
 * @returns A boolean indicating whether the values have changed.
 */
export const hasValuesChanged = (
  values: typeof CHECKOUT_FEATURE_INITIAL_VALUES,
  originalValues: CheckoutFeatureState,
) => {
  if (!originalValues) {
    return false;
  }

  const { accountLocale, accountConfig } = originalValues;

  // Check if the locale has changed
  if (accountLocale?.config?.language_code !== values.locale.languageCode) {
    return true;
  }

  // Check if the email has changed
  if (accountConfig?.emailConfig !== values.email) {
    return true;
  }

  // Check if the custom message has changed
  if (createCustomMessageConfigPayload(values.customMessage, originalValues)) {
    return true;
  }

  // Check if the flash checkout feature has changed
  if (
    !!getFlashCheckoutValue(accountConfig.features) !==
    values[CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]
  ) {
    return true;
  }

  if (
    !!getMandatorySummaryPageValue(accountConfig?.features) !==
    values[CHECKOUT_FEATURE_FIELDS.MANDATORY_SUMMARY_PAGE]
  ) {
    return true;
  }

  return false;
};

const createEmailConfigPayload = (
  emailConfig: typeof CHECKOUT_FEATURE_INITIAL_VALUES.email,
  originalConfig: CheckoutFeatureState,
) => {
  if (
    originalConfig?.accountConfig?.emailConfig === emailConfig ||
    !originalConfig.accountConfig?.features?.length
  ) {
    return null;
  }

  const existingEmailConfigFlag = getEmailConfigFlags(originalConfig.accountConfig?.features);
  let isEmailOptional = false;
  let isEmailShown = false;

  if (!emailConfig.isEnabled) {
    isEmailOptional = false;
    isEmailShown = false;
  } else if (
    emailConfig.isEnabled &&
    emailConfig.value === EmailLessCheckoutConfigOptions.OPTIONAL
  ) {
    isEmailOptional = true;
    isEmailShown = true;
  } else if (
    emailConfig.isEnabled &&
    emailConfig.value === EmailLessCheckoutConfigOptions.MANDATORY
  ) {
    isEmailOptional = false;
    isEmailShown = true;
  }

  const data = {
    features: {},
    should_sync: 0,
  };

  // add only if feature flags are changing from existing
  if (existingEmailConfigFlag.emailShown !== isEmailShown) {
    data.features[CHECKOUT_EMAIL_FEATURE_FLAG.SHOW_EMAIL_ON_CHECKOUT] = isEmailShown;
  }
  if (existingEmailConfigFlag.emailOptional !== isEmailOptional) {
    data.features[CHECKOUT_EMAIL_FEATURE_FLAG.EMAIL_OPTIONAL_ON_CHECKOUT] = isEmailOptional;
  }
  return { data, isEmailShown, isEmailOptional };
};

/**
 * Creates a payload to save the configuration based on the provided values and original values.
 *
 * @param values - The values representing the new configuration.
 * @param originalValues - The original configuration values.
 * @returns The payload object containing the changes to be saved.
 */
export const createPayloadToSaveConfig = (
  values: typeof CHECKOUT_FEATURE_INITIAL_VALUES,
  originalValues: CheckoutFeatureState,
) => {
  const { accountConfig, accountLocale } = originalValues ?? {};

  const payload: CheckoutFeaturePayload = {};

  if (accountLocale?.config?.language_code !== values.locale.languageCode) {
    payload.locale = {
      type: 'locale',
      config: {
        ...accountLocale?.config,
        language_code: values.locale.languageCode,
      },
    };

    if (accountLocale?.id) {
      payload.locale.id = accountLocale.id;
    } else {
      payload.locale.name = '_';
      payload.locale.is_default = true;
    }
  }

  const customMessagePayload = createCustomMessageConfigPayload(
    values.customMessage,
    originalValues,
  );

  if (customMessagePayload) {
    payload.customMessage = {
      ...customMessagePayload,
      type: isEmpty(originalValues?.merchantCheckoutConfig?.checkout_message_banner?.banner_config)
        ? 'post'
        : 'patch',
    };
  }
  const emailConfigPayload = createEmailConfigPayload(values.email, originalValues);
  if (emailConfigPayload) {
    payload.emailConfig = emailConfigPayload;
  }

  if (
    !!getFlashCheckoutValue(accountConfig?.features) !==
    values[CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]
  ) {
    payload.flashCheckout = {
      isFlashCheckoutEnabled: values[CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT],
    };
  }

  if (
    !!getMandatorySummaryPageValue(accountConfig?.features) !==
    values[CHECKOUT_FEATURE_FIELDS.MANDATORY_SUMMARY_PAGE]
  ) {
    payload.mandatorySummaryPage = {
      isMandatorySummaryPageEnabled: values[CHECKOUT_FEATURE_FIELDS.MANDATORY_SUMMARY_PAGE],
    };
  }
  return payload;
};
