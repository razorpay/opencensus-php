import {
  ACCOUNT_LABELS_MAP,
  ANALYTICS_PLATFORM,
  DEFAULT_INTEGRATION_OPTIONS,
  FACEBOOK_INTEGRATION_OPTIONS,
  INTEGRATION_TYPE,
  ENCODED_KEYS,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

export const getAccountDetails = (
  config: Record<string, any>,
  analyticsPlatform: string,
): Record<string, string>[] => {
  const labelMap = Object.keys(ACCOUNT_LABELS_MAP[analyticsPlatform]);

  const accountDetails: Record<string, string>[] = Object.keys(config)
    ?.filter((item) => labelMap.includes(item))
    ?.map((keyName) => {
      return {
        label: ACCOUNT_LABELS_MAP[analyticsPlatform][keyName],
        value: ENCODED_KEYS.includes(keyName) ? window?.atob(config[keyName]) : config[keyName],
      };
    });

  return accountDetails;
};

export const getIntegrationOptions = (
  accountConfigs: Record<string, any>[],
  analyticsPlatform: string,
): Record<string, string>[] => {
  const integrationOptions =
    analyticsPlatform === ANALYTICS_PLATFORM.facebookAds.key
      ? FACEBOOK_INTEGRATION_OPTIONS
      : DEFAULT_INTEGRATION_OPTIONS;

  return accountConfigs.length > 1
    ? integrationOptions.filter((item) => item.name !== INTEGRATION_TYPE.frontend)
    : integrationOptions;
};

interface AccountConfig {
  id?: string;
  data?: Record<string, any>;
  customIntegrationOptions?: Record<string, any>;
  integrationMethod?: string;
  isNotEditable?: boolean;
  authId?: string;
}

type MerchantAnalyticsConfigs = Record<string, any>;
type AnalyticsPlatform = string;

export const accountConfigsFormatter = (
  merchantAnalyticsConfigs: MerchantAnalyticsConfigs,
  analyticsPlatform: AnalyticsPlatform,
): AccountConfig[] => {
  const accountConfigs = merchantAnalyticsConfigs?.[analyticsPlatform]?.accounts || [];

  const formattedAccountConfig: AccountConfig[] = [
    {
      customIntegrationOptions:
        analyticsPlatform !== ANALYTICS_PLATFORM.facebookAds.key
          ? DEFAULT_INTEGRATION_OPTIONS
          : FACEBOOK_INTEGRATION_OPTIONS,
    },
  ];

  if (accountConfigs?.length === 0) {
    return formattedAccountConfig;
  }

  const formattedAccountConfigs: AccountConfig[] = accountConfigs.map((config: any) => {
    let newConfig: AccountConfig = {
      id: config.id,
      data: getAccountDetails(config, analyticsPlatform),
      customIntegrationOptions: getIntegrationOptions(accountConfigs, analyticsPlatform),
      integrationMethod: config.integration_method,
      isNotEditable: config.integration_method
        ? config.integration_method !== INTEGRATION_TYPE.frontend
        : false,
    };

    if (analyticsPlatform === ANALYTICS_PLATFORM.googleAds.key) {
      const authId = config?.analytics_auth_account_id;
      newConfig = { ...newConfig, authId };
    }

    return newConfig;
  });

  return formattedAccountConfigs;
};

interface IntergationOptions {
  label: string;
  name: string;
}

export const addAnalyticsAccount = (
  analyticsPlatform: string,
): { customIntegrationOptions: IntergationOptions[] } => {
  const integrationOptions =
    analyticsPlatform === ANALYTICS_PLATFORM.facebookAds.key
      ? FACEBOOK_INTEGRATION_OPTIONS
      : DEFAULT_INTEGRATION_OPTIONS;
  const customIntegrationOptions: IntergationOptions[] = integrationOptions.filter(
    (item) => item.name !== INTEGRATION_TYPE.frontend,
  );
  return { customIntegrationOptions };
};

export const updateIntegrationMethod = (
  val: string,
  accountConfigs: AccountConfig[] = [{}],
): AccountConfig[] => {
  if (accountConfigs.length === 0) {
    return accountConfigs;
  }

  const modifiedAccountConfigs: AccountConfig[] = [...accountConfigs];
  const lastElement = modifiedAccountConfigs[modifiedAccountConfigs.length - 1];

  if (typeof lastElement === 'object') {
    lastElement.integrationMethod = val;
  }

  return modifiedAccountConfigs;
};

export const updateAddAccountCtaState = (
  integratonMethod: string,
  accountConfig: boolean,
): boolean => {
  if (
    (integratonMethod === INTEGRATION_TYPE.backend || integratonMethod === INTEGRATION_TYPE.both) &&
    accountConfig
  ) {
    return false;
  } else {
    return true;
  }
};

export const updateSaveEventsCtaState = (
  integratonMethod: string,
  accountConfig: boolean,
): boolean => {
  if (
    integratonMethod === INTEGRATION_TYPE.frontend ||
    ((integratonMethod === INTEGRATION_TYPE.backend ||
      integratonMethod === INTEGRATION_TYPE.both) &&
      accountConfig)
  ) {
    return false;
  } else {
    return true;
  }
};
