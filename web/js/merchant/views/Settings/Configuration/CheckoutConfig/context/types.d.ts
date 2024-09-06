export type CheckoutConfigContext<Values> = {
  values: Values;
  isValueModified: boolean;
  isEmailRequiredModalOpen: boolean;
  isSaving: boolean;
  isLoading: boolean;

  handleSave: () => void;
  handleLogoChange: (file: File | null) => void;
  handleEmailChange: (value: string) => void;
  handleRectLogoChange: (file: File | null) => void;
  handleLocaleChange: (value: string) => void;
  handleBrandColorChange: (evt?: React.ChangeEvent) => void;
  handleDiscardAllChanges: () => void;
  handleCustomMessageToggle: (isEnabled: boolean) => void;
  handleConfirmEmailRequired: () => void;
  handleCustomMessageTextChange: (index: number, value: string) => void;
  handleCloseEmailRequiredModal: () => void;
  handleCustomMessageTextColorChange: (index: number, value: string) => void;
  handleCustomMessageBackgroundColorChange: (index: number, value: string) => void;
  handlePreviewChange: (value: boolean) => void;
};

export type FetchCheckoutConfigResponse = {
  color?: string;
  transaction_report_email?: string[];
  name?: string;
  display_name?: string;
  logo?: string;
  locale?: {
    id?: string;
    language_code?: string;
  };
};

export type AccountConfig = {
  brand_color?: string;
  transaction_report_email?: string;
  name?: string;
  display_name?: string;
  logo_url?: string;
  logo_large_size_url?: string;
  emailConfig?: string;
  features?: ConfigFeatures;
  rect_logo_url?: string;
};

export type AccountLocale = {
  id?: string;
  name?: string;
  config?: {
    language_code?: string;
  };
};

export type MerchantCheckoutConfig = {
  checkout_message_banner?: {
    banner_config: Record<
      string,
      {
        text: string;
        text_color: string;
        background_color: string;
        hidden: boolean;
      }
    >;
    checkout_message_banner_enabled: boolean;
    hide_message_banner: boolean;
  } | null;
};

export type ConfigFeatures = { feature: string; value: boolean; display_name: string }[];

export type CheckoutConfigState = {
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
};

export type CheckoutConfigPayload = {
  brandColor?: {
    brand_color: string;
    transaction_report_email: string[] | undefined;
  };
  uploadLogo?: {
    file: File;
    fileName: string;
  };
  removeLogo?: {
    logo_url: string | null;
  };
  locale?: {
    type: string;
    config: {
      language_code: string;
    };
    id?: string;
    name?: string;
    is_default?: boolean;
  };
  customMessage?: {
    type: 'patch' | 'post';
    checkout_configuration: {
      checkout_message_banner: {
        checkout_message_banner_enabled?: boolean | undefined;
        hide_message_banner: boolean;
        banner_config: Record<
          string,
          {
            text: string;
            text_color: string;
            background_color: string;
            hidden: boolean;
          }
        >;
      };
    };
  };
  emailConfig?: {
    data: {
      features: Record<string, boolean>;
      should_sync: number;
    };
    isEmailShown: boolean;
    isEmailOptional: boolean;
  } | null;
};
