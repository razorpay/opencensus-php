import { DateRangeValues } from 'merchant/widgets/common/Select/types';

export const HERO_CARD_MOCK_RESPONSE = {
  id: '11',
  type: 'hero_card',
  title: 'Hero card',
  background_img: '',
  analytics: {
    enabled: true,
  },
  components: [],
  data: {
    hero_card_data: {
      is_transacted: true,
      is_settlement: true,
      settlement_schedule: 'FLBYKXvweeGWrV',
      settlement: {
        current_balance: '60000',
        current_balance_currency: 'INR',
        settlement_currency: 'INR',
        today: {
          total_amount: '1002',
          total_count: '9',
          delayed_total_amount: '800',
          delayed_count: '5',
          failed_total_amount: '0',
          failed_count: '0',
          created_total_amount: '0',
          created_count: '0',
          processed_total_amount: '202',
          processed_count: '4',
          title_key: 'SETL_TODAY_MULTIPLE',
        },
        previous: {
          total_amount: '0',
          total_count: '1',
          created_at: '1659586909',
        },
        upcoming_settlement: {
          title_key: 'UPCOMING_SETL_SKIPPED_NO_NEXT_SETTLEMENT',
          settlement_amount: '0',
          next_settlement_time: '0',
        },
      },
    },
  },
};

export const KEY_UPDATES_MOCK_RESPONSE = {
  id: '1',
  type: 'carousel_cards_with_count',
  title: 'Key Updates',
  background_img: 'https://dashboard.dev.razorpay.in/img/product-recommendations.png',
  actions: [],
  inputs: [],
  analytics: {
    enabled: true,
  },
  components: [
    {
      analytics: {
        enabled: true,
      },
      id: '2',
      type: 'carousel_item',
      title: 'Action required on your International Payment Request',
      description: 'We need some additional details to enable International payments',
      variant: 'failure',
      action: {
        title: 'Review',
        action: 'https://razorpay.com',
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right' as const,
      },
      data: {
        value: '1',
        value_type: 'number',
      },
    },
    {
      id: '3',
      type: 'carousel_item',
      title: 'Your request to update website is delayed',
      description: 'We are working to process the request at the earliest',
      variant: 'open',
      action: {
        title: 'View',
        action: 'https://razorpay.com',
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right' as const,
      },
      analytics: {
        enabled: true,
      },
    },
    {
      id: '4',
      type: 'carousel_item',
      title: 'Website updated',
      description: 'Your website has been updated successfully',
      variant: 'info',
      actions: [],
      inputs: [],
      components: [],
      data: {
        value: '#123213123',
        value_type: 'string',
      },
      analytics: {
        enabled: true,
      },
    },
    {
      id: '5',
      type: 'carousel_item',
      title: 'Refunds Failure',
      description: 'Two refunds have failed due to incorrect bank details',
      variant: 'needs_clarification',
      action: {
        title: 'View',
        action: 'https://razorpay.com',
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right' as const,
      },
      analytics: {
        enabled: true,
      },
    },
    {
      id: '6',
      type: 'carousel_item',
      title: 'Settlements Failure',
      description: 'Could not process settlements for the last 3 days',
      variant: 'closed',
      action: {
        title: 'View',
        action: 'https://razorpay.com',
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right' as const,
      },
      analytics: {
        enabled: true,
      },
    },
  ],
  data: {
    value: '6',
    value_type: 'number',
  },
};

export const PAYMENTS_OVERVIEW_MOCK_RESPONSE = {
  id: '13',
  type: 'tabbed_chart',
  title: 'Payments Overview',
  handler_id: 'payment_overview',
  background_img: '',
  analytics: {
    enabled: true,
  },
  inputs: [
    {
      type: 'select',
      values: ['today', 'this_week', 'this_month'] as DateRangeValues[],
      default_value: 'this_week',
    },
  ],
  components: [
    {
      id: '131',
      title: 'Collected Amount',
      handler_id: 'payment_collected_overview',
      type: 'tab_item',
      analytics: {
        enabled: true,
      },
      action: {
        title: 'View Details',
        action: 'payments',
        action_params: {},
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right',
      },
      data: {
        value: '3900032',
        value_type: 'amount',
        currency: 'INR',
        change: -14,
        sub_text: '{{ .change_amount }} above/below than usual',
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
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
            {
              label: 'Last Week',
              points: [
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
          ],
        },
      },
    },
    {
      id: '132',
      title: 'Refunds',
      handler_id: 'refund_overview',
      type: 'tab_item',
      analytics: {
        enabled: true,
      },
      data: {
        value: '3900032',
        value_type: 'amount',
        currency: 'INR',
        change: -14,
        sub_text: '{{ .change_amount }} above/below than usual',
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
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
            {
              label: 'Last Week',
              points: [
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'refunds',
        action_params: {},
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right',
      },
    },
    {
      id: '133',
      title: 'Orders',
      handler_id: 'order_overview',
      type: 'tab_item',
      analytics: {
        enabled: true,
      },
      data: {
        value: '3900032',
        value_type: 'amount',
        currency: 'INR',
        change: -14,
        sub_text: '{{ .change_amount }} above/below than usual',
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
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
            {
              label: 'Last Week',
              points: [
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'orders',
        action_params: {},
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right',
      },
    },
  ],
};

export const INSIGHTS_CHART_MOCK_RESPONSE = {
  id: '14',
  type: 'insight_charts',
  title: 'Top Insights',
  handler_id: 'top_insights',
  background_img: '',
  analytics: {
    enabled: true,
  },
  inputs: [
    {
      type: 'select' as const,
      values: ['today', 'last_7_days', 'last_30_days'] as DateRangeValues[],
      default_value: 'last_30_days' as const,
    },
  ],
  components: [
    {
      id: '141',
      title: 'Payment Success Rate',
      handler_id: 'payment_success_rate_insight',
      tooltip_text: 'TODO: To be shared from product team',
      type: 'insight_item',
      error: {
        code: 'ERROR_CODE',
        message: 'Something is wrong in subwidget',
      },
      analytics: {
        enabled: true,
      },
      data: {
        value: '90',
        value_type: 'percentage',
        currency: '',
        change: 14,
        sub_text: '14% greater than last week',
        change_behavior_inverted: false,
        chart_data: {
          type: 'line',
          labels: ['This Week'],
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
                  x: 1707264000000,
                  y: 850000,
                },
                {
                  x: 1707350400000,
                  y: 750000,
                },
                {
                  x: 1707436800000,
                  y: 800000,
                },
                {
                  x: 1707523200000,
                  y: 650000,
                },
                {
                  x: 1707609600000,
                  y: 750000,
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'payment_success_rate',
        action_params: {},
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right',
      },
    },
    {
      id: '142',
      title: 'Payment Failures',
      handler_id: 'payment_failure_insight',
      tooltip_text: 'Failures of payments',
      type: 'insight_item',
      analytics: {
        enabled: true,
      },
      data: {
        value: '58',
        value_type: 'number',
        currency: '',
        change: -7,
        sub_text: '7 lesser than last week',
        change_behavior_inverted: false,
        chart_data: {
          type: 'line',
          labels: ['This Week'],
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
                  x: 1707264000000,
                  y: 8500,
                },
                {
                  x: 1707350400000,
                  y: 7500,
                },
                {
                  x: 1707436800000,
                  y: 8000,
                },
                {
                  x: 1707523200000,
                  y: 6500,
                },
                {
                  x: 1707609600000,
                  y: 7500,
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'Review',
        action: 'payment_failed',
        action_params: {
          from: '1706207400',
          to: '1706898599',
          status: 'failed',
        },
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right',
      },
    },
    {
      id: '143',
      title: 'Refund',
      handler_id: 'refund_insight',
      tooltip_text: 'Refund Count',
      type: 'insight_item',
      analytics: {
        enabled: true,
      },
      data: {
        value: 350012,
        value_type: 'currency',
        currency: 'INR',
        change: -50,
        change_behavior_inverted: true,
        sub_text: '50 lesser than last week',
        chart_data: {
          type: 'line',
          labels: ['This Week'],
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
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
            {
              label: 'Last Week',
              points: [
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
                {
                  x: '18888888889',
                  y: 7000,
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'refunds',
        action_params: {},
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right',
      },
    },
    {
      id: '144',
      title: 'Payment Method Split',
      handler_id: 'payment_method_split_insight',
      tooltip_text: 'payment method split view',
      type: 'doughnut_chart',
      analytics: {
        enabled: true,
      },
      data: {
        chart_data: {
          type: 'doughnut',
          labels: ['This Week'],
          schema: {
            x: {
              type: 'string',
              unit: '',
            },
            y: {
              type: 'amount',
              unit: 'INR',
            },
          },
          data: [
            {
              label: 'This Week',
              points: [
                {
                  x: 'Credit Cards',
                  y: 4000000,
                },
                {
                  x: 'UPI',
                  y: 2000000,
                },
                {
                  x: 'Netbanking',
                  y: 1000000,
                },
                {
                  x: 'Others',
                  y: 1500000,
                },
                {
                  x: '18888888889',
                  y: 1200000,
                },
              ],
            },
          ],
        },
      },
      action: {
        title: 'View Details',
        action: 'payment_method_split',
        action_params: {},
        type: 'link',
        icon: 'arrow_right',
        icon_position: 'right',
      },
    },
  ],
};

export const PRODUCT_RECOMMENDER_MOCK_RESPONSE = {
  analytics: { enabled: true },
  id: '15',
  type: 'carousal_card',
  title: 'Products for you',
  background_img: 'https://dashboard.dev.razorpay.in/img/product-recommendations.png',
  components: [
    {
      id: '16',
      type: 'carousel_product_card',
      title: 'Payment Gateway',
      background_img: 'https://media.razorpay.com/file/platform/home-revamp/product-card-pg-2x.png',
      description: 'Offer a seamless payment experience on your website or app',
      actions: [
        {
          title: 'Know more',
          action: 'https://razorpay.com/payment-gateway',
          type: 'link',
          icon: 'arrow_right',
          icon_position: 'right' as const,
        },
      ],
    },
    {
      id: '17',
      type: 'carousel_product_card',
      title: 'Payment Gateway',
      background_img: 'https://media.razorpay.com/file/platform/home-revamp/product-card-pg-2x.png',
      description: 'Offer a seamless payment experience on your website or app',
      actions: [
        {
          title: 'Know more',
          action: 'support_ticket',
          type: 'link',
          icon: 'arrow_right',
          icon_position: 'right' as const,
        },
      ],
    },
  ],
};

export const HOMEPAGE_RTUX_DATA_MOCK = {
  components: [
    {
      id: '1',
      alias: 'home_page',
      analytics: {
        enabled: true,
      },
      components: [
        HERO_CARD_MOCK_RESPONSE,
        KEY_UPDATES_MOCK_RESPONSE,
        PAYMENTS_OVERVIEW_MOCK_RESPONSE,
        INSIGHTS_CHART_MOCK_RESPONSE,
        PRODUCT_RECOMMENDER_MOCK_RESPONSE,
      ],
    },
  ],
};

export const HOMEPAGE_RTUX_LAYOUT_MOCK = {
  components: [
    {
      id: '1',
      alias: 'home_page',
      analytics: {
        enabled: true,
      },
      components: [
        {
          id: '11',
          type: 'hero_card',
          title: 'Hero card',
          background_img: '',
          analytics: {
            enabled: true,
          },
          components: [],
        },
        {
          id: '12',
          type: 'carousel_cards_with_count',
          title: 'Key Updates',
          background_img: '',
          analytics: {
            enabled: true,
          },
          components: [],
        },
        {
          id: '13',
          type: 'tabbed_chart',
          title: 'Payments Overview',
          background_img: '',
          analytics: {
            enabled: true,
          },
          inputs: [
            {
              type: 'select',
              values: ['today', 'this_week', 'this_month'] as DateRangeValues[],
              default_value: 'this_week',
            },
          ],
          components: [
            {
              id: '131',
              title: 'Collected Amount',
              type: 'tab_item',
              analytics: {
                enabled: true,
              },
              action: {
                title: 'View Details',
                action: 'payments',
                action_params: {},
                type: 'link',
                icon: 'arrow_right',
                icon_position: 'right',
              },
            },
            {
              id: '132',
              title: 'Refunds',
              type: 'tab_item',
              analytics: {
                enabled: true,
              },
              action: {
                title: 'View Details',
                action: 'refunds',
                action_params: {},
                type: 'link',
                icon: 'arrow_right',
                icon_position: 'right',
              },
            },
            {
              id: '133',
              title: 'Orders',
              type: 'tab_item',
              analytics: {
                enabled: true,
              },
              action: {
                title: 'View Details',
                action: 'orders',
                action_params: {},
                type: 'link',
                icon: 'arrow_right',
                icon_position: 'right',
              },
            },
          ],
        },
        {
          id: '14',
          type: 'insight_charts',
          title: 'Top Insights',
          background_img: '',
          analytics: {
            enabled: true,
          },
          inputs: [
            {
              type: 'select',
              values: ['today', 'this_week', 'this_month'] as DateRangeValues[],
              default_value: 'this_week',
            },
          ],
          components: [
            {
              id: '141',
              title: 'Payment Success Rate',
              tooltip_text: 'TODO: To be shared from product team',
              type: 'insight_item',
              analytics: {
                enabled: true,
              },
              action: {
                title: 'View Details',
                action: 'payment_success_rate',
                action_params: {},
                type: 'link',
                icon: 'arrow_right',
                icon_position: 'right',
              },
            },
            {
              id: '142',
              title: 'Payment Failures',
              tooltip_text: 'Failures of payments',
              type: 'insight_item',
              analytics: {
                enabled: true,
              },
              action: {
                title: 'Review',
                action: 'payment_failed',
                action_params: {},
                type: 'link',
                icon: 'arrow_right',
                icon_position: 'right',
              },
            },
            {
              id: '143',
              title: 'Refund',
              handler_id: 'refund_insight',
              tooltip_text: 'Refund Count',
              type: 'insight_item',
              analytics: {
                enabled: true,
              },
              action: {
                title: 'View Details',
                action: 'refunds',
                action_params: {},
                type: 'link',
                icon: 'arrow_right',
                icon_position: 'right',
              },
            },
            {
              id: '144',
              title: 'Payment Method Split',
              handler_id: 'payment_method_split_insight',
              tooltip_text: 'payment method split view',
              type: 'doughnut_chart',
              analytics: {
                enabled: true,
              },
              action: {
                title: 'View Details',
                action: 'payment_method_split',
                action_params: {},
                type: 'link',
                icon: 'arrow_right',
                icon_position: 'right',
              },
            },
          ],
        },
        {
          id: '15',
          type: 'carousal_card',
          title: 'Product for you',
          handler_id: 'product_recommendation',
          background_img: 'productRecommendationBackground',
          analytics: {
            enabled: true,
          },
          components: [
            {
              id: '151',
              title: 'API & Bulk Payouts',
              description: 'Make multiple payouts with a single click from your dashboard.',
              background_img: '',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
              actions: [
                {
                  title: 'sign up',
                  action: 'https://razorpay.com',
                  type: 'primary',
                  icon: 'arrow_right',
                  icon_position: 'right',
                },
                {
                  title: 'Know more',
                  action: 'https://razorpay.com/asdasdad',
                  action_params: {},
                  type: 'link',
                },
              ],
            },
            {
              id: '152',
              title: 'Vendor Payments',
              background_img: '',
              description: 'Streamline vendor payouts: Invoices, TDS, payment, accounting',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
            {
              id: '153',
              title: 'Payout Links',
              background_img: '',
              description: 'Share payout links for instant payments, no bank details needed',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
            {
              id: '154',
              title: 'Tax Payments',
              background_img: '',
              description: 'Share payout links for instant payments, no bank details needed',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
            {
              id: '155',
              title: 'Cross Border Payments',
              description: 'Receive payments from 200+ countries and go global',
              background_img: '',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
              actions: [
                {
                  title: 'sign up',
                  action: 'https://razorpay.com',
                  type: 'primary',
                  icon: 'arrow_right',
                  icon_position: 'right',
                },
                {
                  title: 'Know more',
                  action: 'https://razorpay.com/asdasdad',
                  action_params: {},
                  type: 'link',
                },
              ],
            },
            {
              id: '156',
              title: 'Instant Settlements',
              background_img: '',
              description: 'Settle customer payments into your bank account within seconds',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
            {
              id: '157',
              title: 'Payment Links',
              background_img: '',
              description:
                'Create and share links over email, text and social to accept payments instantly.',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
            {
              id: '158',
              title: 'Razorpay POS',
              background_img: '',
              description: "Accept seamless in-store payments with India's best POS solution.",
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
            {
              id: '159',
              title: 'Payroll',
              background_img: '',
              description: 'Master payroll and compliance with RazorpayX Payroll',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
            {
              id: '1510',
              title: 'Current Account',
              background_img: '',
              description: 'Automate all your banking needs with India’s best current account',
              type: 'carousel_product_card',
              analytics: {
                enabled: true,
              },
            },
          ],
        },
      ],
    },
  ],
};
