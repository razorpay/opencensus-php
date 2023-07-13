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
          group_by: [`histogram_${breakdown}`, 'behav_submit_event', 'render_checkout_open_event'],
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
      checkout_method_level_cr: {
        details: {
          index: 'cx_high_level_funnel',
          group_by: [
            `histogram_${breakdown}`,
            'behav_submit_event',
            'render_checkout_open_event',
            'last_selected_method',
          ],
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
        },
      ],
    },
  };
  return getPaymentMetricsData(payload);
};
