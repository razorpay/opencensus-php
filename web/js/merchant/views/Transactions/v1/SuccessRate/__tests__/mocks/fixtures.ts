import moment from 'moment';
import {
  CARD_GROUPING_DATA,
  SR_FILTERS,
} from 'merchant/views/Transactions/v1/SuccessRate/constants';

type Interval = {
  from: number;
  to: number;
  sr: number;
  successful: number;
  total: number;
};

export const DEFAULT_PROPS = {
  MethodFilter: {
    filtersList: [SR_FILTERS.Card],
    activeTab: 'Card',
    tab: { selectedMethodType: 'debit' },
    user: { isOptimizerEnabled: true },
    isOptimizerEnabled: true,
    selectedGrouping: [
      {
        value: 'network',
        text: 'Card Networks',
        query: 'filter',
      },
    ],
  },
};

export type BaseStruct = {
  code: string;
  name: string;
  sr: number;
  successful: number;
  total: number;
  intervals: Interval[];
  groups: {
    [key: string]: BaseStruct[] | [];
  };
};

export type SrPayload = {
  entity: string;
  mode: string;
  from: number;
  to: number;
  filters: {
    method: string[];
    type?: string[];
    recurring_type?: string[];
  };
  group_by: {
    keys?: string[];
    limit?: number;
  };
  interval: number;
  merchant_id?: string;
  features: { use_alias: boolean };
};

export type SuccessSrResponse = {
  status_code: number;
  success: boolean;
  data: BaseStruct;
};

export type FailedResponse = {
  status_code: number;
  success: boolean;
  data: {
    Code: string;
    Description: string;
  };
};

export type DowntimeResponse = {
  status_code: number;
  success: boolean;
};

const MAX_TOTAL = {
  overall: 60,
  upi: 20,
  card: 20,
  netbanking: 20,
  emandate: 0,
  upi_autopay: 30,
};

const WEIGHT_MAPPINGS = {
  0: 1, // 0 (key) refers to Domestic payments as per DB
  1: 2, // 1 (key) refers to International payments as per DB
  card: 3,
  upi: 4,
  emandate: 5,
  netbanking: 6,
  credit: 7,
  debit: 8,
  prepaid: 9,
  bank: 10,
  ICIC: 11,
  SBIN: 12,
  UTIB: 13,
  HDFC: 14,
  others: 15,
  Visa: 16,
  overall: 17,
  RuPay: 18,
  intent: 19,
  collect: 20,
  upi_autopay: 21,
  auto: 22,
  initial: 23,
  LESS_THAN: 1,
  MORE_THAN: 2,
};

const NAME_MAPPINGS = {
  upi: 'upi',
  card: 'card',
  netbanking: 'netbanking',
  emandate: 'emandate',
  upi_autopay: 'upi_autopay',
  others: 'others',
  ICIC: 'ICICI bank',
  0: 'Domestic', // 0 (key) refers to Domestic payments as per DB
  1: 'International', // 1 (key) refers to International payments as per DB
  LESS_THAN: 'Less than 15k',
  MORE_THAN: 'More than 15k',
};

const GROUP_BY_MAPPINGS = {
  overall: {
    keys: {
      method: ['upi', 'card', 'netbanking', 'upi_autopay', 'others'],
    },
  },
  upi: {
    keys: { upi_type: ['intent', 'collect', 'others'] },
  },
  card: {
    credit: {
      keys: {
        network: ['Visa', 'others'],
        issuer: ['ICIC', 'HDFC', 'UTIB', 'others'],
        international: ['0', '1', 'others'], // 0 (key) refers to Domestic payments and 1 refers to International payments as per DB
      },
    },
    debit: {
      keys: {
        network: ['Visa', 'others'],
        issuer: ['UTIB', 'SBIN', 'others'],
        international: ['0', '1', 'others'], // 0 (key) refers to Domestic payments as per DB
      },
    },
    prepaid: {
      keys: {
        network: ['Visa', 'RuPay', 'others'],
        issuer: ['ICIC', 'others'],
        international: ['0', 'others'], // 0 (key) refers to Domestic payments  as per DB
      },
    },
  },
  netbanking: {
    keys: {
      bank: ['ICIC', 'SBIN', 'others'],
    },
  },
  emandate: {
    keys: {
      bank: [],
    },
  },
  upi_autopay: {
    initial: {
      keys: {
        amount_split: ['LESS_THAN', 'MORE_THAN', 'others'],
      },
    },
    auto: {
      keys: {
        amount_split: ['LESS_THAN', 'MORE_THAN', 'others'],
      },
    },
  },
};

type GetIntervals = {
  from: number;
  to: number;
  interval: number;
  key: string;
  method: string;
};

const getIntervals = ({ from, to, interval, key, method }: GetIntervals): Interval[] => {
  const startTime = interval >= 1440 ? moment.unix(from).startOf('day').unix() : from;
  const diff = interval * 60; //seconds
  const numberOfItems = Math.floor((to - from) / diff);
  const list: Interval[] = [];
  const weight = WEIGHT_MAPPINGS[key] || 0;
  const total = MAX_TOTAL[method];
  const successful = total - weight;

  for (let i = 0; i <= numberOfItems; i++) {
    list.push({
      from: !list.length ? startTime : list[list.length - 1].from + diff,
      to: !list.length ? from + diff : list[list.length - 1].from + diff * 2,
      sr: Math.floor((successful / total) * 100) + i,
      successful: successful + i,
      total: total + numberOfItems,
    });
  }

  return list;
};

/**
 * Please refer to this doc for understanding on how MAX_TOTAL and WEIGHT_MAPPINGS
 * works to generate mock sr response.
 * https://docs.google.com/document/d/1bnanI1xUypd9O1NXNSPTIShn23vtCBIKPi5kNd7bGoI/edit
 */

export const getSrResponse = (
  payload: SrPayload,
): {
  status_code: number;
  success: boolean;
  data: BaseStruct;
} => {
  const baseStruct: BaseStruct = {
    code: 'razorpay',
    groups: {},
    name: 'razorpay',
    sr: 0,
    successful: 0,
    total: 0,
    intervals: [],
  };
  const { filters, group_by, from, to, interval } = payload;
  const isParticularMethod = filters.method.length === 1;
  const method = isParticularMethod ? filters.method[0] : 'overall';
  const isFilterTypePresent = filters?.type || filters?.recurring_type;
  const isGroupByPresent = group_by?.keys; //group by as of now for sr dashboard is only supported by keys.

  baseStruct.total = MAX_TOTAL[method];
  baseStruct.successful = baseStruct.total - WEIGHT_MAPPINGS[method];
  baseStruct.sr = Math.floor((baseStruct.successful / baseStruct.total) * 100);
  baseStruct.intervals = getIntervals({
    from,
    to,
    interval,
    key: method,
    method,
  });

  //Enhancement: 'keys' could be picked dynamically.
  if (isGroupByPresent) {
    const groups = isFilterTypePresent
      ? GROUP_BY_MAPPINGS[method][filters.type].keys[group_by.keys]
      : GROUP_BY_MAPPINGS[method].keys[group_by.keys];

    group_by.keys?.forEach((item) => {
      baseStruct.groups[item] = groups.map((item) => {
        const weight = WEIGHT_MAPPINGS[item] || 0;
        const total = MAX_TOTAL[method] + groups.length;
        const successful = total - weight;
        const sr = Math.floor((successful / total) * 100);
        return {
          ...baseStruct,
          code: item,
          name: NAME_MAPPINGS[item] || item,
          intervals: getIntervals({ from, to, interval, key: item, method }),
          groups: [],
          sr,
          successful,
          total,
        };
      });
    });
  }

  return {
    status_code: 200,
    success: true,
    data: baseStruct,
  };
};

export const FAILED_SR_ERROR_RESPONSE = {
  status_code: 200,
  success: true,
  data: {
    Code: 'SERVER_ERROR',
    Description: 'The server encountered an error. The incident has been reported to admins.',
  },
};

export const DOWNTIME_MOCK_RESPONSE = {
  status_code: 200,
  success: true,
  data: [],
};

type ErrorType = {
  reason: string;
  count: number;
};

export type ErrorResponse = {
  status_code: number;
  success: boolean;
  data: {
    [key: string]: ErrorType[];
  };
};

export const getErrorResponse = (payload: SrPayload): ErrorResponse => {
  const { filters, group_by } = payload;
  const errorTypes = ['bank', 'customer', 'business', 'others'];
  const isParticularMethod = filters?.method?.length === 1;
  const method = isParticularMethod ? filters.method[0] : 'overall';
  const type = filters?.type || [];
  const keys = group_by?.keys || [];
  const string = [method, type, keys].flat().join('-');
  const mockTypes = [
    {
      reason: `${string} payments failed for ${type}`,
      count: MAX_TOTAL[method],
    },
    {
      reason: `${string} payments not allowed for ${type}`,
      count: MAX_TOTAL[method],
    },
  ];

  const errors = errorTypes.reduce(
    (acc, type) => ({
      ...acc,
      [type]: mockTypes,
    }),
    {},
  );

  return {
    status_code: 200,
    success: true,
    data: errors,
  };
};

type Tabs = 'Card' | 'Overall' | 'Upi' | 'Netbanking' | 'Emandate' | 'UpiAutopay';
type MerchantErrors = 'default' | 'international';

type SuccessRate = {
  isLoading: boolean;
  isLoadingMerchantErrors: boolean;
  activeTab: string;
  tabs: {
    [keys in Tabs]: {
      selectedDropdownFilterOptions: string[];
    };
  };
  merchantErrors: {
    [keys in Tabs]: {
      failures: {
        [keys in MerchantErrors]: Record<string, ErrorType[]>;
      };
    };
  };
};

type InitialState = {
  successRate: SuccessRate;
};
export const getInitialState = ({
  successRate,
  errorResponse,
}: {
  successRate: SuccessRate;
  errorResponse: ErrorResponse;
}): InitialState => {
  const { tabs, merchantErrors } = successRate;
  return {
    successRate: {
      ...successRate,
      isLoading: false,
      isLoadingMerchantErrors: false,
      activeTab: 'Card',
      tabs: {
        ...tabs,
        Card: {
          ...tabs.Card,
          selectedDropdownFilterOptions: CARD_GROUPING_DATA,
        },
      },
      merchantErrors: {
        ...merchantErrors,
        Card: {
          ...merchantErrors.Card,
          failures: {
            ...merchantErrors.Card.failures,
            default: errorResponse.data,
          },
        },
      },
    },
  };
};
