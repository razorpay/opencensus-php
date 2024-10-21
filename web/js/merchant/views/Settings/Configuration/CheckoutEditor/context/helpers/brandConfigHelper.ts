import isEmpty from 'lodash/isEmpty';
import {
  CheckoutEditorPayload,
  CheckoutEditorState,
  MerchantCheckoutBrandConfig,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

import { AVAILABLE_TITLE_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import {
  CHECKOUT_EDITOR_FIELDS,
  CHECKOUT_EDITOR_INITIAL_VALUES,
  EMPTY_LOGO,
  EMPTY_WORDMARK,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { createMerchantCheckoutStyledPayloadToSaveConfig } from '.';

/**
 * Creates a payload to save the configuration based on the provided values and original values.
 *
 * @param values - The values representing the new configuration.
 * @param originalValues - The original configuration values.
 * @returns The payload object containing the changes to be saved
 */

export const createBrandNamePayloadToSaveConfig = (
  values: typeof CHECKOUT_EDITOR_INITIAL_VALUES,
  originalValues: CheckoutEditorState,
) => {
  const { merchantCheckoutStyledConfig } = originalValues ?? {};
  const payload: MerchantCheckoutBrandConfig = {};

  if (values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] !== merchantCheckoutStyledConfig?.title_style) {
    payload.title_style = values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE];
  }

  if (values[CHECKOUT_EDITOR_FIELDS.BRAND_NAME] !== merchantCheckoutStyledConfig?.brand_name) {
    payload.brand_name = values[CHECKOUT_EDITOR_FIELDS.BRAND_NAME];
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

export const createTitleModalPayloadToSaveConfig = (
  values: typeof CHECKOUT_EDITOR_INITIAL_VALUES,
  originalValues: CheckoutEditorState,
) => {
  const { accountConfig, merchantCheckoutStyledConfig } = originalValues ?? {};

  const payload: CheckoutEditorPayload = {};

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

  if (values.wordmarkRaw) {
    payload.uploadWordmark = {
      file: values.wordmarkRaw,
      fileName: 'wordmark',
    };
  }

  if (
    merchantCheckoutStyledConfig?.wordmark_url &&
    !values.wordmarkRaw &&
    values.wordmark === EMPTY_WORDMARK
  ) {
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
  }

  const merchantCheckoutBrandPayload = createBrandNamePayloadToSaveConfig(values, originalValues);

  if (merchantCheckoutBrandPayload) {
    payload.merchantCheckoutBrandConfig = {
      ...merchantCheckoutBrandPayload,
      type: 'patch',
    };
  }
  return payload;
};

export const checkForTitleStyleDefaultValue = (value?: string) => {
  const availableStyles = Object.values(AVAILABLE_TITLE_STYLE);
  return value && availableStyles.includes(value) ? value : AVAILABLE_TITLE_STYLE.LOGO_TEXT;
};
