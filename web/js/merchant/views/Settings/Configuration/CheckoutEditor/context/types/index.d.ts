export type CheckoutEditorContext<Values> = {
  values: Values;
  isValueModified: boolean;
  isSaving: boolean;
  isSavingTitleModalChange: boolean;
  isLoading: boolean;
  handleSave: () => void;
  handleSuggestionSubmit: (data: any) => void;
  handleFeedbackSubmit: (data: any) => void;
  handleLocaleChange: (value: string) => void;
  handleSuggestionSubmit: (data) => void;
  handleDiscardAllChanges: () => void;
  handleCustomMessageToggle: (isEnabled: boolean) => void;
  handleCustomMessageTextChange: (index: number, value: string) => void;
  handleCustomMessageTextColorChange: (index: number, value: string) => void;
  handleCustomMessageBackgroundColorChange: (index: number, value: string) => void;
  handlePreviewChange: (value: boolean) => void;
  handleFlashCheckoutToggle: (isEnabled: boolean) => void;
  handleEmailValueChange: (value: string) => void;
  handleEmailToggle: (isEnabled: boolean) => void;
  handleMandatorySummaryPageToggle: (isEnabled: boolean) => void;
  handleShowFinalPriceToggle: (isEnabled: boolean) => void;
  handleLogoChange: (file: File | null, fileName: string) => void;
  handleWordmarkChange: (file: File | null, fileName: string) => void;
  handleRectLogoChange: (file: File | null) => void;
  handleBrandColorChange: (evt?: React.ChangeEvent, defaultValue?: string) => void;
  handleButtonStyleChange: (value: string) => void;
  handleFontStyleChange: (value: string) => void;
  handleSidebarGraphicToggle: (value: boolean) => void;
  handleSidebarGraphicValueChange: (value: string) => void;
  handleTitleStyleChange: (value: string) => void;
  handleRtbEnable: (value: boolean) => void;
  handleBrandNameChange: (value: string) => void;
  handleSaveTitleModal: (setShowEditModal: React.Dispatch<React.SetStateAction<boolean>>) => void;
  handleFestivalThemeToggle: (value: boolean) => void;
  handleSelectedConfigChange: (config: MerchantCheckoutPaymentConfig) => void;
  handlePreviewScreenChange: (screen: string) => void;
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

export type Tag = {
  audience_roles: string;
  block_id: string;
  created_at: number;
  enabled: boolean;
  enabled_at: number;
  id: string;
  page: string;
  tab: string;
  tag: string;
  type: string;
  updated_at: number;
  description?: string;
  name: string;
  audience_splitz_id?: string;
};

export type Block = {
  description?: string;
  id: string;
  is_existing_block: boolean;
  is_feedback_taken: boolean;
  name: string;
  page: string;
  tab: string;
  tags: Tag[];
};

type Blocks = Block[];

export type ConfigFeatures = { feature: string; value: boolean; display_name: string }[];

export type AccountConfig = {
  emailConfig?: {
    isEnabled: boolean;
    value: string;
  };
  features?: ConfigFeatures;
  brand_color?: string;
  transaction_report_email?: string;
  name?: string;
  display_name?: string;
  logo_url?: string;
  logo_large_size_url?: string;
  rect_logo_url?: string;
};

export type CheckoutEditorState = {
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  merchantCheckoutStyledConfig?: MerchantCheckoutStyledConfig;
};

export type CheckoutEditorPayload = {
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
    value?: string;
  } | null;
  flashCheckout?: {
    isFlashCheckoutEnabled?: boolean;
  };
  mandatorySummaryPage?: {
    isMandatorySummaryPageEnabled?: boolean;
  };
  brandColor?: {
    brand_color: string;
  };
  uploadLogo?: {
    file: File;
    fileName: string;
  };
  uploadWordmark?: {
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
  merchantCheckoutStyledConfig?: {
    type: 'patch' | 'post';
    checkout_configuration: {
      checkout_style_config: {
        sidebar_graphic?: {
          enabled: boolean;
          svg: string;
        };
        card?: {
          shape: string;
        };
        button?: {
          shape: string;
        };
        text?: {
          font: string;
        };
        wordmark_url?: string;
        festivities_enabled?: boolean;
      };
    };
  };
  merchantCheckoutBrandConfig?: {
    type: 'patch' | 'post';
    checkout_configuration: {
      checkout_style_config: {
        brand_name?: string;
        title_style?: string;
      };
    };
  };
};

export type MerchantCheckoutStyledConfig = {
  brand_name?: string;
  checkout_logo_url?: string;
  title_style?: string;
  rtb_enabled?: boolean;
  brand_color?: string;
  background_color?: string;
  sidebar_graphic?: {
    enabled: boolean;
    svg: string;
  };
  card?: {
    shape: string;
  };
  button?: {
    shape: string;
  };
  text?: {
    font: string;
  };
  wordmark_url?: string;
  festivities_enabled?: boolean;
};

export type MerchantCheckoutPaymentConfig = {
  checkout_config?: {
    display?: {
      blocks?: {
        [key: string]: PaymentConfigBlock;
      };
      sequence?: string[];
      preferences?: {
        show_default_blocks?: boolean;
      };
    };
  };
  is_default?: boolean;
  name?: string;
  type?: string;
  config_id?: string;
  created_at?: string;
  updated_at?: string;
  is_deleted?: boolean;
};

export type PaymentConfigBlock = {
  [key: string]: {
    name: string;
    instruments: any;
  };
};

export type MerchantCheckoutPaymentConfigs = {
  data: {
    checkout_configuration: {
      checkout_configs: Array<MerchantCheckoutPaymentConfig>;
    };
  };
  loading: boolean;
  error: null;
};

export type MerchantCheckoutBrandConfig = {
  brand_name?: string;
  title_style?: string;
};

export type ColorTextInputProps = {
  label?: string;
  value?: string;
  onChange?: (event: ChangeEvent) => void;
  helpText?: React.ReactNode | string;
  name: string;
};

export type RightChildrenProps = {
  buttons: ButtonStyle[];
};

export type ButtonStyleItem = {
  name: string;
  icon: React.ReactElement;
};

export type TrustedBadgeType = {
  status: {
    original: any;
    badgeStatus: string;
  };
  loading: boolean;
  updatePending?: boolean;
  updateError?: boolean;
  updateAction?: string;
};
