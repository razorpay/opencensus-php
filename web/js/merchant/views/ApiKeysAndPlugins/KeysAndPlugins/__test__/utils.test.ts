import {
  getProvidedChannels,
  getLatestKey,
  getAvailablePlatform,
  getAvailablePlugin,
} from './../utils';
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
import { NO_PLUGIN_OPTION } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';

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

  test('should not return AppStore if it was not selected and showProvidedChannels is true', () => {
    updateUser(getStateSpy, {
      business_website: '',
      appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      playstore_url: '',
    });
    const user = getUser();
    const platform = getAvailablePlatform(user, { showProvidedChannels: true });
    expect(platform).toBe(Platform.WEBSITE);
  });

  test('should return ios if it was selected and provided and showProvidedChannels is true', () => {
    updateUser(getStateSpy, {
      business_website: '',
      appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      playstore_url: '',
      merchant_business_detail: {
        website_details: {
          ios_app_present: true,
        },
      },
    });
    const user = getUser();
    const platform = getAvailablePlatform(user, { showProvidedChannels: true });
    expect(platform).toBe(Platform.IOS);
  });
});

describe('getAvailablePlugin', () => {
  beforeEach(() => {
    getStateSpy.mockReset();
  });

  test('should return null if Merchant Selected and Suggeted Plugin are null', () => {
    const merchantSelectedPlugin = null;
    const suggestedPlugin = null;

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin({
      supportedPlugins,
      merchantSelectedPlugin,
      suggestedPlugin,
    });
    expect(plugin).toBeNull();
  });

  test('should return No Plugin Option if Merchant Selected is empty string and Suggeted Plugin is null', () => {
    const merchantSelectedPlugin = '';
    const suggestedPlugin = null;

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin({
      supportedPlugins,
      merchantSelectedPlugin,
      suggestedPlugin,
    });
    expect(plugin).toBe(NO_PLUGIN_OPTION.name);
  });

  test('should return empty string if Merchant Selected is null and Suggeted Plugin is empty string', () => {
    const merchantSelectedPlugin = null;
    const suggestedPlugin = '';

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin({
      supportedPlugins,
      merchantSelectedPlugin,
      suggestedPlugin,
    });
    expect(plugin).toBeNull();
  });

  test('should return empty string if plugin is not supported', () => {
    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin({
      supportedPlugins,
      merchantSelectedPlugin: 'OpenCart',
      suggestedPlugin: null,
    });
    expect(plugin).toBeNull();
  });

  test('should return Suggested Plugin if present', () => {
    const merchantSelectedPlugin = null;
    const suggestedPlugin = 'Wix';

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin({
      supportedPlugins,
      merchantSelectedPlugin,
      suggestedPlugin,
    });
    expect(plugin).toBe(suggestedPlugin);
  });

  test('should prioritize Merchant Selected Plugin over Suggested Plugin', () => {
    const merchantSelectedPlugin = 'Shopify';
    const suggestedPlugin = 'Wix';

    populateSupportedPlugins(getStateSpy);
    const { items: supportedPlugins } = store.getState().plugins.supported;
    const plugin = getAvailablePlugin({
      supportedPlugins,
      merchantSelectedPlugin,
      suggestedPlugin,
    });
    expect(plugin).toBe(merchantSelectedPlugin);
  });
});

describe('getProvidedChannels', () => {
  test('should return only selected and url provided channels', () => {
    updateUser(getStateSpy, {
      business_website: PLATFORM_LINKS.SUCCESS.business_website,
      appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      playstore_url: PLATFORM_LINKS.SUCCESS.playstore_url,
      merchant_business_detail: {
        website_details: {
          ios_app_present: true,
        },
      },
    });
    const user = getUser();
    const channels = getProvidedChannels(user);
    expect(channels.length).toBe(1);
    expect(channels?.[0]).toBe(Platform.IOS);
  });

  test('should return only selected and url provided channels', () => {
    updateUser(getStateSpy, {
      business_website: PLATFORM_LINKS.SUCCESS.business_website,
      appstore_url: PLATFORM_LINKS.SUCCESS.appstore_url,
      playstore_url: PLATFORM_LINKS.SUCCESS.playstore_url,
      merchant_business_detail: {
        website_details: {
          ios_app_present: true,
          android_app_present: true,
          website_present: true,
        },
      },
    });
    const user = getUser();
    const channels = getProvidedChannels(user);
    expect(channels.length).toBe(3);
  });
});
