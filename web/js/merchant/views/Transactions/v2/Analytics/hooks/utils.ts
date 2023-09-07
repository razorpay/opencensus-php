import { Environments } from 'common/typings';
import {
  AccumalateData,
  AccumalateResponse,
  FailedOverviewResult,
  FailedPaymentsAPIResponse,
  FailedPaymentsRequestPayload,
  SuccessRateRequestPayload,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { Duration } from 'merchant/views/Transactions/v2/common/types';

export const getFailedPaymentsRequestPayload = (
  gte: number,
  lte: number,
): FailedPaymentsRequestPayload => {
  return {
    entity: 'payments',
    from: gte,
    to: lte,
    mode: 'razorpay',
    group_by: {
      limit: 6,
    },
  };
};

export const getSuccessRateRequestPayload = (
  gte: number,
  lte: number,
): SuccessRateRequestPayload => {
  return {
    entity: 'payments',
    from: gte,
    to: lte,
    interval: 60,
    mode: 'razorpay',
    features: { use_alias: true },
  };
};

export const getAnalyticsRequestPayload = (
  duration: Duration,
  mode: Environments,
): Record<string, unknown> => {
  const { from: gte, to: lte } = duration;
  return {
    filters: {
      default: [
        {
          created_at: {
            gte,
            lte,
          },
          authorized_at: {
            gt: 0,
          },
        },
      ],
      refundsnormalspeed: [
        {
          created_at: {
            gte,
            lte,
          },
          speed_processed: 'normal',
        },
      ],
      refundsinstantspeed: [
        {
          created_at: {
            gte,
            lte,
          },
          speed_processed: 'instant',
        },
      ],
      method_filter: [
        {
          created_at: {
            gte,
            lte,
          },
          authorized_at: {
            gt: 0,
          },
        },
      ],
    },
    aggregations: {
      paymentcount: {
        agg_type: 'count',
        details: {
          index: 'payments',
          column: 'base_amount',
          group_by: ['status'],
          mode,
        },
      },
      paymentsum: {
        agg_type: 'sum',
        details: {
          index: 'payments',
          column: 'base_amount',
          group_by: ['status'],
          mode,
        },
      },
      refundcountnormal: {
        filter_key: 'refundsnormalspeed',
        agg_type: 'count',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
      refundsumnormal: {
        filter_key: 'refundsnormalspeed',
        agg_type: 'sum',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
      refundcountinstant: {
        filter_key: 'refundsinstantspeed',
        agg_type: 'count',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
      refundsuminstant: {
        filter_key: 'refundsinstantspeed',
        agg_type: 'sum',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
      paymentbymethod: {
        agg_type: 'sorted_sum',
        filter_key: 'method_filter',
        details: {
          index: 'payments',
          column: 'base_amount',
          group_by: ['method'],
        },
      },
    },
  };
};

export const getRefundsRequestPayload = (
  duration: Duration,
  mode: Environments,
): Record<string, unknown> => {
  const { from: gte, to: lte } = duration;
  return {
    filters: {
      normalspeed: [
        {
          created_at: {
            gte,
            lte,
          },
          speed_processed: 'normal',
        },
      ],
      instantspeed: [
        {
          created_at: {
            gte,
            lte,
          },
          speed_processed: 'instant',
        },
      ],
    },
    aggregations: {
      refundcountnormal: {
        filter_key: 'normalspeed',
        agg_type: 'count',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
      refundsumnormal: {
        filter_key: 'normalspeed',
        agg_type: 'sum',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
      refundcountinstant: {
        filter_key: 'instantspeed',
        agg_type: 'count',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
      refundsuminstant: {
        filter_key: 'instantspeed',
        agg_type: 'sum',
        details: {
          group_by: ['status'],
          index: 'refunds',
          column: 'base_amount',
          mode,
        },
      },
    },
  };
};

export function accumalateData({ data, status, exclude }: AccumalateData): number {
  return data.reduce((acc, item) => {
    if (exclude) {
      if (!status.includes(item.status)) {
        acc += item.value;
      }
    } else if (status.includes(item.status)) {
      acc += item.value;
    }
    return acc;
  }, 0);
}

export function accumalateCountAmount({
  countData,
  sumData,
  status,
  exclude = false,
}: AccumalateResponse): {
  count: number;
  amount: number;
} {
  const count = accumalateData({ data: countData, status, exclude });
  const amount = accumalateData({ data: sumData, status, exclude });
  return { count, amount };
}

export const accumulateFailedPaymentsTotal = (data: FailedPaymentsAPIResponse): number => {
  let totalFailedPayments = 0;
  for (const paymentType in data) {
    // istanbul ignore else
    if (data.hasOwnProperty(paymentType)) {
      const paymentTypeData = data[paymentType];
      if (paymentTypeData.length > 0) {
        const typeTotal = paymentTypeData.reduce((total, payment) => total + payment.count, 0);
        totalFailedPayments += typeTotal;
      }
    }
  }
  return totalFailedPayments;
};

export const accumulateFailureData = (data: FailedPaymentsAPIResponse): FailedOverviewResult => {
  const result: FailedOverviewResult = {
    customer: { value: 0, failure_types: [] },
    bank: { value: 0, failure_types: [] },
    business: { value: 0, failure_types: [] },
    others: { value: 0, failure_types: [] },
  };

  for (const key in data) {
    if (data.hasOwnProperty(key) && result.hasOwnProperty(key)) {
      const objectsArray = data[key];

      if (objectsArray.length > 0) {
        result[key].value = objectsArray.reduce((total, obj) => total + obj.count, 0);

        objectsArray.forEach((obj) => {
          const { reason } = obj;
          if (!result[key].failure_types.includes(reason)) {
            result[key].failure_types.push(reason);
          }
        });
      }
    }
  }

  return result;
};
