export type MagicXConfigDataOnServer = {
  status: 'test' | 'live';
  integrations: {
    recurpay: {
      enabled: boolean;
    };
  };
  cart_page_login: boolean;
  permalinks_flow: boolean;
  is_email_optional: boolean;
  is_email_mandatory: boolean;
  is_login_mandatory: boolean;
  enable_native_click: boolean;
  merchant_theme_color: string;
  cart_config: {
    custom_selector: string;
  };
  product_config: {
    custom_selector: string;
  };
};

export type MagicXConfigDataFormData = {
  themeColor: string;
  status: boolean;
  emailField: 'hidden' | 'mandatory' | 'optional';
  isLoginMandatory: boolean;
  enableNativeClick: boolean;
  flowType: 'cart_permalinks' | 'checkout_ui_extensions';
  cartPageLogin: boolean;
  recurpayEnabled: boolean;
  cartSelector: string;
  productSelector: string;
};
