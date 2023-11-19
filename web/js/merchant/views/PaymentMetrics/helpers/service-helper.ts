import { getPaymentMetricsData } from 'merchant/views/PaymentMetrics/service';

// change index and mode before live
export const getOverallCRData = ({
  lte,
  gte,
  breakdown = 'daily',
}: {
  lte: number;
  gte: number;
  breakdown?: string;
}) => {
  const payload = {
    aggregations: {
      checkout_overall_cr: {
        details: {
          index: 'cx_high_level_funnel',
          group_by: [`histogram_${breakdown}`, 'status'],
          mode: 'live',
        },
        agg_type: 'count',
      },
    },
    filters: {
      default: [
        {
          created_at: {
            gte,
            lte,
          },
          render_checkout_open_event: 'true',
          checkout_library: ['hosted', 'checkoutjs'],
        },
      ],
    },
  };
  return getPaymentMetricsData(payload);
};
// change index and mode before live
export const getMethodLevelCRData = ({ lte, gte, breakdown = 'daily' }) => {
  const payload = {
    aggregations: {
      checkout_method_level_overall_cr: {
        details: {
          index: 'cx_high_level_funnel',
          group_by: [`histogram_${breakdown}`, 'status', 'last_selected_method'],
          mode: 'live',
        },
        agg_type: 'count',
      },
    },
    filters: {
      default: [
        {
          created_at: {
            gte,
            lte,
          },
          render_checkout_open_event: 'true',
          checkout_library: ['hosted', 'checkoutjs'],
        },
      ],
    },
  };
  return getPaymentMetricsData(payload);
};

export const getIndustryOverallCRData = ({
  lte,
  gte,
  breakdown = 'daily',
  category,
}: {
  lte: number;
  gte: number;
  breakdown?: string;
  category: string;
}) => {
  const payload = {
    aggregations: {
      checkout_industry_level_overall_cr: {
        filter_key: 'checkout_industry_level_overall_cr',
        details: {
          index: 'cx_high_level_funnel',
          group_by: [`histogram_${breakdown}`, 'status'],
          mode: 'live',
        },
        agg_type: 'count',
      },
    },
    filters: {
      checkout_industry_level_overall_cr: [
        {
          merchant_category: category,
          created_at: {
            gte,
            lte,
          },
          render_checkout_open_event: 'true',
          checkout_library: ['hosted', 'checkoutjs'],
        },
      ],
    },
  };
  return getPaymentMetricsData(payload);
};
