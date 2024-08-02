import React, { useCallback, useEffect, useMemo, useReducer } from 'react';
import cloneDeep from 'lodash/cloneDeep';
import isEmpty from 'lodash/isEmpty';

import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { EmailLessCheckoutConfigOptions } from 'merchant/reducers/config';

import {
  ACTIONS,
  INITIAL_STATE,
  CHECKOUT_CONFIG_FIELDS,
  CUSTOM_MESSAGE_BANNER_SCREEN_LABELS,
} from './constants';
import { checkoutConfigContext } from './createContext';
import { createPayloadToSaveConfig, hasValuesChanged } from './helpers';
import { checkoutConfigReducer } from './reducer';
import { AccountConfig, AccountLocale, MerchantCheckoutConfig } from './types';

export type CheckoutConfigProviderProps = {
  children: React.ReactNode;
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale | null;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  uploadLogo: (file: File, fileName: string) => Promise<unknown>;
  removeLogo: (payload: Record<string, string | null>) => Promise<unknown>;
  saveLocale: (payload: unknown) => Promise<unknown>;
  updateFeatures: (payload: unknown, current?: unknown) => Promise<unknown>;
  createMerchantCheckoutConfig: (payload: unknown) => Promise<unknown>;
  updateConfig: (payload: unknown) => Promise<unknown>;
  showNotification: (payload: unknown) => void;
};

const CheckoutConfigProvider = ({
  children,
  accountConfig,
  accountLocale,
  merchantCheckoutConfig,
  uploadLogo,
  removeLogo,
  saveLocale,
  updateConfig,
  updateFeatures,
  createMerchantCheckoutConfig,
  showNotification,
}: CheckoutConfigProviderProps): JSX.Element => {
  const [state, dispatch] = useReducer(checkoutConfigReducer, INITIAL_STATE);

  const isValueModified = useMemo(
    () => hasValuesChanged(state.values, state.config),
    [state.values, state.config],
  );

  const setValue = (
    key: string,
    value: string | Record<string, string | boolean> | File | null,
  ) => {
    dispatch({ type: ACTIONS.SET_VALUES, payload: { [key]: value } });
  };

  const setIsSaving = (isSaving: boolean) => {
    dispatch({ type: ACTIONS.SET_IS_SAVING, payload: isSaving });
  };

  const handleBrandColorChange = (evt?: React.ChangeEvent) => {
    if (evt) {
      const { value } = evt.target as HTMLInputElement;

      setValue(CHECKOUT_CONFIG_FIELDS.COLOR, value);
    }
  };

  const handleLocaleChange = (value: string) => {
    setValue(CHECKOUT_CONFIG_FIELDS.LOCALE, {
      id: state.values[CHECKOUT_CONFIG_FIELDS.LOCALE].id,
      languageCode: value,
    });
  };

  const handleLogoChange = (value: File | null) => {
    setValue(CHECKOUT_CONFIG_FIELDS.LOGO_RAW, value);

    if (value === null) {
      setValue(CHECKOUT_CONFIG_FIELDS.LOGO, '');
    }
  };

  const handleRectLogoChange = (value: File | null) => {
    setValue(CHECKOUT_CONFIG_FIELDS.LOGO_RECT_RAW, value);

    if (value === null) {
      setValue(CHECKOUT_CONFIG_FIELDS.LOGO_RECT, '');
    }
  };

  const handleEmailChange = (value: string) => {
    if (value === EmailLessCheckoutConfigOptions.REQUIRED) {
      dispatch({ type: ACTIONS.SET_EMAIL_REQUIRED_MODAL_OPEN, payload: true });
    } else {
      setValue(CHECKOUT_CONFIG_FIELDS.EMAIL, value);
    }
  };

  const handleCloseEmailRequiredModal = () => {
    dispatch({ type: ACTIONS.SET_EMAIL_REQUIRED_MODAL_OPEN, payload: false });
  };

  const handleConfirmEmailRequired = () => {
    setValue(CHECKOUT_CONFIG_FIELDS.EMAIL, EmailLessCheckoutConfigOptions.REQUIRED);
    handleCloseEmailRequiredModal();
  };

  const handleCustomMessageTextChange = (index: number, value) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = { ...currentConfigs.configs[index], bannerMessageText: value };

    setValue(CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageBackgroundColorChange = (index: number, value: string) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = {
      ...currentConfigs.configs[index],
      bannerBackgroundColor: value,
    };

    setValue(CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageTextColorChange = (index: number, value: string) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.configs[index] = { ...currentConfigs.configs[index], bannerTextColor: value };

    setValue(CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const handleCustomMessageToggle = (isEnabled: boolean) => {
    const currentConfigs = cloneDeep(state.values.customMessage);

    currentConfigs.isEnabled = isEnabled;

    setValue(CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE, currentConfigs);
  };

  const setAccountConfigToState = useCallback(() => {
    if (accountConfig) {
      dispatch({
        type: ACTIONS.SET_VALUES,
        payload: {
          [CHECKOUT_CONFIG_FIELDS.COLOR]: accountConfig.brand_color,
          [CHECKOUT_CONFIG_FIELDS.LOGO]: accountConfig.logo_url,
          [CHECKOUT_CONFIG_FIELDS.LOGO_RAW]: null,
          [CHECKOUT_CONFIG_FIELDS.LOGO_RECT]: accountConfig.rect_logo_url,
          [CHECKOUT_CONFIG_FIELDS.LOGO_RECT_RAW]: null,
          [CHECKOUT_CONFIG_FIELDS.EMAIL]: accountConfig.emailConfig,
          [CHECKOUT_CONFIG_FIELDS.BRAND_NAME]: accountConfig.name,
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
          ? values[CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE].configs.map((item) => ({
              ...item,
              bannerBackgroundColor: values[CHECKOUT_CONFIG_FIELDS.COLOR],
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
          setValue(CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE, {
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
                  isMessageBannerHidden ?? !values[CHECKOUT_CONFIG_FIELDS.CUSTOM_MESSAGE].isEnabled,
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

      if (payload.brandColor) {
        await updateConfig(payload.brandColor);
        selfServeTrackSuccess({
          selfServeAction: 'Brand Logo Uploaded',
          page: 'Config',
          screen: 'Settings',
        });
      }

      if (payload.uploadLogo) {
        await uploadLogo(payload.uploadLogo.file, payload.uploadLogo.fileName);
        selfServeTrackSuccess({
          selfServeAction: 'Brand Logo Uploaded',
          page: 'Config',
          screen: 'Settings',
        });
      } else if (payload.removeLogo) {
        await removeLogo(payload.removeLogo);
        selfServeTrackSuccess({
          selfServeAction: 'Brand Logo Removed',
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
      }

      showNotification({ type: 'success', message: 'Settings saved successfully' });
    } catch (error) {
      const { errors, message } = error as { errors: string[]; message: string };
      showNotification({ type: 'error', message: errors?.[0] ?? message });
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

  return (
    <checkoutConfigContext.Provider
      value={{
        values: state.values,
        isValueModified,
        isSaving: state.isSaving,
        isLoading: state.isLoading,
        isEmailRequiredModalOpen: state.isEmailRequiredModalOpen,
        handleSave,
        handleLogoChange,
        handleEmailChange,
        handleLocaleChange,
        handleRectLogoChange,
        handleBrandColorChange,
        handleDiscardAllChanges,
        handleCustomMessageToggle,
        handleConfirmEmailRequired,
        handleCloseEmailRequiredModal,
        handleCustomMessageTextChange,
        handleCustomMessageTextColorChange,
        handleCustomMessageBackgroundColorChange,
      }}
    >
      {children}
    </checkoutConfigContext.Provider>
  );
};

export default CheckoutConfigProvider;
