import { INITIAL_STATE } from 'merchant/reducers/magicCheckout/magicXStoreSettings';

import type {
  MagicXConfigDataFormData,
  MagicXConfigDataOnServer,
} from 'merchant/views/MagicCheckout/MagicXStoreSettings/types';

const themeColorRegex = new RegExp('^#[0-9A-Fa-f]{6}$');

export const transformServerDataToForm = (data: {
  sopc_metafields: MagicXConfigDataOnServer;
  shop_plan_name: string;
}): MagicXConfigDataFormData => {
  const transformedData = {
    ...INITIAL_STATE,
  };

  const metafields = data.sopc_metafields || {};
  const isPlusPlan = data.shop_plan_name === 'shopify_plus';

  if (metafields.status === 'live') {
    transformedData.status = true;
  }

  if (metafields.permalinks_flow === false) {
    transformedData.flowType = 'checkout_ui_extensions';
  }

  if (metafields.merchant_theme_color) {
    transformedData.themeColor = metafields.merchant_theme_color;
  }

  if (metafields.is_login_mandatory) {
    transformedData.isLoginMandatory = true;
  }

  if (metafields.is_email_mandatory) {
    transformedData.emailField = 'mandatory';
  } else if (metafields.is_email_optional) {
    transformedData.emailField = 'optional';
  } else {
    transformedData.emailField = 'hidden';
  }

  if (metafields.integrations?.recurpay?.enabled) {
    transformedData.recurpayEnabled = true;
  }

  if (isPlusPlan) {
    if (metafields.cart_page_login === true) {
      transformedData.cartPageLogin = true;
    }

    if (metafields.enable_native_click === true) {
      transformedData.enableNativeClick = true;
    }
  }

  return transformedData;
};

export const transformFormtoServerData = (
  data: MagicXConfigDataFormData,
): MagicXConfigDataOnServer => {
  const transformedData: MagicXConfigDataOnServer = {
    status: data.status ? 'live' : 'test',
    integrations: {
      recurpay: {
        enabled: data.recurpayEnabled,
      },
    },
    cart_page_login: data.cartPageLogin,
    permalinks_flow: data.flowType === 'cart_permalinks',
    is_email_optional: data.emailField === 'optional',
    is_email_mandatory: data.emailField === 'mandatory',
    is_login_mandatory: data.isLoginMandatory,
    enable_native_click: data.enableNativeClick,
    merchant_theme_color: data.themeColor,
  };

  return transformedData;
};

export const validateForm = (data: MagicXConfigDataFormData): string | null => {
  if (!themeColorRegex.test(data.themeColor)) {
    return 'Please enter a valid theme color';
  }

  return null;
};
