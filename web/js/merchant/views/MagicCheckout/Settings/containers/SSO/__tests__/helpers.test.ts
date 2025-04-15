import {
  getUpdatedSSODisplayText,
  createInitialOptions,
  createNewSSOOption,
  createSSOMessageListener,
  initSSO,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/tabs/Customise/helpers';
import { DEFAULT_SSO_CONFIG } from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';
import { SSOConfigType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/types';
import { UpdatePayload } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';

describe('SSO Helpers', () => {
  describe('getUpdatedSSODisplayText', () => {
    const mockParentConfig: SSOConfigType = {
      isSSOEnabled: true,
      ssoSettings: {
        customerConsent: 'separate_selector',
        emailFlow: 'collect_missing_email_flow',
        loginScreenOptions: [{ delay: 2, type: 'landing_page' }],
      },
      ssoWidget: {
        backgroundColor: '',
        buttonColor: '',
        fontFamily: '',
        displayText: {
          heading: 'Original Heading',
          carousel: [
            { icon: '🎉', text: 'Original Text 1' },
            { icon: '🤩', text: 'Original Text 2' },
            { icon: '🛡️', text: 'Original Text 3' },
          ],
        },
      },
    };

    it('should update carousel icon', () => {
      const update: UpdatePayload = {
        action: 'sso_edit_carousel_icon_0',
        text: '🎈',
      };
      const result = getUpdatedSSODisplayText(mockParentConfig, update);
      expect(result.carousel[0].icon).toBe('🎈');
    });

    it('should update carousel text', () => {
      const update: UpdatePayload = {
        action: 'sso_edit_carousel_text_1',
        text: 'New Text',
      };
      const result = getUpdatedSSODisplayText(mockParentConfig, update);
      expect(result.carousel[1].text).toBe('New Text');
    });

    it('should update header text', () => {
      const update: UpdatePayload = {
        action: 'sso_edit_header_text',
        text: 'New Heading',
      };
      const result = getUpdatedSSODisplayText(mockParentConfig, update);
      expect(result.heading).toBe('New Heading');
    });

    it('should return default config for invalid index', () => {
      const update: UpdatePayload = {
        action: 'sso_edit_carousel_icon_5',
        text: '🎈',
      };
      const result = getUpdatedSSODisplayText(mockParentConfig, update);
      expect(result).toEqual(DEFAULT_SSO_CONFIG.ssoWidget.displayText);
    });
  });

  describe('createInitialOptions', () => {
    it('should create initial options with default values', () => {
      const result = createInitialOptions();
      expect(result).toHaveProperty('platform');
      expect(result).toHaveProperty('key', '');
      expect(result).toHaveProperty('is_editable', false);
      expect(result).toHaveProperty('is_mobile_view', false);
      expect(result.config).toHaveProperty('coupon');
      expect(result.config).toHaveProperty('sso_settings');
      expect(result.config).toHaveProperty('sso_widget');
    });

    it('should create initial options with provided values', () => {
      const apiKey = 'test-key';
      const result = createInitialOptions(apiKey, true, true);
      expect(result.key).toBe(apiKey);
      expect(result.is_editable).toBe(true);
      expect(result.is_mobile_view).toBe(true);
    });
  });

  describe('createNewSSOOption', () => {
    const initialOption = {
      key: 'initial-key',
      is_editable: false,
      is_mobile_view: false,
      config: {
        sso_settings: { customer_consent: 'separate_selector' },
        sso_widget: { background_color: 'white' },
      },
    };

    it('should create new SSO option with updated values', () => {
      const newOptions = {
        sso_settings: { customer_consent: 'new_selector' },
        sso_widget: { background_color: 'black' },
      };
      const result = createNewSSOOption(
        initialOption,
        newOptions,
        true,
        true,
        'new-key'
      );
      expect(result.key).toBe('new-key');
      expect(result.is_editable).toBe(true);
      expect(result.is_mobile_view).toBe(false);
      expect(result.config.sso_settings['customer_consent']).toBe('new_selector');
      expect(result.config.sso_widget['background_color']).toBe('black');
    });
  });

  describe('createSSOMessageListener', () => {
    it('should create message listener and cleanup function', () => {
      const mockOnMessage = jest.fn();
      const targetId = 'test-id';
      const { cleanup } = createSSOMessageListener(targetId, mockOnMessage);

      // Simulate message event
      const mockEvent = new MessageEvent('message', {
        data: { source: targetId, message: 'test' },
      });
      window.dispatchEvent(mockEvent);

      expect(mockOnMessage).toHaveBeenCalledWith({ source: targetId, message: 'test' });

      // Cleanup
      cleanup();
      window.dispatchEvent(mockEvent);
      expect(mockOnMessage).toHaveBeenCalledTimes(1);
    });
  });
}); 