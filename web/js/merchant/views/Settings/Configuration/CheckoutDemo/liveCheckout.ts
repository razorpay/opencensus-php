function getOptionsFromState(state) {
  const options: {
    key: string;
    amount: number;
    name?: string;
    'prefill.contact': string;
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
  } = {
    key: 'rzp_live_ILgsfZCZoFIKMb',
    amount: 5000_00,
    'prefill.contact': '8888888888',
  };

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

  if (state.titleStyle) {
    options['theme.title_style'] = state.titleStyle;
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
  return {
    options,
  };
}

export function initCheckout(
  parent: HTMLIFrameElement,
  initialState: Record<string, unknown>,
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
    const { options } = getOptionsFromState(state);
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
                options: getOptionsFromState(state).options,
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
