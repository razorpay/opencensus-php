import React, { useCallback, useEffect, useMemo, useReducer } from 'react';
import cloneDeep from 'lodash/cloneDeep';
import isEmpty from 'lodash/isEmpty';
import { AccountConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context/types';

import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';
import {
  trackFlashCheckoutFailure,
  trackFlashCheckoutInitiate,
  trackFlashCheckoutSuccess,
} from 'merchant/views/Settings/Configuration/CheckoutFeatures/utils/flashCheckout';
import { flashCheckoutProps } from 'merchant/views/Settings/Configuration/settings-config-constants';

import {
  ACTIONS,
  INITIAL_STATE,
  CHECKOUT_FEATURE_FIELDS,
  CUSTOM_MESSAGE_BANNER_SCREEN_LABELS,
} from './constants';
import { checkoutFeatureContext } from './createContext';
import { createPayloadToSaveConfig, hasValuesChanged } from './helpers';
import { checkoutFeatureReducer } from './reducer';
import { AccountLocale, ConfigFeatures, MerchantCheckoutConfig } from './types';

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

  const handleEmailChange = (value: string) => {
    if (value === EmailLessCheckoutConfigOptions.REQUIRED) {
      dispatch({ type: ACTIONS.SET_EMAIL_REQUIRED_MODAL_OPEN, payload: true });
    } else {
      setValue(CHECKOUT_FEATURE_FIELDS.EMAIL, value);
    }
  };

  const handleCloseEmailRequiredModal = () => {
    dispatch({ type: ACTIONS.SET_EMAIL_REQUIRED_MODAL_OPEN, payload: false });
  };

  const handleConfirmEmailRequired = () => {
    setValue(CHECKOUT_FEATURE_FIELDS.EMAIL, EmailLessCheckoutConfigOptions.REQUIRED);
    handleCloseEmailRequiredModal();
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

  const setAccountConfigToState = useCallback(() => {
    const getFeatureFlag = (features: ConfigFeatures | undefined, featureAPIKey: string) => {
      const featureObj = features?.find((feature) => feature.feature === featureAPIKey);
      return featureObj?.value;
    };
    const getFlashCheckoutValue = (features: AccountConfig['features']) => {
      const isFeatureFlagSet = getFeatureFlag(features, flashCheckoutProps.featureAPIKey);
      return flashCheckoutProps.isFeatureAPIKeyReversed ? !isFeatureFlagSet : isFeatureFlagSet;
    };
    if (accountConfig) {
      dispatch({
        type: ACTIONS.SET_VALUES,
        payload: {
          [CHECKOUT_FEATURE_FIELDS.EMAIL]: accountConfig.emailConfig,
          [CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]: !!getFlashCheckoutValue(accountConfig.features),
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
        isEmailRequiredModalOpen: state.isEmailRequiredModalOpen,
        handleSave,
        handleEmailChange,
        handleLocaleChange,
        handleDiscardAllChanges,
        handleCustomMessageToggle,
        handleConfirmEmailRequired,
        handleCloseEmailRequiredModal,
        handleCustomMessageTextChange,
        handleCustomMessageTextColorChange,
        handleCustomMessageBackgroundColorChange,
        handlePreviewChange,
        handleFlashCheckoutToggle,
      }}
    >
      {children}
    </checkoutFeatureContext.Provider>
  );
};

export default CheckoutConfigProvider;
