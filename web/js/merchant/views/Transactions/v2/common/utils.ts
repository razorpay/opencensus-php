import {
  CurrencyCodeType,
  convertToMajorUnit,
  convertToMinorUnit,
} from '@razorpay/i18nify-js/currency';
import moment, { Moment } from 'moment';
import qs from 'query-string';
import { Environments, User } from 'common/typings';

import { Option } from 'common/components/Dropdown/types';
import { ANALYTICS } from 'common/constant';
import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getDateFormat } from 'common/utils/date-utils';
import {
  decodeSensitiveFields,
  encodeSensitiveFields,
  stringifyQueryParams,
} from 'common/utils/rzp-utils';
import { validateUnixTimestamp } from 'merchant/views/Settlements/v3/utils/common';
import { SearchQueryParamType } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/types';

import {
  ALL_VALUE,
  CURRENT_YEAR_JAN_TILL_DATE,
  CUSTOM,
  LAST_30_DAYS,
  LAST_7_DAYS,
  LAST_90_DAYS,
  SearchQueryParam,
  THIS_FINANCIAL_YEAR,
  TODAY,
} from './constants';
import { Duration, DurationOption, DurationOptionsMap, Paginate } from './types';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

export const generateOptions = (optionsMap: {
  [key: string]: string;
}): Array<{ title: string; value: string }> =>
  Object.entries(optionsMap).map(([value, title]) => ({
    title,
    value,
  }));

export const getValue = (selectedOptions: Option[]): string => {
  let values: string[] = [];
  for (const { value } of selectedOptions) {
    if (value === ALL_VALUE) {
      values = [];
      break;
    }
    values.push(value);
  }
  return String(values);
};

export const getDefaultSingleSelectValueAndOption = ({
  searchValue,
  options,
}: {
  searchValue: string | null;
  options: Option[];
}): {
  defaultValue: string;
  defaultOption: Option;
} => {
  let defaultOption = options[0];
  const defaultValue = (searchValue as string) || '';
  if (searchValue) {
    defaultOption = options.find(({ value }) => value === searchValue) as Option;
  }
  return { defaultValue, defaultOption };
};

export const getFromTime = (durationOption: DurationOption['value']): Moment => {
  switch (durationOption) {
    case TODAY:
      return moment().startOf('day');
    case LAST_30_DAYS:
      return moment().subtract(30, 'days').startOf('day');
    case LAST_90_DAYS:
      return moment().subtract(90, 'days').startOf('day');
    case CURRENT_YEAR_JAN_TILL_DATE:
      return moment().startOf('year');
    case THIS_FINANCIAL_YEAR:
      if (moment().quarter() === 1) {
        return moment().subtract(1, 'year').month('April').startOf('month');
      }
      return moment().month('April').startOf('month');
    case LAST_7_DAYS:
    default:
      return moment().subtract(7, 'days').startOf('day');
  }
};

export const endOfDay: Moment = moment().endOf('day');

export const getDefaultDateAndOption = ({
  customDurationOptionsMap,
  sectionOptions,
}: {
  customDurationOptionsMap: DurationOptionsMap;
  sectionOptions: Option[];
}): {
  defaultDate: Duration;
  defaultDuration: DurationOption;
} => {
  const { from, to } = qs.parse(window.location.search);
  const { last7Days, custom } = customDurationOptionsMap;
  let defaultDuration: { title: string; value: DurationOption['value'] } = {
    title: last7Days,
    value: LAST_7_DAYS,
  };
  let defaultDate = {
    from: getFromTime(defaultDuration.value).unix(),
    to: endOfDay.unix(),
  };
  if (from && to) {
    const queryParamsFromTimestamp = validateUnixTimestamp(from as string);
    const queryParamsToTimestamp = validateUnixTimestamp(to as string);
    if (queryParamsFromTimestamp && queryParamsToTimestamp) {
      defaultDate = { from: queryParamsFromTimestamp, to: queryParamsToTimestamp };
      defaultDuration = { title: custom, value: CUSTOM };
      for (const option of sectionOptions) {
        const { value } = option;
        const fromTimestamp = getFromTime(value as DurationOption['value']).unix();
        const toTimestamp = endOfDay.unix();
        if (fromTimestamp === queryParamsFromTimestamp && toTimestamp === queryParamsToTimestamp) {
          defaultDuration = option as DurationOption;
          break;
        }
      }
    }
  }
  return { defaultDate, defaultDuration };
};

export const getDefaultSearchByValueAndOption = ({
  searchByOptionsMap,
}: {
  searchByOptionsMap: {
    [key: string]: string;
  };
}): {
  defaultSearchByOption: Option;
  defaultSearchByValue: string;
} => {
  const { ID, EMAIL, CONTACT, ORDER_ID, PAYMENT_ID, NOTES } = SearchQueryParam;
  const search = decodeSensitiveFields(qs.parse(window.location.search)) as Record<
    SearchQueryParamType,
    string | null
  >;
  const { id, email, contact, country_code, order_id, payment_id, notes } = search;
  let defaultSearchByParam = '';
  switch (true) {
    case !!id:
    default:
      defaultSearchByParam = ID;
      break;
    case !!email:
      defaultSearchByParam = EMAIL;
      break;
    case !!country_code:
    case !!contact:
      defaultSearchByParam = CONTACT;
      break;
    case !!order_id:
      defaultSearchByParam = ORDER_ID;
      break;
    case !!payment_id:
      defaultSearchByParam = PAYMENT_ID;
      break;
    case !!notes:
      defaultSearchByParam = NOTES;
      break;
  }
  const defaultSearchByOption = {
    title: searchByOptionsMap[defaultSearchByParam],
    value: defaultSearchByParam,
  };
  const defaultSearchByValue = (search[defaultSearchByParam] as string) || '';
  return { defaultSearchByOption, defaultSearchByValue };
};

export const onSearch =
  (history: RouteComponentProps['history']) =>
  (args: Record<string, unknown>): void => {
    args = encodeSensitiveFields(args);
    const { pathname, hash, state } = history.location;
    history.replace({
      pathname,
      hash,
      search: stringifyQueryParams(args),
      state,
    });
  };

export const onPaginate =
  (paginate: Paginate) =>
  (params: Record<string, unknown>): void => {
    const searchParams = qs.parse(window.location.search);
    paginate({ ...searchParams, ...params });
  };

export const getCreatedOnTime = ({ created_at }: { created_at: number }): string => {
  const format = getDateFormat(created_at);
  const createdAt = moment.unix(created_at).format(format);
  return createdAt;
};

export const isTransactionsV2Enabled = (splitz: SpiltzContextState, user: User): boolean => {
  // HDFC Bank is excluded from Transactions V2 not for long though :)
  const excludedOrgs = [
    ORG_CUSTOM_CODE_MAP.HDFC_SMART_HUB,
    ORG_CUSTOM_CODE_MAP.HDFC_COLLECT_NOW,
    ORG_CUSTOM_CODE_MAP.HDFC_GIG,
  ];

  if (user.isJnKOmniEnabled) {
    return false;
  }

  if (excludedOrgs.some((org) => org.toLowerCase() === user.orgCustomCode?.toLowerCase())) {
    return user.isParityFeaturesEnabledForHDCF;
  }

  const { abExperiments } = splitz || { abExperiments: { Transactions_Revamp: undefined } };

  if (!abExperiments?.Transactions_Revamp) return false;

  // All optimiser merchants are parity merchants
  // All merchants whose org is Curlec are parity merchants
  // All merchants whose org is VAS are parity merchants
  const isExcludedMerchant = user.isFeatureEnabled('raas') || !user.isOrgRZP;
  const isTransactionsEnabledForExcludedMerchant = isExperimentEnabled(
    abExperiments?.enable_trxn_v2_for_excluded_merchants,
  );

  // for excluded merchants, if experiment is enabled, then show trxn v2
  if (isExcludedMerchant) {
    if (isTransactionsEnabledForExcludedMerchant) {
      return true;
    }
    return false;
  }

  return (
    Boolean(user.isCountryIndia || user.isCountrySingapore) &&
    isExperimentEnabled(abExperiments.Transactions_Revamp)
  );
};

export const isMicrofrontendSelfserveEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { microfrontend_selfserve: undefined } };

  if (!abExperiments?.microfrontend_selfserve) return false;

  return isExperimentEnabled(abExperiments.microfrontend_selfserve);
};

export const isSettlementRetryTimelineEnabled = (
  splitz: SpiltzContextState,
  user: User,
): boolean => {
  const { abExperiments } = splitz || { abExperiments: { Transaction_Retry_Timeline: undefined } };
  if (!abExperiments?.Transaction_Retry_Timeline) return false;
  if (user.isOrgCurlec) {
    return false;
  }
  return isExperimentEnabled(abExperiments.Transaction_Retry_Timeline) && user.isOrgRZP;
};

export const i18nifyConvertToMajorUnit = (
  value: number,
  currency: CurrencyCodeType = 'INR',
): number => {
  let majorAmt: number;
  try {
    majorAmt = convertToMajorUnit(value, { currency });
  } catch (error) {
    majorAmt = Number((value / 100).toFixed(2));
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `amount: ${value}, currency: ${currency}`,
        error: `${error}`,
      },
    });
  }
  return majorAmt;
};

export const i18nifyConvertToMinorUnit = (
  value: number,
  currency: CurrencyCodeType = 'INR',
): number => {
  let minorAmt: number;
  try {
    minorAmt = convertToMinorUnit(value, { currency });
  } catch (error) {
    minorAmt = Number((value * 100).toFixed(2));

    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `amount: ${value}, currency: ${currency}`,
        error: `${error}`,
      },
    });
  }
  return minorAmt;
};

export const isRefundRevampEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { refund_revamp: undefined } };
  if (!abExperiments?.refund_revamp) return false;
  return isExperimentEnabled(abExperiments.refund_revamp);
};

export const isBounceMemoEnabled = (splitz: SpiltzContextState): boolean => {
  const { abExperiments } = splitz || { abExperiments: { bounce_memo: undefined } };
  if (!abExperiments?.bounce_memo) return false;
  return isExperimentEnabled(abExperiments.bounce_memo);
};

// New features introduced within Transactions V2 are behind this experiment
export function isPaymentV2ParityFeatureEnabled(splitz, user) {
  return (
    isTransactionsV2Enabled(splitz, user) &&
    isExperimentEnabled(splitz?.abExperiments?.enable_trxn_v2_parity_features)
  );
}

export const getCountryTaxDefinition = ({ countryCode = '' }: { countryCode: string }) => {
  switch (countryCode) {
    case 'MY':
      return 'Tax';
    case 'IN':
    case 'SG':
    default:
      return 'GST';
  }
};

export const shouldHideAnalytics = (user: User, mode: Environments): boolean => {
  const isCurlecVASTestMode = mode === 'test' && !user.isOrgRZP;
  return isCurlecVASTestMode;
};
