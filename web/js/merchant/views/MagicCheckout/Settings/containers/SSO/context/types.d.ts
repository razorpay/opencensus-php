import {
  CustomerConsentType,
  LoginScreenOption,
  EmailFlowType,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/types.d.ts';

export type SSOSettings = {
  loginScreenOptions: LoginScreenOption[];
  customerConsent: CustomerConsentType;
  emailFlow: EmailFlowType;
};

export type SSOWidgetDisplayText = {
  heading: string;
  carousel: { icon: string; text: string }[];
};

export type SSOWidget = {
  backgroundColor: string;
  buttonColor: string;
  fontFamily: string;
  displayText: SSOWidgetDisplayText;
};

export type SSOConfigType = {
  isSSOEnabled: boolean;
  ssoSettings: SSOSettings;
  ssoWidget: SSOWidget;
  initialConfig?: isSSOEnabled & SSOSettings & SSOWidget;
};

export type SSOContextType = SSOConfigType & {
  apiKey: string;
  mode: string;
  merchantId: string;
  dashboardView: string;
  setDashboardView: (newDashboardView: string) => void;
  setApiKey: (newApiKey: string) => void;
  setMode: (newMode: string) => void;
  setMerchantId: (newMerchantId: string) => void;
  updateLoginScreenOptions: (newData: LoginScreenOption[]) => void;
  updateCustomerConsent: (newData: CustomerConsentType) => void;
  updateEmailFlow: (newData: EmailFlowType) => void;
  updateSSOWidgetCss: (newData: Partial<SSOWidget>) => void;
  updateSSOWidgetCarousel: (newData: Partial<SSOWidgetDisplayText>) => void;
  updateIsSSOEnabled: (newData: boolean) => void;
  updateInitialConfig: (newData: SSOConfigType['initialConfig']) => void;
};
