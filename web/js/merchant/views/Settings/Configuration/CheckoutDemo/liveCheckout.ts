import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { isExperimentEnabled } from 'common/splitz/utils';
import { AVAILABLE_TITLE_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

function getOptionsFromState(state, abExperiments: Record<string, any> = {}) {
  const options: {
    key: string;
    keyless_header?: string;
    amount: number;
    name?: string;
    'prefill.contact': string;
    'prefill.email': string;
    'config.display.display.language'?: string;
    image?: string;
    'theme.color'?: string;
    'theme.border_radius'?: string;
    'theme.sidebar_graphic'?: {
      enabled: boolean;
      svg: string;
    };
    'theme.title_style'?: string;
    'theme.font_family'?: {
      heading: string;
    };
    'theme.festivities_enabled'?: boolean;
    notification_banner?: {
      banner_config?: {
        [key in 'contact' | 'address' | 'payment' | 'shipping_information']?: {
          text?: string;
          image?: string;
          text_color?: string;
          background_color?: string;
          hidden?: boolean;
        };
      };
      hide_message_banner?: boolean;
    };
    locale?: string;
    hide_rtb?: boolean;
    wordmark?: string;
    'prefill.method'?: string;
    'prefill.block'?: {
      name?: string;
    };
    config?: MerchantCheckoutPaymentConfig;
    __internal?: {
      reinit: boolean;
    };
  } = {
    key: 'rzp_live_ILgsfZCZoFIKMb',
    amount: 5000_00,
    'prefill.contact': '8888888888',
    'prefill.email': 'abc@example.com',
  };

  if (state.apiKey) {
    options.key = state.apiKey;
    // force set keyless_header to be empty
    options.keyless_header = '';
  }

  if (state.keylessHeader) {
    options.keyless_header = state.keylessHeader;
    // force set apiKey to be empty
    options.key = '';
  }

  if (state.locale) {
    options.locale = state.locale.languageCode;
  }

  if (state.logo) {
    options.image = state.logo;
  }

  if (state.color) {
    options['theme.color'] = state.color;
  }

  if (state.brandName) {
    options.name = state.brandName;
  }

  if (state.borderRadius) {
    options['theme.border_radius'] = state.borderRadius;
  }

  if (state.sidebarGraphic) {
    options['theme.sidebar_graphic'] = state.sidebarGraphic;
  }

  const shouldShowFestivalTheme = isExperimentEnabled(abExperiments?.checkout_festival_theme);

  if (shouldShowFestivalTheme) {
    const themeEnabled = state.festivalTheme === undefined ? true : state.festivalTheme;
    options['theme.festivities_enabled'] = themeEnabled || false;
  }

  if (state.titleStyle) {
    options['theme.title_style'] = state.titleStyle;
    if (state.titleStyle === AVAILABLE_TITLE_STYLE.WORDMARK) {
      options.wordmark = state.wordmark;
    }
  }

  if (state.fontFamily) {
    options['theme.font_family'] = {
      heading: state.fontFamily,
    };
  }

  if (state.customMessage) {
    const banner_config = state.customMessage.configs.reduce((acc, config) => {
      return {
        ...acc,
        [config.name]: {
          text: config.bannerMessageText,
          background_color: config.bannerBackgroundColor,
          hidden: config.hidden,
          text_color: config.bannerTextColor,
        },
      };
    }, {});
    options.notification_banner = {
      banner_config,
      hide_message_banner: !state.customMessage.isEnabled,
    };
  }
  options.hide_rtb = state.rtb_enabled === undefined ? true : !state.rtb_enabled;

  if (state.selectedPaymentOption) {
    const paymentOption = state.selectedPaymentOption;
    if (paymentOption.isCustomBlock) {
      options['prefill.block'] = {
        name: paymentOption.name,
      };
      options['prefill.method'] = 'home';
    } else {
      options['prefill.method'] = paymentOption.name;
      options['prefill.block'] = {
        name: 'home',
      };
    }
    options.__internal = {
      reinit: true,
    };
  }

  if (state.selectedPaymentConfig) {
    options.config = state.selectedPaymentConfig.checkout_config;
    options.__internal = {
      reinit: true,
    };
  }
  return {
    options,
  };
}

export function initCheckout(
  parent: HTMLIFrameElement,
  initialState: Record<string, unknown>,
  abExperiments: Record<string, unknown> = {},
): Promise<{
  update: (value: Record<string, unknown>) => void;
}> {
  const iWindow = parent.contentWindow;

  const post = (event, data = {}) => {
    iWindow?.postMessage(
      {
        id: '00000000000000',
        event,
        ...data,
      },
      '*',
    );
  };

  let state = initialState;

  const update = (updatedState: Record<string, unknown>) => {
    state = updatedState;
    const { options } = getOptionsFromState(state, abExperiments);
    post('update_options', { data: options });
  };

  return new Promise((resolve, reject) => {
    addEventListener('message', (e) => {
      if (e.source === iWindow) {
        try {
          const data = JSON.parse(e.data);
          switch (data.event) {
            case 'load':
              post('open', {
                options: getOptionsFromState(state, abExperiments).options,
              });
              break;

            case 'render':
              resolve({ update });
              break;

            case 'fault':
              reject();
              break;
            default:
              break;
          }
        } catch (e) {
          reject(e);
        }
      }
    });
  });
}
