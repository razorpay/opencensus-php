import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';
import { Platform, Plugin } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';

const storeData = store.getState();

export const PLATFORM_LINKS = {
  SUCCESS: {
    [Platform.WEBSITE]: 'https://razorpay.com',
    [Platform.IOS]: 'https://apps.apple.com/in/app/razorpay-accept-payments-now/id1497250144',
    [Platform.ANDROID]: 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
  },
  FAILURE: {
    [Platform.WEBSITE]: 'razorpay.com',
    [Platform.IOS]: 'https://apps.apple.com/in/app/',
    [Platform.ANDROID]: 'https://play.google.com/store/apps/details',
  },
};

export const SUPPORTED_PLUGINS: Record<string, Plugin> = {
  Shopify: {
    name: 'Shopify',
    icon: 'https://cdn.razorpay.com/static/assets/product-led-onboarding/Shopify.svg',
    integration_guide:
      'https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/shopify/',
    integration_url:
      'https://shopify.razorpay.com/admin/settings/payments/alternative-providers/1058840',
  },
  Wix: {
    name: 'Wix',
    icon: 'https://cdn.razorpay.com/static/assets/product-led-onboarding/Wix.svg',
    integration_guide: 'https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/wix/',
    integration_url: 'https://support.wix.com/en/article/connecting-razorpay-as-a-payment-provider',
  },

  WordPress: {
    name: 'WordPress',
    icon: 'https://cdn.razorpay.com/static/assets/product-led-onboarding/Wordpress.svg',
    integration_guide:
      'https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/wordpress/',
    integration_url: '',
  },
};

export const populateSupportedPlugins = (getStateSpy) => {
  getStateSpy.mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.plugins.supported = {
      ...clonedStore.plugins.supported,
      loading: false,
      items: SUPPORTED_PLUGINS,
    };
    return clonedStore;
  });
};

export const updateUser = (getStateSpy, user) => {
  getStateSpy.mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.session.user = {
      ...clonedStore.session.user,
      ...user,
    };
    return clonedStore;
  });
};

export const KEY = {
  new: 'new',
  old: 'old',
  live: 'live',
  test: 'test',
};

export const KEYS = {
  old: {
    resourceIdField: 'id',
    id: KEY.old,
    entity: 'key',
    created_at: 1660000000,
    updated_at: 1660000000,
    expired_at: 1668575655,
    resourceUrl: 'keys',
  },
  new: {
    resourceIdField: 'id',
    id: KEY.new,
    entity: 'key',
    created_at: 1668575655,
    updated_at: 1668575655,
    expired_at: null,
    resourceUrl: 'keys',
  },
};

export const getKeys = (mode: 'live' | 'test', oldKey = null) => {
  return [
    {
      ...KEYS.new,
      id: `${KEY.new}_${KEY[mode]}`,
      secret: `${KEY.new}_${KEY[mode]}_secret`,
    },
    {
      ...KEYS.old,
      id: oldKey ?? `${KEY.old}_${KEY[mode]}`,
      secret: `${KEY.old}_${KEY[mode]}_secret`,
    },
  ];
};

export const populateKeys = (getStateSpy, keys) => {
  getStateSpy.mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.keys = {
      ...clonedStore.keys,
      loading: false,
      isLoaded: true,
      keys,
      count: 1,
    };
    return clonedStore;
  });
};
