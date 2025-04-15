import { SSOConfigs, SSOConfigsPayloadType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';
import { SSOConfigType } from './types';

export function updateSSOStore(response: SSOConfigs, updateFunctions) {
  if (!response) return;

  const initialSSOConfig = JSON.parse(JSON.stringify(response));

  const {
    updateIsSSOEnabled,
    updateLoginScreenOptions,
    updateCustomerConsent,
    updateEmailFlow,
    updateSSOWidgetCss,
    updateSSOWidgetCarousel,
    updateInitialConfig,
  } = updateFunctions;

  updateInitialConfig(initialSSOConfig);

  updateIsSSOEnabled(response.sso_enabled);

  if (response.sso_settings) {
    const { login_screen_options, customer_consent, email_flow } = response.sso_settings;

    if (login_screen_options) {
      updateLoginScreenOptions(login_screen_options);
    }

    updateCustomerConsent(customer_consent);
    updateEmailFlow(email_flow);
  }

  if (response.sso_widget) {
    const { background_color, button_color, font_family, display_text } = response.sso_widget;

    updateSSOWidgetCss({
      backgroundColor: background_color,
      buttonColor: button_color,
      fontFamily: font_family,
    });

    if (display_text) {
      updateSSOWidgetCarousel({
        heading: display_text.heading,
        carousel: display_text.carousel || [],
      });
    }
  }
}

export function getSSOConfigPayload(store: SSOConfigType, merchantId: string): SSOConfigsPayloadType {
  return {
    merchant_id: merchantId,
    configs: {
      sso_config: {
        sso_enabled: store.isSSOEnabled,
        sso_settings: {
          login_screen_options: store.ssoSettings.loginScreenOptions,
          customer_consent: store.ssoSettings.customerConsent,
          email_flow: store.ssoSettings.emailFlow,
        },
        sso_widget: {
          background_color: store.ssoWidget.backgroundColor,
          button_color: store.ssoWidget.buttonColor,
          font_family: store.ssoWidget.fontFamily,
          display_text: {
            heading: store.ssoWidget.displayText.heading,
            carousel: store.ssoWidget.displayText.carousel,
          },
        },
      },
    },
  };
}
