import moment, { Moment } from 'moment';
import qs from 'query-string';
import { RouteComponentProps } from 'react-router-dom';

import { Option } from 'common/components/Dropdown/types';
import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { getDateFormat } from 'common/utils/date-utils';
import {
  stringifyQueryParams,
  encodeSensitiveFields,
  decodeSensitiveFields,
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
  const { from, to } = qs.parse(location.search);
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
  const { ID, EMAIL, CONTACT, ORDER_ID, PAYMENT_ID } = SearchQueryParam;
  const search = decodeSensitiveFields(qs.parse(location.search)) as Record<
    SearchQueryParamType,
    string | null
  >;
  const { id, email, contact, country_code, order_id, payment_id } = search;
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
    const searchParams = qs.parse(location.search);
    paginate({ ...searchParams, ...params });
  };

export const getCreatedOnTime = ({ created_at }: { created_at: number }): string => {
  const format = getDateFormat(created_at);
  const createdAt = moment.unix(created_at).format(format);
  return createdAt;
};

export const isTransactionsV2Enabled = (splitz: SpiltzContextState, user: User): boolean => {
  const { abExperiments } = splitz || { abExperiments: { Transactions_Revamp: undefined } };

  if (!abExperiments?.Transactions_Revamp) return false;

  if (user.isOrgCurlec) {
    return false;
  }
  return isExperimentEnabled(abExperiments.Transactions_Revamp) && user.isOrgRZP;
};
