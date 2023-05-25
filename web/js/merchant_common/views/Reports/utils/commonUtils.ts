import { User } from 'common/typings';
import { uniqueArray, randomInt } from 'common/utils/rzp-utils';

import { prefixEntityValue } from 'merchant_common/helpers/data';
import { AccountStateType } from 'merchant_common/views/Reports/types/account';
import { Format } from 'merchant_common/views/Reports/types';
import {
  DEFAULT_FORMATS,
  RPT_FORMAT,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/constants';

export { randomInt };

export const arrayFromRange = (start, end) => {
  const length = end - start + 1;
  return Array.from({ length }, (_, idx) => idx + start);
};

export const appendReportTypeHeader = (isPartnerReport) => {
  return {
    ...(isPartnerReport && { 'X-Report-Type': 'partner' }),
  };
};

export const sortCardsByReportType = (configs) => {
  try {
    if (!configs || !Array.isArray(configs)) return [];
    const obj = [...configs]?.reduce((prev, curr) => {
      return {
        ...prev,
        [curr?.type_title ?? curr?.type]: [],
      };
    }, {});
    configs?.forEach((config) => obj[config?.type_title ?? config?.type]?.push(config));
    return Object.entries(obj);
  } catch (err) {
    console.error(err);
    return [];
  }
};

// for emails selection dropdown
export const getAvailableEmails = (user: User): string[] => {
  if (!user) return [];

  const { email, contact_email, transaction_report_email } = user;

  const availableEmails = uniqueArray([
    email,
    contact_email,
    ...(transaction_report_email ? transaction_report_email.split(',') : []),
  ]);

  return availableEmails;
};

// contains only for dashboard type === merchant
export const getMerchantAccounts = (
  accountsData,
  sessionUser?: User,
): AccountStateType | undefined => {
  if (!sessionUser) return undefined;

  const isAccountEnabled = Boolean(sessionUser && sessionUser.isMarketplaceEnabled && accountsData);

  if (isAccountEnabled && accountsData) {
    const id = prefixEntityValue('account', sessionUser.current);

    const defaultAccount = {
      name: `${sessionUser.name || sessionUser.user?.name} (${id})`,
      id,
      email: sessionUser.email,
      tag: 'My Account',
      current: true,
    };

    const { accounts, loading } = accountsData;
    return loading
      ? accountsData
      : {
          ...accountsData,
          accounts: [defaultAccount, ...accounts],
        };
  }
  return undefined;
};

export const parseReqDataFromConfigs = (configs) => {
  return configs.map(({ name, description, id, type, template, ...otherProps }) => ({
    name,
    description,
    id,
    type,
    template: {
      referred_accounts: template?.referred_accounts,
    },
    type_title: otherProps?.type_title,
  }));
};

export const getAvailableFormats = (user?: User): Format[] => {
  const conditionalFormats: Format[] = [];

  if (user?.isCustomReportExtensionsEnabled) {
    conditionalFormats.push(RPT_FORMAT);
  }

  return [...DEFAULT_FORMATS, ...conditionalFormats];
};
