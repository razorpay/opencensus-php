export type LoginScreenOption = {
  type: 'landing_page' | 'add_to_cart' | 'checkout_init';
  delay: number;
  mandatory?: boolean;
};

export type CustomerConsentType = 'single_selector' | 'separate_selector' | 'none';

export type EmailFlowType = 'collect_missing_email_flow' | 'email_less_flow';

export type SSOResponsePayload = {
  success: boolean;
  data: {
    configs: {
      sso_config: {
        sso_enabled: boolean;
        login_screen_options: LoginScreenOption[];
        customer_consent: CustomerConsentType;
        email_flow: EmailFlowType;
      };
    };
  };
  error: object;
};

export type UpdatePayload = {
  action: string;
  text: string;
};

export type updateThemeResponseType = {
  data: {
    activation_link: string;
  };
  success: boolean;
  status_code: number;
};

export type SSOConfigs = {
  sso_enabled: boolean;
  sso_settings: {
    login_screen_options: LoginScreenOption[];
    customer_consent: CustomerConsentType;
    email_flow: EmailFlowType;
  };
  sso_widget: {
    background_color: string;
    button_color: string;
    font_family: string;
    display_text: {
      heading: string;
      carousel: Array<{ icon: string; text: string }>;
    };
  };
};

export type SSOConfigsPayloadType = {
  merchant_id: string;
  configs: {
    sso_config: SSOConfigs;
  };
};

export type SSOOption = {
  key: string;
  is_editable: boolean;
  is_mobile_view: boolean;
  config: {
    sso_settings: Record<string, unknown>;
    sso_widget: Record<string, unknown>;
    [x]: unknown;
  };
};

export type SSOPreviewContextType = {
  isDesktopPreview: boolean;
  setIsDesktopPreview: React.Dispatch<React.SetStateAction<boolean>>;
};
