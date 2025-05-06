const fullResponse = {
  id: '34',
  type: 'home_payment_insight',
  title: 'Payment insights',
  actions: [],
  inputs: [
    {
      type: 'select',
      values: ['today', 'last_7_days', 'last_30_days'],
      default_value: 'last_7_days',
      name: 'date',
    },
  ],
  components: [
    {
      id: '341',
      type: 'home_payment_insight_item',
      title: 'Payments collected',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          insight: {
            payment: {
              data_summary: {
                last_updated: '1740407859',
                input_time: 'last_7_days',
                current_data: 8402973382,
                previous_data: 8402973382,
                percentage_change: 900,
              },
              online_international_amount: '4201486691',
              international_payment_enabled: false,
              online_domestic_amount: '4201486691',
              payment_enabled: true,
              payment_locked: false,
              offline_payment_enabled: false,
              ray_insight: {
                data_summary: {
                  current_data: 800,
                  previous_data: 850,
                },
                current_top_payment_method: 'netbanking',
                previous_top_payment_method: 'cod',
              },
            },
          },
        },
      },
      alias: 'home_payment_collected_insight',
      analytics: null,
      styles: null,
    },
    {
      id: '342',
      type: 'home_payment_insight_item',
      title: 'Success Rate',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          insight: {
            success_rate: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: 92,
                previous_data: 88,
                percentage_change: 9,
              },
              ray_insight: {
                data_summary: {
                  last_updated: '1738061936',
                  input_time: 'last_7_days',
                  current_data: 32,
                  percentage_change: 40,
                },
                payment_failure_error_code: 'ERR001',
                payment_failure_error_description: 'Transaction timeout',
              },
            },
          },
        },
      },
      alias: 'home_success_rate_insight',
      analytics: null,
      styles: null,
    },
    {
      id: '343',
      type: 'home_payment_insight_item',
      title: 'Refunds',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          insight: {
            refund: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: 510000000000,
                previous_data: 520000000000,
                percentage_change: -9,
              },
              ray_insight: {
                data_summary: {
                  last_updated: '1738061936',
                  input_time: 'last_7_days',
                  percentage_change: -9,
                },
              },
            },
          },
        },
      },
      alias: 'home_refund_insight',
      analytics: null,
      styles: null,
    },
  ],
  data: {
    one_home_data: {
      insight: {
        insight_summary: {
          data_summary: {
            last_updated: '1740407859',
            input_time: 'last_7_days',
          },
          payment_enabled: true,
          payment_locked: false,
        },
      },
    },
  },
  alias: 'home_payment_insight',
  analytics: null,
  styles: null,
};

const nonPGResponse = {
  ...fullResponse,
  data: {
    one_home_data: {
      insight: {
        insight_summary: {
          data_summary: {
            last_updated: '1740407859',
            input_time: 'last_7_days',
          },
          payment_enabled: false,
          payment_locked: false,
        },
      },
    },
  },
};

const lockedResponse = {
  ...fullResponse,
  data: {
    one_home_data: {
      insight: {
        insight_summary: {
          data_summary: {
            last_updated: '1740407859',
            input_time: 'last_7_days',
          },
          payment_enabled: true,
          payment_locked: true,
        },
      },
    },
  },
};

const emptyResponse = JSON.parse(JSON.stringify(fullResponse));
emptyResponse.components[0].data.one_home_data.insight.payment.data_summary.current_data = null;

export { fullResponse, nonPGResponse, lockedResponse, emptyResponse };
