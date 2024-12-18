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

export const SOH_MOCK = {
  sub_title:
    'Your settlements are on-hold because your Bank IFSC code is invalid. Please update your Bank details to resume settlements.',
  cta_text: 'Update bank details',
  source: 'SOH',
  cta_link: 'https://dashboard.razorpay.com/app/profile/update_bank_account',
  status: true,
};
export const SOH_MOCK_2 = {
  sub_title:
    'Contact Support for resuming your settlements. We are here to help you with any queries you have.',
  cta_text: 'Contact Support',
  source: 'SOH',
  cta_link: 'www.razorpay.com',
  status: true,
};

export const FOH_MOCK = {
  sub_title: 'Contact Support team to resume your settlements.',
  cta_text: 'Contact Support',
  cta_link: 'www.razorpay.com',
  source: 'FOH',
  status: true,
};

export const BLOCK_MOCK = {
  sub_title: 'Please reach out to support team',
  cta_text: 'Contact Support',
  cta_link: 'www.razorpay.com',
  source: 'Block',
  status: true,
};

export const DEFAULT_MOCK = {
  sub_title:
    "Please reach out to our support team, and we'll assist you in resolving this issue and getting your settlements back on track.",
  cta_text: 'Contact Support',
  source: '',
  status: true,
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

export const CAMPAIGN_HERO_MOCK_RESPONSE = {
  id: '18',
  type: 'campaignhq_banners',
  title: 'Campaigns',
  background_img: '',
  actions: [],
  inputs: [],
  components: [],
  data: {
    campaign_hero_card_data: {
      channelId: 'PVMmiWWPxbmu35',
      assetData: [
        {
          trackingData: {
            campaign: 'UCS Test Campaign 1',
            subCampaign: 'UCS Test Campaign 1.1',
            campaignId: 'PVOitQWBLEAUD7',
            subCampaignId: 'PVOj5geksyDCDq',
            tags: {
              campaign_source: 'CampaignHQ',
              end_at: 1735583400,
              start_at: 1733817186,
            },
          },
          templates: [
            {
              id: 'PVOl2uC7lTPy9W',
              asset: 'JSON_SCHEMA',
              name: 'template-default-Variant A',
              data: {
                id: 'UCS_Test_Campaign_#1_DEC1024_JSON_SCHEMA_PVOl2uC7lTPy9W',
                rtux_ucs_campaigns: {
                  alt_text: 'This is alt text for first image.',
                  cta_link: 'https://razorpay.com/',
                  image: {
                    lg: 'https://betacdn.np.razorpay.in/growth/PVOj5geksyDCDq/large.png',
                    md: 'https://betacdn.np.razorpay.in/growth/PVOj5geksyDCDq/medium.png',
                    sm: 'https://betacdn.np.razorpay.in/growth/PVOj5geksyDCDq/small.png',
                  },
                },
              },
              created_at: '2024-12-10T07:54:57Z',
              updated_at: '2024-12-10T07:54:57Z',
              dynamic_asset_name: 'RTUX_UCS_CAMPAIGNS',
            },
          ],
        },
        {
          trackingData: {
            campaign: 'UCS Test Campaign 2',
            subCampaign: 'UCS Test Campaign 2.1',
            campaignId: 'P3MRoSvKlUAdhU',
            subCampaignId: 'PVPS8ASYSlzffM',
            tags: {
              campaign_source: 'CampaignHQ',
              end_at: 1734114600,
              start_at: 1733819745,
            },
          },
          templates: [
            {
              id: 'PVPTCTBe09eW0D',
              asset: 'JSON_SCHEMA',
              name: 'template-default-Variant A',
              data: {
                id: 'UCS_Test_Campaign_#2DEC1024_JSON_SCHEMA_PVPTCTBe09eW0D',
                rtux_ucs_campaigns: {
                  alt_text: 'This is alt text for second image.',
                  cta_link: 'https://razorpay.com/',
                  image: {
                    lg: 'https://betacdn.np.razorpay.in/growth/PVPS8ASYSlzffM/large.png',
                    md: 'https://betacdn.np.razorpay.in/growth/PVPS8ASYSlzffM/medium.png',
                    sm: 'https://betacdn.np.razorpay.in/growth/PVPS8ASYSlzffM/small.png',
                  },
                },
              },
              created_at: '2024-12-10T08:36:45Z',
              updated_at: '2024-12-10T08:36:45Z',
              dynamic_asset_name: 'RTUX_UCS_CAMPAIGNS',
            },
          ],
        },
      ],
    },
  },
  analytics: {
    enabled: true,
  },
  styles: null,
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

export const D2C_WIDGET_MOCK_RESPONSE = {
  id: '14',
  type: 'd2c_widget',
  title: 'Optimise your funnel performance',
  description: "with Razorpay's product suite",
  actions: [],
  inputs: [],
  components: [
    {
      id: '141',
      type: 'd2c_acquire_customers',
      title: 'Acquire Customers',
      description: '1 suggestion',
      actions: [],
      inputs: [],
      components: [
        {
          id: '1411',
          type: 'd2c_product_pitching',
          title: '',
          background_img:
            'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/negative_background.svg',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14111',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_negative_decrease',
                  labels: ['last month', 'Current'],
                  schema: null,
                  data: [
                    {
                      label: 'Acquisition Rate',
                      points: [
                        {
                          x: '-30%',
                          y: '0',
                        },
                      ],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14112',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: [],
                  text_content: {
                    display: '30% drop',
                    display_color: '#D92D20',
                    above_display_heading: 'Notice a',
                    below_display_heading: 'in acquisition last month',
                    caption: '',
                    caption_list: [],
                  },
                  brand_images: [],
                  text_body: "Fix it by boosting your brand's visibility with Razorpay",
                  actions: {
                    title: 'See how to improve',
                    action: '1412',
                    type: 'component_switch',
                    icon: '',
                    icon_position: '',
                    action_params: {},
                  },
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
        {
          id: '1412',
          type: 'd2c_solution_pitching',
          title: '',
          background_img: 'TODO: Url for solution pitching slide',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14121',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_positive_increase',
                  labels: ['Current', 'Expected'],
                  schema: null,
                  data: [
                    {
                      label: 'Acquisition Rate',
                      points: [
                        {
                          x: '+30%',
                          y: '0',
                        },
                      ],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14122',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: ['Recommended for you', 'Easy to use'],
                  text_content: {
                    display: 'Upto 30% growth',
                    display_color: '#008743',
                    above_display_heading: '',
                    below_display_heading:
                      'in acquisition by creating visibility for your \n brand',
                    caption_list: [
                      "Generate and distribute your brand's Gift Cards at ease",
                      'Acquire new customers at lower cost',
                    ],
                  },
                  brand_images: [
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/berrylush.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/blue_tokai.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/borosil.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/bose.png',
                  ],
                  text_body: 'Be a part of 20,00,000+ D2C brands driving sales',
                  actions: {
                    title: 'Get in touch for Gift Cards',
                    action: 'https://razorpay.typeform.com/to/WS1dWIQ3',
                    type: 'url_redirection',
                    icon: '',
                    icon_position: '',
                    action_params: {},
                  },
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
      ],
      data: {
        value: "Customer's Acquisition",
        sub_text: 'Better',
        change_type: 'selected',
        one_nav_data: [],
        cross_sell_widget_data: {
          badges: [],
          brand_images: [],
          cross_sell_widget_product_data: {
            product_name: 'gift_card',
            product_pitching_type: 'pre_post_trend',
          },
        },
      },
      analytics: {
        enabled: true,
      },
    },
    {
      id: '142',
      type: 'improve_customer_intention_to_purchase',
      title: "Improve Customers's Intention To Purchase",
      description: '1 suggestion',
      actions: [],
      inputs: [],
      components: [
        {
          id: '1423',
          type: 'd2c_cooling_period',
          title: '',
          background_img:
            'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/positive_background.svg',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14231',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_positive_increase',
                  labels: ['Past', 'Current'],
                  schema: null,
                  data: [
                    {
                      label: 'Average Order Value',
                      points: [],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14232',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: [],
                  text_content: {
                    display: 'optimized',
                    display_color: '#008743',
                    above_display_heading: 'Great News! \n This part of the funnel is already',
                    below_display_heading: '',
                    caption: "with Razorpay's ",
                    caption_list: [],
                  },
                  brand_images: [],
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
      ],
      data: {
        value: "Customer's Intention To Purchase",
        sub_text: 'Improved',
        change_type: 'not_selected',
        one_nav_data: [],
        cross_sell_widget_data: {
          badges: [],
          brand_images: [],
          cross_sell_widget_product_data: {
            product_name: 'no_product',
            product_pitching_type: 'no_type',
          },
        },
      },
      analytics: {
        enabled: true,
      },
    },
    {
      id: '143',
      type: 'easy_checkout_and_payment',
      title: 'Easy Checkout And Payment',
      description: '1 suggestion',
      actions: [],
      inputs: [],
      components: [
        {
          id: '1431',
          type: 'd2c_product_pitching',
          title: '',
          background_img:
            'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/negative_background.svg',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14311',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_negative_decrease',
                  labels: ['last month', 'Current'],
                  schema: null,
                  data: [
                    {
                      label: 'Conversion Rate',
                      points: [
                        {
                          x: '-50%',
                          y: '0',
                        },
                      ],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14312',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: [],
                  text_content: {
                    display: 'dropped by 50%',
                    display_color: '#D92D20',
                    above_display_heading: 'Your checkout conversion has',
                    below_display_heading: 'in last 30 days - Fix it today',
                    caption: '',
                    caption_list: [],
                  },
                  brand_images: [],
                  text_body: 'Enable a smooth, effortless with ease checkout \n with Razorpay',
                  actions: {
                    title: 'See how to improve',
                    action: '1432',
                    type: 'component_switch',
                    icon: '',
                    icon_position: '',
                    action_params: {},
                  },
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
        {
          id: '1432',
          type: 'd2c_solution_pitching',
          title: '',
          background_img:
            'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/positive_background.svg',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14321',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_positive_increase',
                  labels: ['Current', 'Expected'],
                  schema: null,
                  data: [
                    {
                      label: 'Conversion Rate',
                      points: [
                        {
                          x: '+30%',
                          y: '0',
                        },
                      ],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14322',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: ['Recommended for you', 'Easy to use'],
                  text_content: {
                    display: 'Upto 30% growth',
                    display_color: '#008743',
                    above_display_heading: '',
                    below_display_heading:
                      'in conversion by enabling a smoother checkout \n experience',
                    caption_list: [
                      'Pre-fill address and contact details',
                      'Reduce drop offs by auto applying eligible offers and coupons',
                    ],
                  },
                  brand_images: [
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/berrylush.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/blue_tokai.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/borosil.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/bose.png',
                  ],
                  text_body: 'Be a part of 20,00,000+ D2C brands driving sales',
                  actions: {
                    title: 'Check Magic Checkout',
                    action: 'https://razorpay.com/magic/',
                    type: 'url_redirection',
                    icon: '',
                    icon_position: '',
                    action_params: {},
                  },
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
      ],
      data: {
        value: 'Checkout and Payment Experience',
        sub_text: 'Easier',
        change_type: 'not_selected',
        one_nav_data: [],
        cross_sell_widget_data: {
          badges: [],
          brand_images: [],
          cross_sell_widget_product_data: {
            product_name: 'magic_checkout',
            product_pitching_type: 'pre_post_trend',
          },
        },
      },
      analytics: {
        enabled: true,
      },
    },
    {
      id: '144',
      type: 'optimise_return_orders',
      title: 'Optimise Return Orders',
      description: '1 suggestion',
      actions: [],
      inputs: [],
      components: [
        {
          id: '1443',
          type: 'd2c_cooling_period',
          title: '',
          background_img:
            'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/positive_background.svg',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14431',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_positive_increase',
                  labels: ['Past', 'Current'],
                  schema: null,
                  data: [
                    {
                      label: 'Return Orders',
                      points: [],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14432',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: [],
                  text_content: {
                    display: 'optimized',
                    display_color: '#008743',
                    above_display_heading: 'Great News! \n This part of the funnel is already',
                    below_display_heading: '',
                    caption: "with Razorpay's ",
                    caption_list: [],
                  },
                  brand_images: [],
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
      ],
      data: {
        value: 'Return Orders',
        sub_text: 'Optimised',
        change_type: 'not_selected',
        one_nav_data: [],
        cross_sell_widget_data: {
          badges: [],
          brand_images: [],
          cross_sell_widget_product_data: {
            product_name: 'no_product',
            product_pitching_type: 'no_type',
          },
        },
      },
      analytics: {
        enabled: true,
      },
    },
    {
      id: '145',
      type: 'boost_repeat_purchase',
      title: 'Boost Repeat Purchase',
      description: '1 suggestion',
      actions: [],
      inputs: [],
      components: [
        {
          id: '1451',
          type: 'd2c_product_pitching',
          title: '',
          background_img:
            'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/negative_background.svg',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14511',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_negative_decrease',
                  labels: ['last month', 'Current'],
                  schema: null,
                  data: [
                    {
                      label: 'Repeat Orders',
                      points: [
                        {
                          x: '-39%',
                          y: '0',
                        },
                      ],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14512',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: [],
                  text_content: {
                    display: 'dropped by 39%',
                    display_color: '#D92D20',
                    above_display_heading: 'Repeat users purchases',
                    below_display_heading: 'last month',
                    caption: '',
                    caption_list: [],
                  },
                  brand_images: [],
                  text_body:
                    'Increase customer lifetime value, boost brand loyalty \n with Razorpay',
                  actions: {
                    title: 'See how to improve',
                    action: '1452',
                    type: 'component_switch',
                    icon: '',
                    icon_position: '',
                    action_params: {},
                  },
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
        {
          id: '1452',
          type: 'd2c_solution_pitching',
          title: '',
          background_img:
            'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/positive_background.svg',
          actions: [],
          inputs: [],
          components: [
            {
              id: '14521',
              type: 'd2c_visuals',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                value: '',
                value_type: 'chart',
                chart_data: {
                  type: 'neutral_positive_increase',
                  labels: ['Current', 'Expected'],
                  schema: null,
                  data: [
                    {
                      label: 'Repeat Orders',
                      points: [
                        {
                          x: '+30%',
                          y: '0',
                        },
                      ],
                    },
                  ],
                },
                one_nav_data: [],
              },
              analytics: {
                enabled: true,
              },
            },
            {
              id: '14522',
              type: 'd2c_card_widget',
              title: '',
              actions: [],
              inputs: [],
              components: [],
              data: {
                one_nav_data: [],
                cross_sell_widget_data: {
                  badges: ['Recommended for you', 'Easy to use'],
                  text_content: {
                    display: 'Upto 30% growth',
                    display_color: '#008743',
                    above_display_heading: '',
                    below_display_heading:
                      'in repeat orders by building customer\n loyalty to your brand',
                    caption_list: [
                      'Enable customers to pay via Wallet in a single click',
                      'Customers using brand Wallet are 5x likely to stay retained',
                    ],
                  },
                  brand_images: [
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/berrylush.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/blue_tokai.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/borosil.png',
                    'https://cdn.razorpay.com/static/assets/merchant-dash/cross_sell_widget/logo/bose.png',
                  ],
                  text_body: 'Be a part of 20,00,000+ D2C brands driving sales',
                  actions: {
                    title: 'Get in touch for Wallet',
                    action: 'https://razorpay.typeform.com/to/lFcyo61y',
                    type: 'url_redirection',
                    icon: '',
                    icon_position: '',
                    action_params: {},
                  },
                },
              },
              analytics: {
                enabled: true,
              },
            },
          ],
          analytics: {
            enabled: true,
          },
        },
      ],
      data: {
        value: 'Repeat Purchases',
        sub_text: 'Boosted',
        change_type: 'not_selected',
        one_nav_data: [],
        cross_sell_widget_data: {
          badges: [],
          brand_images: [],
          cross_sell_widget_product_data: {
            product_name: 'wallet',
            product_pitching_type: 'pre_post_trend',
          },
        },
      },
      analytics: {
        enabled: true,
      },
    },
  ],
  analytics: {
    enabled: true,
  },
} as const;
