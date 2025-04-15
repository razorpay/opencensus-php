import React, { useReducer, ReactNode, useEffect } from 'react';

import { SSOReducer } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/SSOReducer';
import {
  SSOConfigType,
  SSOWidget,
  SSOWidgetDisplayText,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/types';
import { SSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/createContext';
import { DEFAULT_SSO_CONFIG } from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';
import {
  CustomerConsentType,
  EmailFlowType,
  LoginScreenOption,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';

export const SSOProvider = ({ children }: { children: ReactNode }) => {
  const [state, dispatch] = useReducer(SSOReducer, DEFAULT_SSO_CONFIG);
  const [apiKey, setApiKey] = React.useState<string>('');
  const [mode, setMode] = React.useState<string>('');
  const [dashboardView, setDashboardView] = React.useState<string>('');
  const [merchantId, setMerchantId] = React.useState<string>('');

  const updateIsSSOEnabled = (newData: boolean) => {
    dispatch({ type: 'UPDATE_SSO_STATUS', payload: newData });
  };

  const updateInitialConfig = (newData: SSOConfigType['initialConfig']) => {
    dispatch({ type: 'UPDATE_INITIAL_CONFIG', payload: newData });
  };

  const updateLoginScreenOptions = (newData: LoginScreenOption[]) => {
    dispatch({ type: 'UPDATE_LOGIN_SCREEN_OPTIONS', payload: newData });
  };

  const updateCustomerConsent = (newData: CustomerConsentType) => {
    dispatch({ type: 'UPDATE_CUSTOMER_CONSENT', payload: newData });
  };

  const updateEmailFlow = (newData: EmailFlowType) => {
    dispatch({ type: 'UPDATE_EMAIL_FLOW', payload: newData });
  };

  const updateSSOWidgetCss = (newData: Partial<SSOWidget>) => {
    dispatch({ type: 'UPDATE_SSO_WIDGET_CSS', payload: newData });
  };

  const updateSSOWidgetCarousel = (newData: Partial<SSOWidgetDisplayText>) => {
    dispatch({ type: 'UPDATE_SSO_WIDGET_CAROUSEL', payload: newData });
  };

  return (
    <SSOContext.Provider
      value={{
        ...state,
        apiKey,
        mode,
        merchantId,
        dashboardView,
        setDashboardView,
        setMerchantId,
        setApiKey,
        setMode,
        updateIsSSOEnabled,
        updateLoginScreenOptions,
        updateCustomerConsent,
        updateEmailFlow,
        updateSSOWidgetCss,
        updateSSOWidgetCarousel,
        updateInitialConfig,
      }}
    >
      {children}
    </SSOContext.Provider>
  );
};

export default SSOProvider;
