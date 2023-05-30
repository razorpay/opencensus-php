import moment from 'moment';

type Interval = {
  from: number;
  to: number;
  sr: number;
  successful: number;
  total: number;
};

type BaseStruct = {
  code: string;
  name: string;
  sr: number;
  successful: number;
  total: number;
  intervals: Interval[];
  groups: {
    [key: string]: BaseStruct[];
  };
};

export type SrPayload = {
  from: number;
  to: number;
  filters: {
    method: string[];
    type?: string[];
  };
  group_by: {
    keys?: string[];
  };
  interval: number;
};

const MAX_TOTAL = {
  overall: 60,
  upi: 20,
  card: 20,
  netbanking: 20,
  emandate: 0,
};

const WEIGHT_MAPPINGS = {
  0: 1,
  1: 2,
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
};

const NAME_MAPPINGS = {
  upi: 'upi',
  card: 'card',
  netbanking: 'netbanking',
  emandate: 'emandate',
  others: 'others',
  ICIC: 'ICICI bank',
};

const GROUP_BY_MAPPINGS = {
  overall: {
    keys: {
      method: ['upi', 'card', 'netbanking', 'others'],
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
      },
    },
    debit: {
      keys: {
        network: ['Visa', 'others'],
        issuer: ['UTIB', 'SBIN', 'others'],
      },
    },
    prepaid: {
      keys: {
        network: ['Visa', 'RuPay', 'others'],
        issuer: ['ICIC', 'others'],
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
  const isFilterTypePresent = filters?.type;
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

export const MERCHANT_ERROR_RESPONSE_OVERALL_SUCCESS = {
  status_code: 200,
  success: true,
  data: {
    customer: [
      { reason: 'Payment timed-out', count: 4992 },
      { reason: 'Payment cancelled', count: 916 },
    ],
    bank: [
      { reason: 'Payment declined by bank', count: 1929 },
      { reason: 'Bank technical issue', count: 1075 },
    ],
    business: [
      { reason: 'The is invalid', count: 4 },
      { reason: 'Bank technical issue', count: 2 },
    ],
    others: [
      { reason: 'There was an issue with the payment request.', count: 2 },
      { reason: 'Bank technical issue', count: 1 },
    ],
  },
};
