import {
  getLatestKey,
  getAvailablePlatform,
  getAvailablePlugin,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/utils';
import store, { getUser } from 'merchant/store';
import { Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import {
  updateUser,
  populateKeys,
  populateSupportedPlugins,
  getKeys,
  KEY,
  PLATFORM_LINKS,
} from './mocks/fixtures';

const getStateSpy = jest.spyOn(store, 'getState');

describe('getAvailablePlatform', () => {
  beforeEach(() => {
    getStateSpy.mockReset();
  });

  test('should return Website if no platform is present', () => {
    updateUser(getStateSpy, { business_website: '', appstore_url: '', playstore_url: '' });
    const user = getUser();
    const platform = getAvailablePlatform(user);
    expect(platform).toBe(Platform.WEBSITE);
  });

  test('should prioritize Website if present over Android/iOS ', () => {
    updateUser(getStateSpy, PLATFORM_LINKS.SUCCESS);
    const user = getUser();
    const platform = getAvailablePlatform(user);
    expect(platform).toBe(Platform.WEBSITE);
  });

  test('should prioritize Android if present over iOS', () => {
    updateUser(getStateSpy, {
      business_website: '',
      appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      playstore_url: PLATFORM_LINKS.SUCCESS.playstore_url,
    });
    const user = getUser();
    const platform = getAvailablePlatform(user);
    expect(platform).toBe(Platform.ANDROID);
  });

  test('should return iOS if present Website/Android not present', () => {
    updateUser(getStateSpy, {
      business_website: '',
      appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      playstore_url: '',
    });
    const user = getUser();
    const platform = getAvailablePlatform(user);
    expect(platform).toBe(Platform.IOS);
  });
});

describe('getLatestKey', () => {
  beforeEach(() => {
    getStateSpy.mockReset();
  });

  test('should return the Latest Key if multiple keys are present', () => {
    populateKeys(getStateSpy, getKeys('test'));
    const { keys } = store.getState().keys;
    const latestKey = getLatestKey(keys);
    expect(latestKey.id).toMatch(KEY.new);
  });

  test('should return the Key if single key is present', () => {
    const key = getKeys('test')[0];
    populateKeys(getStateSpy, [key]);
    const { keys } = store.getState().keys;
    const latestKey = getLatestKey(keys);
    expect(latestKey.id).toBe(key.id);
  });

  test('should return null if no key is present', () => {
    populateKeys(getStateSpy, []);
    const { keys } = store.getState().keys;
    const latestKey = getLatestKey(keys);
    expect(latestKey).toBeNull();
  });
});

describe('getAvailablePlugin', () => {
  beforeEach(() => {
    getStateSpy.mockReset();
  });

  test('should return empty string if Merchant Selected and Suggeted Plugin are null', () => {
    const merchantSelectedPlugin = null;
    const suggestedPlugin = null;

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin(supportedPlugins, [merchantSelectedPlugin, suggestedPlugin]);
    expect(plugin).toBe('');
  });

  test('should return empty string if plugin is not supported', () => {
    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin(supportedPlugins, ['OpenCart']);
    expect(plugin).toBe('');
  });

  test('should return Suggested Plugin if present', () => {
    const merchantSelectedPlugin = null;
    const suggestedPlugin = 'Wix';

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin(supportedPlugins, [merchantSelectedPlugin, suggestedPlugin]);
    expect(plugin).toBe(suggestedPlugin);
  });

  test('should prioritize Merchant Selected Plugin over Suggested Plugin', () => {
    const merchantSelectedPlugin = 'Shopify';
    const suggestedPlugin = 'Wix';

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin(supportedPlugins, [merchantSelectedPlugin, suggestedPlugin]);
    expect(plugin).toBe(merchantSelectedPlugin);
  });
});
