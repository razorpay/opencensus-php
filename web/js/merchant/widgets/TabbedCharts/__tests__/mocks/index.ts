import { IconPositionEnum } from 'merchant/widgets/TabbedCharts/types';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';

export const TABBED_CHARTS_MOCKED_RESPONSE = {
  queryKey: ['tabbed_chart_5'],
  id: '5',
  type: 'tabbed_chart',
  title: 'Payments Overview',
  background_img: '',
  analytics: {
    enabled: true,
  },
  inputs: [
    {
      type: 'select' as const,
      values: ['today', 'last_7_days', 'last_30_days'] as DateRangeValues[],
      default_value: 'last_7_days' as const,
    },
  ],
  components: [
    {
      id: '6',
      title: 'Collected Amount',
      type: 'tab_item',
      analytics: {
        enabled: true,
      },
      tooltip_text: 'This is the total amount collected from your customers.',
      data: {
        value: 3900032,
        value_type: 'amount',
        currency: 'INR',
        change: '-14',
        change_type: 'percentage' as const,
        sub_text: '₹7,180 above than usual',
        chart_data: {
          type: 'line',
          labels: ['This Week', 'Last Week'],
          schema: {
            x: {
              type: 'timestamp',
              unit: '',
            },
            y: {
              type: 'number',
              unit: '',
            },
          },
          data: [
            {
              label: 'This Week',
              points: [
                {
                  x: '1697599800000',
                  y: '5000',
                },
                {
                  x: '1697686200000',
                  y: '7000',
                },
                {
                  x: '1697772600000',
                  y: '4000',
                },
                {
                  x: '1697859000000',
                  y: '2500',
                },
                {
                  x: '1697945400000',
                  y: '3900',
                },
                {
                  x: '1698031800000',
                  y: '5700',
                },
                {
                  x: '1698118200000',
                  y: '7900',
                },
              ],
            },
            {
              label: 'Last Week',
              points: [
                {
                  x: '1697599800000',
                  y: '2000',
                },
                {
                  x: '1697686200000',
                  y: '3500',
                },
                {
                  x: '1697772600000',
                  y: '4500',
                },
                {
                  x: '1697859000000',
                  y: '4000',
                },
                {
                  x: '1697945400000',
                  y: '2900',
                },
                {
                  x: '1698031800000',
                  y: '3700',
                },
                {
                  x: '1698118200000',
                  y: '4500',
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'https://razorpay.com/payments',
        type: 'link',
        icon: 'arrow_right',
        icon_position: IconPositionEnum.RIGHT,
      },
    },
    {
      id: '7',
      title: 'Refunds',
      handler_id: 'refund_overview',
      type: 'tab_item',
      analytics: {
        enabled: true,
      },
      tooltip_text: 'This is the total amount refunded to your customers.',
      data: {
        value: 3900032,
        value_type: 'amount',
        currency: 'INR',
        change: '-14',
        change_type: 'percentage' as const,
        sub_text: '₹580 below than usual',
        chart_data: {
          type: 'line',
          labels: ['This Week', 'Last Week'],
          schema: {
            x: {
              type: 'timestamp',
              unit: '',
            },
            y: {
              type: 'number',
              unit: '',
            },
          },
          data: [
            {
              label: 'This Week',
              points: [
                {
                  x: '1694995200',
                  y: '4000',
                },
                {
                  x: '1695081600',
                  y: '5500',
                },
                {
                  x: '1695168000',
                  y: '5000',
                },
                {
                  x: '1695254400',
                  y: '4500',
                },
                {
                  x: '1695340800',
                  y: '4900',
                },
                {
                  x: '1695427200',
                  y: '5700',
                },
                {
                  x: '1695513600',
                  y: '6900',
                },
              ],
            },
            {
              label: 'Last Week',
              points: [
                {
                  x: '1694995200',
                  y: '2000',
                },
                {
                  x: '1695081600',
                  y: '3500',
                },
                {
                  x: '1695168000',
                  y: '4500',
                },
                {
                  x: '1695254400',
                  y: '4000',
                },
                {
                  x: '1695340800',
                  y: '2900',
                },
                {
                  x: '1695427200',
                  y: '3700',
                },
                {
                  x: '1695513600',
                  y: '4500',
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'https://razorpay.com/refunds',
        type: 'link',
        icon: 'arrow_right',
        icon_position: IconPositionEnum.RIGHT,
      },
    },
    {
      id: '8',
      title: 'Disputes',
      type: 'tab_item',
      analytics: {
        enabled: true,
      },
      tooltip_text: 'This is the total amount disputed by your customers.',
      handler_id: 'disputes_overview',
      data: {
        value: 3900032,
        value_type: 'amount',
        currency: 'INR',
        change: '-14',
        change_type: 'percentage' as const,
        sub_text: '₹580 below than usual',
        chart_data: {
          type: 'line',
          labels: ['This Week', 'Last Week'],
          schema: {
            x: {
              type: 'timestamp',
              unit: '',
            },
            y: {
              type: 'number',
              unit: '',
            },
          },
          data: [
            {
              label: 'This Week',
              points: [
                {
                  x: '1694995200',
                  y: '4000',
                },
                {
                  x: '1695081600',
                  y: '5500',
                },
                {
                  x: '1695168000',
                  y: '5000',
                },
                {
                  x: '1695254400',
                  y: '4500',
                },
                {
                  x: '1695340800',
                  y: '4900',
                },
                {
                  x: '1695427200',
                  y: '5700',
                },
                {
                  x: '1695513600',
                  y: '6900',
                },
              ],
            },
            {
              label: 'Last Week',
              points: [
                {
                  x: '1694995200',
                  y: '2000',
                },
                {
                  x: '1695081600',
                  y: '3500',
                },
                {
                  x: '1695168000',
                  y: '4500',
                },
                {
                  x: '1695254400',
                  y: '4000',
                },
                {
                  x: '1695340800',
                  y: '2900',
                },
                {
                  x: '1695427200',
                  y: '3700',
                },
                {
                  x: '1695513600',
                  y: '4500',
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'https://razorpay.com/disputes',
        type: 'link',
        icon: 'arrow_right',
        icon_position: IconPositionEnum.RIGHT,
      },
    },
  ],
};
