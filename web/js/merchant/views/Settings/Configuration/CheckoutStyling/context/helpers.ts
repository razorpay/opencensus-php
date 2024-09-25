import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';
import { CHECKOUT_CONFIG_INITIAL_VALUES } from './constants';
import { CheckoutConfigState, CheckoutConfigPayload } from './types';

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
