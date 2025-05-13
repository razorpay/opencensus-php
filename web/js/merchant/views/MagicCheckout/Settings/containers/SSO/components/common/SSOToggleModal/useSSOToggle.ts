import { useCallback, useState } from 'react';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context';
import { getSSOConfigPayload } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/helpers';
import { updateThemeResponseType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';
import { updateMerchantTheme } from 'merchant/views/MagicCheckout/Settings/containers/SSO/api';
import { ModalState } from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/common/SSOToggleModal/types';

export const useSSOToggle = () => {
  const [currentState, setCurrentState] = useState<ModalState>('loading');
  const [activationLink, setActivationLink] = useState<string>('');
  const [errorMsg, setErrorMsg] = useState<string>('');

  const {
    isSSOEnabled,
    updateIsSSOEnabled,
    ssoSettings,
    ssoWidget,
    merchantId,
    dashboardView,
    mode,
  } = useSSOContext();

  const handleToggle = useCallback(async (): Promise<ModalState> => {
    try {
      const payload = getSSOConfigPayload(
        { isSSOEnabled: !isSSOEnabled, ssoSettings, ssoWidget },
        merchantId,
      );

      const res: updateThemeResponseType = await updateMerchantTheme(payload, dashboardView, mode);

      if (res.success) {
        updateIsSSOEnabled(!isSSOEnabled);
        setActivationLink(res.data.activation_link);
        return 'success';
      }

      throw new Error('SSO configs update failed');
    } catch (error) {
      const errorStatus = (error as { status_code?: number })?.status_code;

      if (errorStatus === 409) {
        setErrorMsg('Your shopify plan is not compatible with Razorpay Login');
      } else {
        setErrorMsg('Please try again');
      }

      return 'error';
    }
  }, [isSSOEnabled, ssoSettings, ssoWidget, merchantId, dashboardView, mode, updateIsSSOEnabled]);

  return {
    currentState,
    setCurrentState,
    activationLink,
    errorMsg,
    handleToggle,
  };
};
