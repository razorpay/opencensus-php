import React, { useCallback, useEffect, useMemo, useReducer } from 'react';
import cloneDeep from 'lodash/cloneDeep';
import isEmpty from 'lodash/isEmpty';
import { AccountConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context/types';

import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

import {
  ACTIONS,
  INITIAL_STATE,
  CHECKOUT_FEATURE_FIELDS,
  CUSTOM_MESSAGE_BANNER_SCREEN_LABELS,
} from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/constants';
import { checkoutFeatureContext } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/createContext';
import {
  createPayloadToSaveConfig,
  hasValuesChanged,
} from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/helpers';
import { checkoutFeatureReducer } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/reducer';
import {
  AccountLocale,
  ConfigFeatures,
  MerchantCheckoutConfig,
} from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/types';
import {
  flashCheckoutProps,
  skipCardMandateSummaryProps,
} from 'merchant/views/Settings/Configuration/settings-config-constants';
import {
  trackFlashCheckoutFailure,
  trackFlashCheckoutInitiate,
  trackFlashCheckoutSuccess,
} from 'merchant/views/Settings/Configuration/CheckoutFeatures/utils/flashCheckout';
import {
  trackMandateSummaryPageFailure,
  trackMandateSummaryPageInitiate,
  trackMandateSummaryPageSuccess,
} from 'merchant/views/Settings/Configuration/CheckoutFeatures/utils/mandateSummaryPage';

export type CheckoutConfigProviderProps = {
  children: React.ReactNode;
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale | null;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  saveLocale: (payload: unknown) => Promise<unknown>;
  updateFeatures: (payload: unknown, current?: unknown) => Promise<unknown>;
  createMerchantCheckoutConfig: (payload: unknown) => Promise<unknown>;
  showNotification: (payload: unknown) => void;
};

const CheckoutConfigProvider = ({
  children,
  accountConfig,
  accountLocale,
  merchantCheckoutConfig,
  saveLocale,
  updateFeatures,
  createMerchantCheckoutConfig,
  showNotification,
}: CheckoutConfigProviderProps): JSX.Element => {
  const [state, dispatch] = useReducer(checkoutFeatureReducer, INITIAL_STATE);

  const isValueModified = useMemo(
    () => hasValuesChanged(state.values, state.config),
    [state.values, state.config],
  );

  const setValue = (
    key: string,
    value: string | Record<string, string | boolean> | File | null | boolean,
  ) => {
    dispatch({ type: ACTIONS.SET_VALUES, payload: { [key]: value } });
  };

  const setIsSaving = (isSaving: boolean) => {
    dispatch({ type: ACTIONS.SET_IS_SAVING, payload: isSaving });
  };

  const handleLocaleChange = (value: string) => {
    setValue(CHECKOUT_FEATURE_FIELDS.LOCALE, {
      id: state.values[CHECKOUT_FEATURE_FIELDS.LOCALE].id,
      languageCode: value,
    });
  };

  const handleCustomMessageTextChange = (index: number, value) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = { ...currentConfigs.configs[index], bannerMessageText: value };

    setValue(CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageBackgroundColorChange = (index: number, value: string) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = {
      ...currentConfigs.configs[index],
      bannerBackgroundColor: value,
    };

    setValue(CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageTextColorChange = (index: number, value: string) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = { ...currentConfigs.configs[index], bannerTextColor: value };

    setValue(CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageToggle = (isEnabled: boolean) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.isEnabled = isEnabled;

    setValue(CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleFlashCheckoutToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT, isEnabled);
  };

  const handleMandatorySummaryPageToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_FEATURE_FIELDS.MANDATORY_SUMMARY_PAGE, isEnabled);
  };

  const handleShowFinalPriceToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_FEATURE_FIELDS.SHOW_FINAL_PRICE, isEnabled);
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
          [CHECKOUT_FEATURE_FIELDS.EMAIL]: accountConfig.emailConfig,
          [CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]: !!getFlashCheckoutValue(accountConfig.features),
          [CHECKOUT_FEATURE_FIELDS.MANDATORY_SUMMARY_PAGE]: !!!!getMandatorySummaryPageValue(
            accountConfig?.features,
          ),
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
          ? values[CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE].configs
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
          setValue(CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE, {
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
                  isMessageBannerHidden ??
                  !values[CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE].isEnabled,
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
    setMerchantConfigToState(state.values);
  };

  const handleSave = async () => {
    const payload = createPayloadToSaveConfig(state.values, state.config);
    try {
      setIsSaving(true);

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
      showNotification({ type: 'success', message: 'Settings saved successfully' });
    } catch (error) {
      const { errors, message } = error as { errors: string[]; message: string };
      showNotification({ type: 'error', message: errors?.[0] ?? message });
    } finally {
      setIsSaving(false);
    }
  };

  const handlePreviewChange = (value: boolean) => {
    setValue(CHECKOUT_FEATURE_FIELDS.IS_DESKTOP_PREVIEW, value);
  };

  const handleEmailToggle = (isEnabled: boolean) => {
    setValue(CHECKOUT_FEATURE_FIELDS.EMAIL, {
      ...state.values[CHECKOUT_FEATURE_FIELDS.EMAIL],
      isEnabled,
    });
  };

  const handleEmailValueChange = (value: string) => {
    setValue(CHECKOUT_FEATURE_FIELDS.EMAIL, {
      ...state.values[CHECKOUT_FEATURE_FIELDS.EMAIL],
      value,
    });
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

  return (
    <checkoutFeatureContext.Provider
      value={{
        values: state.values,
        isValueModified,
        isSaving: state.isSaving,
        isLoading: state.isLoading,
        handleSave,
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
      }}
    >
      {children}
    </checkoutFeatureContext.Provider>
  );
};

export default CheckoutConfigProvider;
