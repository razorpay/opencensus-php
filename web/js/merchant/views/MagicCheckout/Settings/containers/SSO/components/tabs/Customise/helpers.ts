import { DEFAULT_SSO_CONFIG } from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';
import {
  SSOConfigType,
  SSOWidget,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/types';
import {
  SSOOption,
  UpdatePayload,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';
import debounce from 'common/utils/debounce';

// Updates the SSO widget display text based on the type of update
export function getUpdatedSSODisplayText(
  parentConfig: SSOConfigType,
  update: UpdatePayload,
): SSOWidget['displayText'] {
  const updatedConfig: SSOConfigType = JSON.parse(JSON.stringify(parentConfig));

  // get the index of the update type if present
  const index = parseInt(update.action.split('_').pop() || '', 10);

  // return default if index is out of bounds or does not exist
  if (index && (index < 0 || index > 2)) {
    return DEFAULT_SSO_CONFIG.ssoWidget.displayText;
  }

  // Update based on type
  if (update.action.startsWith('sso_edit_carousel_icon_')) {
    updatedConfig.ssoWidget.displayText.carousel[index].icon = update.text;
  } else if (update.action.startsWith('sso_edit_carousel_text_')) {
    updatedConfig.ssoWidget.displayText.carousel[index].text = update.text;
  } else if (update.action === 'sso_edit_header_text') {
    updatedConfig.ssoWidget.displayText.heading = update.text;
  }

  return updatedConfig.ssoWidget.displayText;
}

// cretaes intial options for the SSO widget
export function createInitialOptions(apiKey = '', isEditable = false, isMobileView = false) {
  return {
    platform: navigator.userAgent,
    key: apiKey,
    is_editable: isEditable,
    is_mobile_view: isMobileView,
    config: {
      coupon: {
        code: 'TRY NEW',
        desc: 'Get 10% off on your first order',
        reward_subtext: 'You just won a special coupon for your login 🎉',
      },
      sso_settings: {
        customer_consent: 'separate_selector',
        email_flow: 'separate_selector',
        login_screen_options: [{ delay: 2, type: 'landing_page' }],
      },
      sso_widget: {
        background_color: '',
        button_color: '',
        font_family: '',
        display_text: {
          heading: 'Hello again, snoozer! \n Exciting offer waiting for you',
          carousel: [
            {
              icon: '🎉',
              text: 'Enjoy hassle free shopping with the best offers applied for you',
            },
            {
              icon: '🤩',
              text: 'Explore unbeatable prices and unmatchable value',
            },
            {
              icon: '🛡️',
              text: '100% secure & spam free, we will not annoy you, pinky promise!',
            },
          ],
        },
      },
    },
  };
}

// Creates a new SSO option based on the initial config and new options
export function createNewSSOOption(
  initial: SSOOption,
  newOptions: Partial<SSOOption['config']>,
  isDesktopModeEnabled: boolean,
  isEditable: boolean,
  apiKey: string,
) {
  return {
    ...initial,
    key: apiKey ? apiKey : initial.key,
    is_editable: isEditable,
    is_mobile_view: !isDesktopModeEnabled,
    config: {
      ...initial.config,
      sso_settings: {
        ...initial.config.sso_settings,
        ...newOptions?.sso_settings,
      },
      sso_widget: {
        ...initial.config.sso_widget,
        ...newOptions?.sso_widget,
      },
    },
  };
}

// Creates a new SSO option based on the initial config and new options
export function createSSOMessageListener(targetId: string, onMessage?: (data: any) => void) {
  const messageHandler = (event: MessageEvent) => {
    const eventData = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
    if (typeof eventData === 'object' && eventData.source === targetId && onMessage) {
      onMessage(eventData);
    }
  };

  window.addEventListener('message', messageHandler);

  return {
    cleanup: () => {
      window.removeEventListener('message', messageHandler);
    },
  };
}

/**
 * Initializes the SSO widget
 * returns a promise with the update function and a removeListener function
 */
export function initSSO(parent: HTMLIFrameElement): Promise<{
  update: (value: Record<string, unknown>) => void;
  removeListener: () => void;
}> {
  const iWindow = parent.contentWindow;

  const post = (data = {}) => {
    iWindow?.postMessage(
      {
        id: '111111111111',
        ...data,
      },
      '*',
    );
  };

  let options: Record<string, unknown> = createInitialOptions();

  const debouncedUpdate = debounce((updatedOptions: Record<string, unknown>) => {
    options = updatedOptions;
    post({
      action: 'open',
      options: options,
    });
  }, 400);

  return new Promise((resolve) => {
    function handleMessage(e: MessageEvent) {
      if (e.source === iWindow) {
        try {
          const data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
          switch (data.action) {
            case 'sso_script_loaded':
              post({
                action: 'open',
                options: options,
              });
              resolve({ update: debouncedUpdate, removeListener });
              break;

            default:
              break;
          }
        } catch (e) {
          resolve({ update: debouncedUpdate, removeListener });
        }
      }
    }

    addEventListener('message', handleMessage);

    function removeListener() {
      removeEventListener('message', handleMessage);
    }
  });
}
