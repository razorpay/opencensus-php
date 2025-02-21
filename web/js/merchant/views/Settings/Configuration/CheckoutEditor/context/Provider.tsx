import React, { useCallback, useEffect, useMemo, useReducer } from 'react';
import { useToast } from '@razorpay/blade/components';
import cloneDeep from 'lodash/cloneDeep';
import isEmpty from 'lodash/isEmpty';
import {
  AccountConfig,
  AccountLocale,
  ConfigFeatures,
  MerchantCheckoutConfig,
  MerchantCheckoutPaymentConfig,
  MerchantCheckoutPaymentConfigs,
  MerchantCheckoutPaymentMethodDetails,
  MerchantCheckoutStyledConfig,
  TrustedBadgeType,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import {
  trackFlashCheckoutFailure,
  trackFlashCheckoutInitiate,
  trackFlashCheckoutSuccess,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/utils/flashCheckout';
import {
  trackMandateSummaryPageFailure,
  trackMandateSummaryPageInitiate,
  trackMandateSummaryPageSuccess,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/utils/mandateSummaryPage';
import track from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/TrustedBadge/track';
import { isRazorpayTrustedBadgeActive } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/TrustedBadge/utils';
import {
  getHandleConfigNameChange,
  getHandleCurrentExpandedCustomBlockChange,
  getHandleOriginalPaymentConfigChange,
  getHandlePaymentConfigScreenChange,
  getHandleSelectedConfigChange,
  getHandleSelectedPaymentOptionChange,
  getHandleSetConfigAsDefault,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/updater';
import {
  ACTIONS,
  INITIAL_STATE,
  CHECKOUT_EDITOR_FIELDS,
  CUSTOM_MESSAGE_BANNER_SCREEN_LABELS,
  EMPTY_LOGO,
  EMPTY_WORDMARK,
  CHECKOUT_EDITOR_INITIAL_VALUES,
  DEFAULT_PAYMENT_CONFIG,
  PAYMENT_CONFIG_SCREEN,
  PREVIEW_SCREEN,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { checkoutEditorContext } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/createContext';
import {
  createPayloadToSaveConfig,
  hasValuesChanged,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/helpers';
import {
  checkForTitleStyleDefaultValue,
  createTitleModalPayloadToSaveConfig,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/helpers/brandConfigHelper';
import { checkoutFeatureReducer } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/reducer';
import {
  flashCheckoutProps,
  skipCardMandateSummaryProps,
} from 'merchant/views/Settings/Configuration/settings-config-constants';
import sendToSegment from 'merchant/views/Settings/Configuration/CheckoutEditor/track';

export type CheckoutEditorProviderProps = {
  children: React.ReactNode;
  trustedBadge: TrustedBadgeType;
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale | null;
  merchantCheckoutStyledConfig?: MerchantCheckoutStyledConfig;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  merchantCheckoutPaymentConfigs?: MerchantCheckoutPaymentConfigs;
  selectedPaymentConfig?: MerchantCheckoutPaymentConfig;
  uploadLogo: (file: File, fileName: string) => Promise<unknown>;
  uploadWordmark: (file: File, fileName: string) => Promise<unknown>;
  removeLogo: (payload: Record<string, string | null>) => Promise<unknown>;
  saveLocale: (payload: unknown) => Promise<unknown>;
  updateFeatures: (payload: unknown, current?: unknown) => Promise<unknown>;
  createMerchantCheckoutConfig: (payload: unknown) => Promise<unknown>;
  createSuggestion: (payload: unknown) => Promise<unknown>;
  createFeedback: (payload: unknown) => Promise<unknown>;
  showNotification: (payload: unknown) => void;
  updateConfig: (payload: unknown) => Promise<unknown>;
  createMerchantCheckoutStylingConfig: (payload: unknown) => Promise<unknown>;
  createMerchantCheckoutBrandConfig: (payload: unknown) => Promise<unknown>;
  updateEmailConfig: (payload: unknown) => Promise<unknown>;
  createMerchantCheckoutPaymentConfig: (payload: unknown) => Promise<unknown>;
  apiKey?: string;
  merchantCheckoutPaymentMethodDetails?: MerchantCheckoutPaymentMethodDetails;
};

const CheckoutEditorProvider = ({
  children,
  accountConfig,
  trustedBadge,
  uploadLogo,
  uploadWordmark,
  removeLogo,
  updateConfig,
  accountLocale,
  merchantCheckoutConfig,
  saveLocale,
  updateFeatures,
  createMerchantCheckoutConfig,
  createSuggestion,
  createFeedback,
  merchantCheckoutStyledConfig,
  merchantCheckoutPaymentConfigs,
  selectedPaymentConfig,
  createMerchantCheckoutStylingConfig,
  createMerchantCheckoutPaymentConfig,
  createMerchantCheckoutBrandConfig,
  showNotification,
  updateEmailConfig,
  apiKey,
  merchantCheckoutPaymentMethodDetails,
}: CheckoutEditorProviderProps): JSX.Element => {
  const [state, dispatch] = useReducer(checkoutFeatureReducer, INITIAL_STATE);
  const toast = useToast();

  const hasValuesChangedInContext = useMemo(
    () => hasValuesChanged(state.values, state.config),
    [state.values, state.config],
  );

  let isValueModified = false;
  let isPaymentConfigChanged = false;

  if (typeof hasValuesChangedInContext !== 'boolean') {
    isPaymentConfigChanged = hasValuesChangedInContext?.changed === 'paymentConfig';
    isValueModified = isPaymentConfigChanged;
  } else {
    isValueModified = hasValuesChangedInContext;
  }

  const setValue = (
    key: string,
    value:
      | string
      | Record<string, string | boolean>
      | File
      | null
      | boolean
      | MerchantCheckoutConfig,
  ) => {
    dispatch({ type: ACTIONS.SET_VALUES, payload: { [key]: value } });
  };

  const setConfig = (
    key: string,
    value:
      | string
      | Record<string, string | boolean>
      | File
      | null
      | boolean
      | MerchantCheckoutConfig,
  ) => {
    dispatch({ type: ACTIONS.SET_CONFIG, payload: { [key]: value } });
  };

  const setIsSaving = (isSaving: boolean) => {
    dispatch({ type: ACTIONS.SET_IS_SAVING, payload: isSaving });
  };

  const setIsLoading = (isLoading: boolean) => {
    dispatch({ type: ACTIONS.SET_IS_LOADING, payload: isLoading });
  };

  const setIsSavingTitleModal = (isSaving: boolean) => {
    dispatch({ type: ACTIONS.SET_IS_SAVING_TITLE_MODAL_CHANGE, payload: isSaving });
  };

  const handleLocaleChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.LOCALE, {
      id: state.values[CHECKOUT_EDITOR_FIELDS.LOCALE].id,
      languageCode: value,
    });
  };

  const handleBrandColorChange = (evt?: React.ChangeEvent, defaultValue?: string) => {
    if (defaultValue) {
      setValue(CHECKOUT_EDITOR_FIELDS.COLOR, defaultValue);
      return;
    }

    if (evt) {
      const { value } = evt.target as HTMLInputElement;
      setValue(CHECKOUT_EDITOR_FIELDS.COLOR, value);
    }
  };

  const handleButtonStyleChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.BORDER_STYLE, value);
  };

  const handleFontStyleChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.FONT_FAMILY, value);
  };

  const handleTitleStyleChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.TITLE_STYLE, value);
  };

  const handleRtbEnable = (value: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.RTB_ENABLED, value);
  };

  const handleLogoChange = (value: File | null, fileName: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.LOGO_RAW, value);
    setValue(CHECKOUT_EDITOR_FIELDS.LOGO, fileName ?? EMPTY_LOGO);
  };

  const handleWordmarkChange = (value: File | null, fileName: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.WORDMARK_RAW, value);
    setValue(CHECKOUT_EDITOR_FIELDS.WORDMARK, fileName ?? EMPTY_WORDMARK);
  };

  const handleSidebarGraphicToggle = (value: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC, {
      ...state.values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC],
      enabled: value,
    });
  };

  const handleSidebarGraphicValueChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC, {
      ...state.values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC],
      svg: value,
    });
  };

  const handleFestivalThemeToggle = (value: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME, value);
  };

  const handleRectLogoChange = (value: File | null) => {
    setValue(CHECKOUT_EDITOR_FIELDS.LOGO_RECT_RAW, value);

    if (value === null) {
      setValue(CHECKOUT_EDITOR_FIELDS.LOGO_RECT, '');
    }
  };

  const handleCustomMessageTextChange = (index: number, value) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = { ...currentConfigs.configs[index], bannerMessageText: value };

    setValue(CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageBackgroundColorChange = (index: number, value: string) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = {
      ...currentConfigs.configs[index],
      bannerBackgroundColor: value,
    };

    setValue(CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageTextColorChange = (index: number, value: string) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = { ...currentConfigs.configs[index], bannerTextColor: value };

    setValue(CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageToggle = (isEnabled: boolean) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.isEnabled = isEnabled;

    setValue(CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleFlashCheckoutToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT, isEnabled);
  };

  const handleMandatorySummaryPageToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE, isEnabled);
  };

  const handleShowFinalPriceToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.SHOW_FINAL_PRICE, isEnabled);
  };

  const handleBrandNameChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.BRAND_NAME, value);
  };

  const handlePreviewScreenChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.PREVIEW_SCREEN, value);
  };

  const setAccountConfigToState = useCallback(() => {
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
    if (accountConfig) {
      dispatch({
        type: ACTIONS.SET_VALUES,
        payload: {
          [CHECKOUT_EDITOR_FIELDS.EMAIL]: accountConfig.emailConfig,
          [CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]: !!getFlashCheckoutValue(accountConfig.features),
          [CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]: !!!!getMandatorySummaryPageValue(
            accountConfig?.features,
          ),
          [CHECKOUT_EDITOR_FIELDS.COLOR]: accountConfig.brand_color,
          [CHECKOUT_EDITOR_FIELDS.LOGO]: accountConfig.logo_url ?? EMPTY_LOGO,
          [CHECKOUT_EDITOR_FIELDS.LOGO_RAW]: null,
          [CHECKOUT_EDITOR_FIELDS.LOGO_RECT]: accountConfig.rect_logo_url,
          [CHECKOUT_EDITOR_FIELDS.LOGO_RECT_RAW]: null,
        },
      });

      dispatch({
        type: ACTIONS.SET_CONFIG,
        payload: {
          accountConfig,
        },
      });
    }
  }, [accountConfig]);

  const setMerchantCheckoutStyledConfigToState = useCallback(() => {
    if (merchantCheckoutStyledConfig) {
      dispatch({
        type: ACTIONS.SET_VALUES,
        payload: {
          [CHECKOUT_EDITOR_FIELDS.BORDER_STYLE]: merchantCheckoutStyledConfig?.button?.shape || '',
          [CHECKOUT_EDITOR_FIELDS.FONT_FAMILY]: merchantCheckoutStyledConfig?.text?.font,
          [CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]: merchantCheckoutStyledConfig?.sidebar_graphic,
          [CHECKOUT_EDITOR_FIELDS.FESTIVAL_THEME]:
            merchantCheckoutStyledConfig?.festivities_enabled,
          [CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]: checkForTitleStyleDefaultValue(
            merchantCheckoutStyledConfig?.title_style,
          ),
          [CHECKOUT_EDITOR_FIELDS.BRAND_NAME]: merchantCheckoutStyledConfig?.brand_name,
          [CHECKOUT_EDITOR_FIELDS.WORDMARK]:
            merchantCheckoutStyledConfig?.wordmark_url ?? EMPTY_WORDMARK,
          [CHECKOUT_EDITOR_FIELDS.RTB_ENABLED]:
            merchantCheckoutStyledConfig?.rtb_enabled ?? isRazorpayTrustedBadgeActive(trustedBadge)
              ? CHECKOUT_EDITOR_INITIAL_VALUES.rtb_enabled
              : undefined,
        },
      });

      dispatch({
        type: ACTIONS.SET_CONFIG,
        payload: {
          merchantCheckoutStyledConfig,
        },
      });

      track.logRTBConfigAPIResponse(merchantCheckoutStyledConfig.rtb_enabled);
    }
  }, [merchantCheckoutStyledConfig, trustedBadge]);

  const setAPIKeyToState = useCallback(() => {
    if (apiKey) {
      dispatch({
        type: ACTIONS.SET_VALUES,
        payload: {
          [CHECKOUT_EDITOR_FIELDS.API_KEY]: apiKey,
        },
      });
    }
  }, [apiKey]);

  const setMerchantCheckoutPaymentConfigToState = useCallback(() => {
    if (merchantCheckoutPaymentConfigs) {
      if (merchantCheckoutPaymentConfigs.loading) {
        setIsLoading(true);
      } else {
        const razorpayConfig = !selectedPaymentConfig
          ? {
              ...DEFAULT_PAYMENT_CONFIG,
              is_default: true,
            }
          : DEFAULT_PAYMENT_CONFIG;
        dispatch({
          type: ACTIONS.SET_VALUES,
          payload: {
            [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_CONFIGS]:
              merchantCheckoutPaymentConfigs?.data ?? [],
            [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]:
              selectedPaymentConfig ?? razorpayConfig,
            [CHECKOUT_EDITOR_FIELDS.PREVIEW_SCREEN]: PREVIEW_SCREEN.HOME,
            [CHECKOUT_EDITOR_FIELDS.IS_CONFIG_SET_AS_DEFAULT_INITIALLY]:
              selectedPaymentConfig?.is_default ?? false,
            [CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_OPTION]: {
              name: 'home',
              isCustomBlock: false,
            },
          },
        });

        dispatch({
          type: ACTIONS.SET_CONFIG,
          payload: {
            merchantCheckoutSelectedPaymentConfig: selectedPaymentConfig ?? razorpayConfig,
          },
        });
      }
    }
  }, [merchantCheckoutPaymentConfigs, selectedPaymentConfig]);

  const setMerchantCheckoutPaymentMethodDetailsToState = useCallback(() => {
    if (merchantCheckoutPaymentMethodDetails) {
      dispatch({
        type: ACTIONS.SET_VALUES,
        payload: {
          [CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS]: merchantCheckoutPaymentMethodDetails,
        },
      });
    }
  }, [merchantCheckoutPaymentMethodDetails]);

  const setAccountLocaleToState = useCallback(() => {
    if (accountLocale) {
      dispatch({
        type: ACTIONS.SET_VALUES,
        payload: {
          locale: {
            id: accountLocale.id,
            languageCode: accountLocale.config?.language_code,
          },
        },
      });

      dispatch({
        type: ACTIONS.SET_CONFIG,
        payload: {
          accountLocale,
        },
      });
    }
  }, [accountLocale]);

  const setMerchantConfigToState = useCallback(
    (values) => {
      if (merchantCheckoutConfig) {
        const { banner_config, hide_message_banner: isMessageBannerHidden } =
          merchantCheckoutConfig.checkout_message_banner ?? {};

        const configs = isEmpty(banner_config)
          ? values[CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE].configs.map((item) => ({
              ...item,
              bannerBackgroundColor: values[CHECKOUT_EDITOR_FIELDS.COLOR],
            }))
          : Object.keys(banner_config ?? {}).map((key) => {
              const item = banner_config?.[key];
              const label = CUSTOM_MESSAGE_BANNER_SCREEN_LABELS[key];

              return {
                name: key,
                label,
                bannerMessageText: item?.text,
                bannerBackgroundColor: item?.background_color,
                bannerTextColor: item?.text_color,
              };
            });

        if (isMessageBannerHidden !== undefined) {
          setValue(CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE, {
            isEnabled: !isMessageBannerHidden,
            configs,
          });
        }

        dispatch({
          type: ACTIONS.SET_CONFIG,
          payload: {
            merchantCheckoutConfig: {
              ...merchantCheckoutConfig,
              checkout_message_banner: {
                banner_config,
                hide_message_banner:
                  isMessageBannerHidden ?? !values[CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE].isEnabled,
              },
            },
          },
        });
      }
    },
    [merchantCheckoutConfig],
  );

  const handleDiscardAllChanges = () => {
    setAccountConfigToState();
    setAccountLocaleToState();
    setMerchantCheckoutStyledConfigToState();
    setMerchantConfigToState(state.values);
    discardPaymentConfigChanges();
  };

  const handleFeedbackSubmit = async (data) => {
    await createFeedback({ type: 'post', data });
    selfServeTrackSuccess({
      selfServeAction: 'Feedback Added',
      page: 'Checkout',
      screen: 'Settings',
    });
  };

  const handleSuggestionSubmit = async (data) => {
    try {
      await createSuggestion({ type: 'post', data });
      showNotification({ type: 'success', message: 'Suggestion submitted successfully' });
      sendToSegment(
        'create feature suggestion',
        'submit',
        { suggestion: data.suggestion },
        'Checkout Settings',
        'suggestion',
      );
    } catch (error) {
      const { errors, message } = error as { errors: string[]; message: string };
      showNotification({ type: 'error', message: errors?.[0] ?? message });
    }
  };

  const handleCurrentExpandedCustomBlockChange =
    getHandleCurrentExpandedCustomBlockChange(setValue);

  const handleSaveTitleModal = async (setShowEditModal) => {
    setIsSavingTitleModal(true);
    const payload = createTitleModalPayloadToSaveConfig(state.values, state.config);
    try {
      if (payload.uploadLogo) {
        try {
          await uploadLogo(payload.uploadLogo.file, payload.uploadLogo.fileName);
          selfServeTrackSuccess({
            selfServeAction: 'Brand Logo Uploaded',
            page: 'Config',
            screen: 'Settings',
          });
        } catch (error) {
          const { errors } = error as { errors: string[] };
          throw new Error(errors?.[0]);
        }
      }

      if (payload.removeLogo) {
        try {
          await removeLogo(payload.removeLogo);
          selfServeTrackSuccess({
            selfServeAction: 'Brand Logo Removed',
            page: 'Config',
            screen: 'Settings',
          });
        } catch (error) {
          const { errors } = error as { errors: string[] };
          throw new Error(errors?.[0]);
        }
      }

      if (payload.uploadWordmark) {
        try {
          await uploadWordmark(payload.uploadWordmark.file, payload.uploadWordmark.fileName);
          selfServeTrackSuccess({
            selfServeAction: 'Brand Wordmark Uploaded',
            page: 'Config',
            screen: 'Settings',
          });
        } catch (error) {
          const { errors } = error as { errors: string[] };
          throw new Error(errors?.[0]);
        }
      }

      if (payload.merchantCheckoutStyledConfig) {
        await createMerchantCheckoutStylingConfig(payload.merchantCheckoutStyledConfig);
        selfServeTrackSuccess({
          selfServeAction: 'Merchant Checkout Style Changes',
          page: 'Config',
          screen: 'Settings',
        });
      }

      if (payload.merchantCheckoutBrandConfig) {
        try {
          if (
            payload.merchantCheckoutBrandConfig?.checkout_configuration?.checkout_style_config
              ?.brand_name
          ) {
            await createMerchantCheckoutBrandConfig(payload.merchantCheckoutBrandConfig);
          } else {
            await createMerchantCheckoutStylingConfig(payload.merchantCheckoutBrandConfig);
          }
          selfServeTrackSuccess({
            selfServeAction: 'Merchant Checkout Style Changes',
            page: 'Config',
            screen: 'Settings',
          });
        } catch (error) {
          const { message } = error as { errors: string[]; message: string };
          throw new Error(message);
        }
      }
      toast.show({ content: 'Changes saved successfully', color: 'positive', autoDismiss: true });
      setShowEditModal(false);
    } catch (error) {
      const { errors, message } = error as { errors: string[]; message: string };
      toast.show({ content: errors?.[0] ?? message, color: 'negative', autoDismiss: true });
    } finally {
      setIsSavingTitleModal(false);
    }
  };

  const handlePreviewChange = (value: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.IS_DESKTOP_PREVIEW, value);
  };

  const handleEmailToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_EDITOR_FIELDS.EMAIL, {
      ...state.values[CHECKOUT_EDITOR_FIELDS.EMAIL],
      isEnabled,
    });
  };

  const handleEmailValueChange = (value: string) => {
    setValue(CHECKOUT_EDITOR_FIELDS.EMAIL, {
      ...state.values[CHECKOUT_EDITOR_FIELDS.EMAIL],
      value,
    });
  };

  const handleSelectedConfigChange = getHandleSelectedConfigChange(setValue);
  const handleOriginalPaymentConfigChange = getHandleOriginalPaymentConfigChange(setConfig);
  const handleConfigNameChange = getHandleConfigNameChange(setValue);
  const handleSetConfigAsDefault = getHandleSetConfigAsDefault(setValue);
  const handlePaymentConfigScreenChange = getHandlePaymentConfigScreenChange(setValue);
  const handleSelectedPaymentOptionChange = getHandleSelectedPaymentOptionChange(setValue);

  function discardPaymentConfigChanges() {
    const originalConfig = state.config?.merchantCheckoutSelectedPaymentConfig ?? {};
    if (originalConfig.config_id === DEFAULT_PAYMENT_CONFIG.config_id) {
      handlePaymentConfigScreenChange(PAYMENT_CONFIG_SCREEN.CONFIG_LIST);
    }
    handleSelectedConfigChange(originalConfig);
    handleSelectedPaymentOptionChange({ name: 'home', isCustomBlock: false });
    handlePreviewScreenChange(PREVIEW_SCREEN.HOME);
    handleCurrentExpandedCustomBlockChange('');
  }

  const handleSave = async () => {
    const payload = createPayloadToSaveConfig(state.values, state.config);
    try {
      setIsSaving(true);

      if (payload.brandColor) {
        await updateConfig(payload.brandColor);
        selfServeTrackSuccess({
          selfServeAction: 'Brand Logo Uploaded',
          page: 'Config',
          screen: 'Settings',
        });
      }

      if (payload.merchantCheckoutStyledConfig) {
        await createMerchantCheckoutStylingConfig(payload.merchantCheckoutStyledConfig);
        selfServeTrackSuccess({
          selfServeAction: 'Merchant Checkout Style Changes',
          page: 'Config',
          screen: 'Settings',
        });
      }

      if (payload.locale) {
        await saveLocale(payload.locale);
        selfServeTrackSuccess({
          selfServeAction: 'Language Changed',
          page: 'Config',
          screen: 'Settings',
        });
      }

      if (payload.customMessage) {
        await createMerchantCheckoutConfig(payload.customMessage);
        selfServeTrackSuccess({
          selfServeAction: 'Custom Message Configured',
          page: 'Config',
          screen: 'Settings',
        });
      }

      if (payload.emailConfig) {
        await updateFeatures(payload.emailConfig.data);
        await updateEmailConfig(payload.emailConfig.value);
      }

      if (payload.flashCheckout) {
        const isFlashCheckoutEnabled = !!payload.flashCheckout.isFlashCheckoutEnabled;
        const data = {
          features: {
            [flashCheckoutProps.featureAPIKey]: flashCheckoutProps.isFeatureAPIKeyReversed
              ? !isFlashCheckoutEnabled
              : isFlashCheckoutEnabled,
          },
          should_sync: 0,
        };
        trackFlashCheckoutInitiate(isFlashCheckoutEnabled);
        try {
          await updateFeatures(data);
          trackFlashCheckoutSuccess(isFlashCheckoutEnabled);
        } catch (error) {
          const { errors, message } = error as { errors: string[]; message: string };
          trackFlashCheckoutFailure(isFlashCheckoutEnabled, errors?.[0] || '');
          throw new Error(message);
        }
      }
      if (payload.mandatorySummaryPage) {
        const isMandatorySummaryPageEnabled =
          !!payload.mandatorySummaryPage.isMandatorySummaryPageEnabled;
        const data = {
          features: {
            [skipCardMandateSummaryProps.featureAPIKey]: isMandatorySummaryPageEnabled,
          },
          should_sync: 0,
        };
        trackMandateSummaryPageInitiate(isMandatorySummaryPageEnabled);
        try {
          await updateFeatures(data);
          trackMandateSummaryPageSuccess(isMandatorySummaryPageEnabled);
        } catch (error) {
          const { errors, message } = error as { errors: string[]; message: string };
          trackMandateSummaryPageFailure(isMandatorySummaryPageEnabled, errors?.[0] || '');
          throw new Error(message);
        }
      }

      if (payload.merchantCheckoutPaymentConfig) {
        try {
          await createMerchantCheckoutPaymentConfig(payload.merchantCheckoutPaymentConfig);
        } catch (error) {
          discardPaymentConfigChanges();
          throw error;
        } finally {
          handleCurrentExpandedCustomBlockChange('');
          handleSelectedPaymentOptionChange({ name: 'home', isCustomBlock: false });
        }
      }

      toast.show({ content: 'Changes saved successfully', color: 'positive', autoDismiss: true });
    } catch (error) {
      const { errors, message } = error as { errors: string[]; message: string };
      toast.show({ content: errors?.[0] ?? message, color: 'negative', autoDismiss: true });
    } finally {
      setIsSaving(false);
    }
  };

  useEffect(() => {
    setAccountConfigToState();
  }, [accountConfig, setAccountConfigToState]);

  useEffect(() => {
    setAccountLocaleToState();
  }, [accountLocale, setAccountLocaleToState]);

  useEffect(() => {
    setMerchantConfigToState(state.values);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [merchantCheckoutConfig, setMerchantConfigToState]);

  useEffect(() => {
    setMerchantCheckoutStyledConfigToState();
  }, [merchantCheckoutStyledConfig, setMerchantCheckoutStyledConfigToState]);

  useEffect(() => {
    setAPIKeyToState();
  }, [apiKey, setAPIKeyToState]);

  useEffect(() => {
    setMerchantCheckoutPaymentConfigToState();
  }, [merchantCheckoutPaymentConfigs, setMerchantCheckoutPaymentConfigToState]);

  useEffect(() => {
    setMerchantCheckoutPaymentMethodDetailsToState();
  }, [merchantCheckoutPaymentMethodDetails, setMerchantCheckoutPaymentMethodDetailsToState]);

  return (
    <checkoutEditorContext.Provider
      value={{
        values: state.values,
        isValueModified,
        isPaymentConfigChanged,
        isSaving: state.isSaving,
        isLoading: state.isLoading,
        isSavingTitleModalChange: state.isSavingTitleModalChange,
        handleSave,
        handleSuggestionSubmit,
        handleFeedbackSubmit,
        handleLocaleChange,
        handleDiscardAllChanges,
        handleCustomMessageToggle,
        handleCustomMessageTextChange,
        handleCustomMessageTextColorChange,
        handleCustomMessageBackgroundColorChange,
        handlePreviewChange,
        handleFlashCheckoutToggle,
        handleEmailValueChange,
        handleEmailToggle,
        handleMandatorySummaryPageToggle,
        handleShowFinalPriceToggle,
        handleLogoChange,
        handleWordmarkChange,
        handleRectLogoChange,
        handleBrandColorChange,
        handleButtonStyleChange,
        handleFontStyleChange,
        handleSidebarGraphicToggle,
        handleSidebarGraphicValueChange,
        handleTitleStyleChange,
        handleBrandNameChange,
        handleSaveTitleModal,
        handleRtbEnable,
        handleFestivalThemeToggle,
        handleSelectedConfigChange,
        handleConfigNameChange,
        handleSetConfigAsDefault,
        handleOriginalPaymentConfigChange,
        handlePreviewScreenChange,
        handlePaymentConfigScreenChange,
        handleSelectedPaymentOptionChange,
        handleCurrentExpandedCustomBlockChange,
      }}
    >
      {children}
    </checkoutEditorContext.Provider>
  );
};

export default CheckoutEditorProvider;
