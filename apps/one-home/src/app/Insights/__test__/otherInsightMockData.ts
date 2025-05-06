const fullResponse = {
  id: '35',
  type: 'home_other_insights',
  title: 'Other Insights',
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
      id: '351',
      type: 'home_other_insight_item',
      title: 'Payouts',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          other_insight: {
            payout: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: 1738061936,
                previous_data: '1738061936',
                percentage_change: -32,
              },
              business_banking_enabled: true,
              locked: false,
              ray_insight: {
                data_summary: {
                  current_data: 1738061936,
                },
                business_name: 'Razorpay',
              },
            },
          },
        },
      },
      alias: 'home_payout_other_insight',
      analytics: null,
      styles: null,
    },
    {
      id: '352',
      type: 'home_other_insight_item',
      title: 'Employees and payroll',
      description: 'Run payroll with razorpay',
      actions: [
        {
          title: 'Get started',
          action: 'navigate',
          type: 'button',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/payroll',
          },
          properties: {
            variant: 'secondary',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'home_payroll_other_insight',
      analytics: null,
      styles: null,
    },
    {
      id: '353',
      type: 'home_other_insight_item',
      title: 'Customers',
      description: 'Acquire and retain customers',
      actions: [],
      inputs: [],
      components: [],
      alias: 'home_customer_other_insight',
      analytics: null,
      styles: null,
    },
  ],
  data: {
    one_home_data: {
      other_insight: {
        insight_summary: {
          data_summary: {
            last_updated: '1740407859',
            input_time: 'last_7_days',
          },
        },
      },
    },
  },
  alias: 'home_other_insight',
  analytics: null,
  styles: null,
};

const lockedResponse = JSON.parse(JSON.stringify(fullResponse));
lockedResponse.components[0].data.one_home_data.other_insight.payout.locked = true;

const emptyResponse = JSON.parse(JSON.stringify(fullResponse));
emptyResponse.components[0].data.one_home_data.other_insight.payout.data_summary.current_data =
  null;

const bankingDisabledResponse = JSON.parse(JSON.stringify(fullResponse));
bankingDisabledResponse.components[0].data.one_home_data.other_insight.payout.business_banking_enabled =
  false;

export { fullResponse, bankingDisabledResponse, lockedResponse, emptyResponse };
