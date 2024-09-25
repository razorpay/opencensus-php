export type CheckoutFeatureContext<Values> = {
  values: Values;
  isValueModified: boolean;
  isSaving: boolean;
  isLoading: boolean;

  handleSave: () => void;
  handleLocaleChange: (value: string) => void;
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

export type CheckoutFeatureState = {
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
};

export type CheckoutFeaturePayload = {
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
  flashCheckout?: {
    isFlashCheckoutEnabled?: boolean;
  };
  mandatorySummaryPage?: {
    isMandatorySummaryPageEnabled?: boolean;
  };
};
