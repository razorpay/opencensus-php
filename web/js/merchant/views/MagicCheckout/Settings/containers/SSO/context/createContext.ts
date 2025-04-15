import { createContext, useContext } from 'react';

import { noop } from 'common/utils/rzp-utils';
import { SSOContextType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/types';
import { DEFAULT_SSO_CONFIG } from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';

export const SSOContext = createContext<SSOContextType>({
  ...DEFAULT_SSO_CONFIG,
  apiKey: '',
  mode: '',
  merchantId: '',
  dashboardView: '',
  setMerchantId: noop,
  setApiKey: noop,
  setMode: noop,
  updateIsSSOEnabled: () => noop,
  updateLoginScreenOptions: noop,
  updateCustomerConsent: noop,
  updateEmailFlow: noop,
  updateSSOWidgetCss: noop,
  updateSSOWidgetCarousel: noop,
  updateInitialConfig: noop,
  setDashboardView: noop,
} as SSOContextType);

export const useSSOContext = (): SSOContextType => {
  const context = useContext(SSOContext);
  return context;
};
