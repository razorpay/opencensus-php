function getOptionsFromState(state) {
  const options: {
    key: string;
    amount: number;
    name?: string;
    'prefill.contact': string;
    'config.display.display.language'?: string;
    image?: string;
    'theme.color'?: string;
  } = {
    key: 'rzp_live_ILgsfZCZoFIKMb',
    amount: 5000.0,
    'prefill.contact': '8888888888',
  };

  if (state.locale) {
    options['config.display.display.language'] = state.locale;
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
