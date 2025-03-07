import isEmpty from 'lodash/isEmpty';
import isEqual from 'lodash/isEqual';
import {
  ConfigFeatures,
  AccountConfig,
  CheckoutEditorState,
  CheckoutEditorPayload,
  MerchantCheckoutStyledConfig,
  PaymentConfigInstrument,
  PaymentConfigDisplayPreferences,
  MerchantCheckoutPaymentConfig,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

import {
  CHECKOUT_EMAIL_FEATURE_FLAG,
  EmailLessCheckoutConfigOptions,
  getEmailConfigFlags,
} from 'merchant/reducers/config';
import {
  CHECKOUT_EDITOR_FIELDS,
  CHECKOUT_EDITOR_INITIAL_VALUES,
  EMPTY_LOGO,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  flashCheckoutProps,
  skipCardMandateSummaryProps,
} from 'merchant/views/Settings/Configuration/settings-config-constants';

export const mapCheckoutEmailConfig = (
  email_config: string,
): {
  isEnabled: boolean;
  value: string;
} => {
  if (email_config === EmailLessCheckoutConfigOptions.NO) {
    return {
      isEnabled: false,
      value: EmailLessCheckoutConfigOptions.NO,
    };
  }
  if (email_config === EmailLessCheckoutConfigOptions.OPTIONAL) {
    return {
      isEnabled: true,
      value: EmailLessCheckoutConfigOptions.OPTIONAL,
    };
  }
  return {
    isEnabled: true,
    value: EmailLessCheckoutConfigOptions.MANDATORY,
  };
};

/**
 * Creates a payload for custom message configuration.
 * @param customMessage - The custom message configuration.
 * @param originalConfig - The original checkout configuration state.
 * @returns The payload for custom message configuration, or null if no changes were made.
 */
export const createCustomMessageConfigPayload = (
  customMessage: typeof CHECKOUT_EDITOR_INITIAL_VALUES.customMessage,
  originalConfig: CheckoutEditorState,
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
  values: typeof CHECKOUT_EDITOR_INITIAL_VALUES,
  originalValues: CheckoutEditorState,
) => {
  if (!originalValues) {
    return false;
  }

  const {
    accountLocale,
    accountConfig,
    merchantCheckoutStyledConfig,
    merchantCheckoutSelectedPaymentConfig,
  } = originalValues;

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
    values[CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]
  ) {
    return true;
  }

  if (
    !!getMandatorySummaryPageValue(accountConfig?.features) !==
    values[CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]
  ) {
    return true;
  }

  if (values.logoRaw || (!values.logoRaw && values.logo === '')) {
    return true;
  }

  // Check if the color has changed
  if (accountConfig?.brand_color !== values.color) {
    return true;
  }

  if (values[CHECKOUT_EDITOR_FIELDS.BORDER_STYLE] !== merchantCheckoutStyledConfig?.button?.shape) {
    return true;
  }

  if (
    values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled !==
    merchantCheckoutStyledConfig?.sidebar_graphic?.enabled
  ) {
    return true;
  }

  if (
    values[CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME] !==
    merchantCheckoutStyledConfig?.festivities_enabled
  ) {
    return true;
  }

  if (
    values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.svg !==
    merchantCheckoutStyledConfig?.sidebar_graphic?.svg
  ) {
    return true;
  }

  if (values[CHECKOUT_EDITOR_FIELDS.FONT_FAMILY] !== merchantCheckoutStyledConfig?.text?.font) {
    return true;
  }

  if (values[CHECKOUT_EDITOR_FIELDS.RTB_ENABLED] !== merchantCheckoutStyledConfig?.rtb_enabled) {
    return true;
  }

  if (
    hasPaymentConfigChanged(
      values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG],
      merchantCheckoutSelectedPaymentConfig,
    )
  ) {
    return {
      changed: 'paymentConfig',
    };
  }
  return false;
};

function hasPaymentConfigChanged(
  newConfig: MerchantCheckoutPaymentConfig,
  oldConfig: MerchantCheckoutPaymentConfig | undefined,
) {
  if (!oldConfig) {
    return false
  }
  if (isEqual(newConfig, oldConfig)) {
    return false;
  }
  if (
    hasSequenceChanged(
      oldConfig.checkout_config?.display?.sequence ?? [],
      newConfig.checkout_config?.display?.sequence ?? [],
    )
  ) {
    return true;
  }
  if (
    hasHiddenBlocksChanged(
      oldConfig.checkout_config?.display?.hide ?? [],
      newConfig.checkout_config?.display?.hide ?? [],
    )
  ) {
    return true;
  }
  if (
    hasPreferencesChanged(
      oldConfig.checkout_config?.display?.preferences ?? {},
      newConfig.checkout_config?.display?.preferences ?? {},
    )
  ) {
    return true;
  }
  if (hasDefaultPaymentConfigChanged(oldConfig, newConfig)) {
    return true;
  }
  if (hasPaymentConfigNameChanged(oldConfig.name ?? '', newConfig.name ?? '')) {
    return true;
  }
  if (hasCustomBlocksChanged(oldConfig, newConfig)) {
    return true;
  }
  return false;
}

function hasSequenceChanged(oldSequence: string[], newSequence: string[]) {
  if (oldSequence.length !== newSequence.length) {
    return true;
  }

  for (let i = 0; i < oldSequence.length; i++) {
    if (oldSequence[i] !== newSequence[i]) {
      return true;
    }
  }
  return false;
}

function hasHiddenBlocksChanged(
  oldHiddenBlocks: PaymentConfigInstrument[],
  newHiddenBlocks: PaymentConfigInstrument[],
) {
  if (oldHiddenBlocks.length !== newHiddenBlocks.length) {
    return true;
  }

  const newHiddenBlocksCopy = [...newHiddenBlocks];

  for (const block of oldHiddenBlocks) {
    const matchIndex = newHiddenBlocksCopy.findIndex((newBlock) => isEqual(newBlock, block));
    if (matchIndex === -1) {
      return true;
    }

    // remove the matched block to prevent double-matching
    newHiddenBlocksCopy.splice(matchIndex, 1);
  }
  return false;
}

function hasPreferencesChanged(
  oldPreferences: PaymentConfigDisplayPreferences,
  newPreferences: PaymentConfigDisplayPreferences,
) {
  return !isEqual(oldPreferences, newPreferences);
}

function hasPaymentConfigNameChanged(oldName: string, newName: string) {
  return oldName !== newName;
}

function hasDefaultPaymentConfigChanged(
  oldConfig: MerchantCheckoutPaymentConfig,
  newConfig: MerchantCheckoutPaymentConfig,
) {
  return oldConfig.is_default !== newConfig.is_default;
}

function hasCustomBlocksChanged(
  oldConfig: MerchantCheckoutPaymentConfig,
  newConfig: MerchantCheckoutPaymentConfig,
) {
  return !isEqual(
    oldConfig?.checkout_config?.display?.blocks,
    newConfig?.checkout_config?.display?.blocks,
  );
}

const createEmailConfigPayload = (
  emailConfig: typeof CHECKOUT_EDITOR_INITIAL_VALUES.email,
  originalConfig: CheckoutEditorState,
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
  let emailValue = '';

  if (!emailConfig.isEnabled) {
    isEmailOptional = false;
    isEmailShown = false;
    emailValue = EmailLessCheckoutConfigOptions.NO;
  } else if (
    emailConfig.isEnabled &&
    emailConfig.value === EmailLessCheckoutConfigOptions.OPTIONAL
  ) {
    isEmailOptional = true;
    isEmailShown = true;
    emailValue = EmailLessCheckoutConfigOptions.OPTIONAL;
  } else if (
    emailConfig.isEnabled &&
    emailConfig.value === EmailLessCheckoutConfigOptions.MANDATORY
  ) {
    isEmailOptional = false;
    isEmailShown = true;
    emailValue = EmailLessCheckoutConfigOptions.MANDATORY;
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
  return { data, isEmailShown, isEmailOptional, value: emailValue };
};

/**
 * Creates a payload to save the configuration based on the provided values and original values.
 *
 * @param values - The values representing the new configuration.
 * @param originalValues - The original configuration values.
 * @returns The payload object containing the changes to be saved.
 */
export const createMerchantCheckoutStyledPayloadToSaveConfig = (
  values: typeof CHECKOUT_EDITOR_INITIAL_VALUES,
  originalValues: CheckoutEditorState,
) => {
  const { merchantCheckoutStyledConfig } = originalValues ?? {};
  const payload: MerchantCheckoutStyledConfig = {};

  if (values[CHECKOUT_EDITOR_FIELDS.BORDER_STYLE] !== merchantCheckoutStyledConfig?.button?.shape) {
    payload.button = {
      shape: values[CHECKOUT_EDITOR_FIELDS.BORDER_STYLE],
    };
  }

  if (
    values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled !==
    merchantCheckoutStyledConfig?.sidebar_graphic?.enabled
  ) {
    payload.sidebar_graphic = {
      enabled: values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled,
      svg: values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.svg,
    };
  }

  if (
    values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.svg !==
    merchantCheckoutStyledConfig?.sidebar_graphic?.svg
  ) {
    payload.sidebar_graphic = {
      enabled: values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled,
      svg: values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.svg,
    };
  }

  if (
    values[CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME] !==
    merchantCheckoutStyledConfig?.festivities_enabled
  ) {
    payload.festivities_enabled = values[CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME];
  }

  if (values[CHECKOUT_EDITOR_FIELDS.FONT_FAMILY] !== merchantCheckoutStyledConfig?.text?.font) {
    payload.text = {
      font: values[CHECKOUT_EDITOR_FIELDS.FONT_FAMILY],
    };
  }

  if (values[CHECKOUT_EDITOR_FIELDS.RTB_ENABLED] !== merchantCheckoutStyledConfig?.rtb_enabled) {
    payload.rtb_enabled = values[CHECKOUT_EDITOR_FIELDS.RTB_ENABLED];
  }

  if (values[CHECKOUT_EDITOR_FIELDS.WORDMARK] !== merchantCheckoutStyledConfig?.wordmark_url) {
    payload.wordmark_url = values[CHECKOUT_EDITOR_FIELDS.WORDMARK];
  }

  if (payload && !isEmpty(payload)) {
    const finalPayload = {
      checkout_configuration: {
        checkout_style_config: {
          ...payload,
        },
      },
    };
    return finalPayload;
  }

  return null;
};

/**
 * Creates a payload to save the configuration based on the provided values and original values.
 *
 * @param values - The values representing the new configuration.
 * @param originalValues - The original configuration values.
 * @returns The payload object containing the changes to be saved.
 */
export const createPayloadToSaveConfig = (
  values: typeof CHECKOUT_EDITOR_INITIAL_VALUES,
  originalValues: CheckoutEditorState,
) => {
  const { accountConfig, accountLocale, merchantCheckoutSelectedPaymentConfig } =
    originalValues ?? {};

  const payload: CheckoutEditorPayload = {};

  if (values.color !== accountConfig?.brand_color) {
    payload.brandColor = {
      brand_color: values.color.replace('#', ''),
    };
  }

  if (values.logoRaw) {
    payload.uploadLogo = {
      file: values.logoRaw,
      fileName: 'logo',
    };
  }

  if (accountConfig?.logo_url && !values.logoRaw && values.logo === EMPTY_LOGO) {
    payload.removeLogo = {
      ...accountConfig,
      logo_url: null,
    };
  }

  const merchantCheckoutStyledPayload = createMerchantCheckoutStyledPayloadToSaveConfig(
    values,
    originalValues,
  );

  if (merchantCheckoutStyledPayload) {
    payload.merchantCheckoutStyledConfig = {
      ...merchantCheckoutStyledPayload,
      type: 'patch',
    };
  }

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
    values[CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]
  ) {
    payload.flashCheckout = {
      isFlashCheckoutEnabled: values[CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT],
    };
  }

  if (
    !!getMandatorySummaryPageValue(accountConfig?.features) !==
    values[CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]
  ) {
    payload.mandatorySummaryPage = {
      isMandatorySummaryPageEnabled: values[CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE],
    };
  }

  if (
    merchantCheckoutSelectedPaymentConfig && !isEqual(
      values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG],
      merchantCheckoutSelectedPaymentConfig,
    )
  ) {
    const paymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
    if (
      paymentConfig.checkout_config?.display?.blocks &&
      (Array.isArray(paymentConfig.checkout_config?.display?.blocks) ||
        isEmpty(paymentConfig.checkout_config?.display?.blocks))
    ) {
      // convert empty array to empty object due to api restrictions
      delete paymentConfig.checkout_config?.display?.blocks;
    }
    payload.merchantCheckoutPaymentConfig = {
      type: paymentConfig.config_id ? 'patch' : 'post',
      checkout_configuration: {
        is_payment_config_flag_enabled: false,
        checkout_config_request: paymentConfig,
      },
    };
  }
  return payload;
};
