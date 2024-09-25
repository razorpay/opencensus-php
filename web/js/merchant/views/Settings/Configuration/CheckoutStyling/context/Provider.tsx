import React, { useCallback, useEffect, useMemo, useReducer } from 'react';

import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

import { ACTIONS, INITIAL_STATE, CHECKOUT_CONFIG_FIELDS } from './constants';
import { checkoutConfigContext } from './createContext';
import { createPayloadToSaveConfig, hasValuesChanged } from './helpers';
import { checkoutConfigReducer } from './reducer';
import { AccountConfig } from './types';

export type CheckoutConfigProviderProps = {
  children: React.ReactNode;
  accountConfig?: AccountConfig;
  uploadLogo: (file: File, fileName: string) => Promise<unknown>;
  removeLogo: (payload: Record<string, string | null>) => Promise<unknown>;
  updateConfig: (payload: unknown) => Promise<unknown>;
  showNotification: (payload: unknown) => void;
};

const CheckoutConfigProvider = ({
  children,
  accountConfig,
  uploadLogo,
  removeLogo,
  updateConfig,
  showNotification,
}: CheckoutConfigProviderProps): JSX.Element => {
  const [state, dispatch] = useReducer(checkoutConfigReducer, INITIAL_STATE);

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

  const handleBrandColorChange = (evt?: React.ChangeEvent, defaultValue?: string) => {
    if (defaultValue) {
      setValue(CHECKOUT_CONFIG_FIELDS.COLOR, defaultValue);
      return;
    }

    if (evt) {
      const { value } = evt.target as HTMLInputElement;
      setValue(CHECKOUT_CONFIG_FIELDS.COLOR, value);
    }
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

  const handleDiscardAllChanges = () => {
    setAccountConfigToState();
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

      showNotification({ type: 'success', message: 'Settings saved successfully' });
    } catch (error) {
      const { errors, message } = error as { errors: string[]; message: string };
      showNotification({ type: 'error', message: errors?.[0] ?? message });
    } finally {
      setIsSaving(false);
    }
  };

  const handlePreviewChange = (value: boolean) => {
    setValue(CHECKOUT_CONFIG_FIELDS.IS_DESKTOP_PREVIEW, value);
  };

  useEffect(() => {
    setAccountConfigToState();
  }, [accountConfig, setAccountConfigToState]);

  return (
    <checkoutConfigContext.Provider
      value={{
        values: state.values,
        isValueModified,
        isSaving: state.isSaving,
        isLoading: state.isLoading,
        handleSave,
        handleLogoChange,
        handleRectLogoChange,
        handleBrandColorChange,
        handleDiscardAllChanges,
        handlePreviewChange,
      }}
    >
      {children}
    </checkoutConfigContext.Provider>
  );
};

export default CheckoutConfigProvider;
