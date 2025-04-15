import { LoginScreenOption } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';
import { SSOConfigType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/types';

type SSOAction =
  | { type: 'UPDATE_SSO'; payload: Partial<SSOConfigType> }
  | { type: 'UPDATE_INITIAL_CONFIG'; payload: SSOConfigType['initialConfig'] }
  | { type: 'UPDATE_SSO_STATUS'; payload: boolean }
  | {
      type: 'UPDATE_LOGIN_SCREEN_OPTIONS';
      payload: LoginScreenOption[];
    }
  | { type: 'UPDATE_CUSTOMER_CONSENT'; payload: SSOConfigType['ssoSettings']['customerConsent'] }
  | { type: 'UPDATE_EMAIL_FLOW'; payload: SSOConfigType['ssoSettings']['emailFlow'] }
  | { type: 'UPDATE_SSO_WIDGET_CSS'; payload: Partial<SSOConfigType['ssoWidget']> }
  | {
      type: 'UPDATE_SSO_WIDGET_CAROUSEL';
      payload: Partial<SSOConfigType['ssoWidget']['displayText']>;
    };

export const SSOReducer = (state: SSOConfigType, action: SSOAction): SSOConfigType => {
  switch (action.type) {
    case 'UPDATE_SSO':
      return { ...state, ...action.payload };

    case 'UPDATE_LOGIN_SCREEN_OPTIONS':
      return {
        ...state,
        ssoSettings: {
          ...state.ssoSettings,
          loginScreenOptions: action.payload as LoginScreenOption[],
        },
      };

    case 'UPDATE_SSO_STATUS':
      return {
        ...state,
        isSSOEnabled: action.payload,
      };

    case 'UPDATE_CUSTOMER_CONSENT':
      return {
        ...state,
        ssoSettings: { ...state.ssoSettings, customerConsent: action.payload },
      };

    case 'UPDATE_INITIAL_CONFIG':
      return {
        ...state,
        initialConfig: action.payload,
      };

    case 'UPDATE_EMAIL_FLOW':
      return {
        ...state,
        ssoSettings: { ...state.ssoSettings, emailFlow: action.payload },
      };

    case 'UPDATE_SSO_WIDGET_CSS':
      return {
        ...state,
        ssoWidget: { ...state.ssoWidget, ...action.payload },
      };

    case 'UPDATE_SSO_WIDGET_CAROUSEL':
      return {
        ...state,
        ssoWidget: {
          ...state.ssoWidget,
          displayText: { ...state.ssoWidget.displayText, ...action.payload },
        },
      };

    default:
      return state;
  }
};
