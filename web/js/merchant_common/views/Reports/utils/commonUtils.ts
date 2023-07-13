import { uniq as uniqViaLodash } from 'lodash';
import { User } from 'common/typings';
import { randomInt } from 'common/utils/rzp-utils';
import { prefixEntityValue } from 'merchant_common/helpers/data';
import { AccountStateType } from 'merchant_common/views/Reports/types/account';
import { Format, QueryStringParams } from 'merchant_common/views/Reports/types';
import {
  DEFAULT_FORMATS,
  RPT_FORMAT,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/constants';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import {
  BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
  BATCH_PAYMENT_PAGE_PAYMENT_REPORT,
  CONFIG_TYPE_BATCH_PAGES,
  TYPE_TITLE_BATCH_PAGES,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/constants';

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

export const sortCardsByReportType = (configs): Array<[string, BaseConfigType[]]> => {
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
    return [];
  }
};

// for emails selection dropdown
export const getAvailableEmails = (user: User, additional?: string[]): string[] => {
  if (!user) return [];

  const { email, contact_email, transaction_report_email } = user;

  const availableEmails = uniqViaLodash([
    email,
    contact_email,
    ...(transaction_report_email ? transaction_report_email.split(',') : []),
    ...(additional ? additional : []),
  ]) as string[];

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

export const parseReqDataFromConfigs = (configs): BaseConfigType[] => {
  return configs.map(({ name, description, id, type, template, ...otherProps }) => {
    const config = {
      name,
      description,
      id,
      type,
      template: {
        referred_accounts: template?.referred_accounts,
      },
      type_title: otherProps?.type_title,
      emails: otherProps?.emails ?? [],
    };

    if (name === BATCH_PAYMENT_PAGE_PAYMENT_REPORT || name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT) {
      config.type_title = TYPE_TITLE_BATCH_PAGES;
      config.type = CONFIG_TYPE_BATCH_PAGES;
    }

    return config;
  });
};

export const getAvailableFormats = (user?: User): Format[] => {
  const conditionalFormats: Format[] = [];

  if (user?.isCustomReportExtensionsEnabled) {
    conditionalFormats.push(RPT_FORMAT);
  }

  return [...DEFAULT_FORMATS, ...conditionalFormats];
};

export const getQueryString = ({
  title,
  viewType,
  skip = 0,
  count = 25,
}: QueryStringParams): string => {
  return `title=${title}&view_type=${viewType}&skip=${skip}&count=${count}`;
};
