export const criticalActionsMockData = {
  actions: [],
  alias: 'critical_actions',
  analytics: null,
  components: [
    {
      actions: [
        {
          action: 'navigate',
          action_params: {
            path: '/settings/payments',
          },
          icon: '',
          icon_position: '',
          properties: {
            variant: 'secondary',
          },
          title: 'Complete Request',
          type: 'button',
        },
      ],
      alias: 'international_critical_action_item',
      analytics: null,
      components: [],
      description: 'International Enablement Request Requires Additional Information',
      id: '312',
      inputs: [],
      styles: null,
      title: 'International Enablement Request',
      type: 'critical_action_item',
    },
    {
      actions: [
        {
          action: 'navigate',
          action_params: {
            path: '/settings/bank-account',
          },
          icon: '',
          icon_position: '',
          properties: {
            variant: 'secondary',
          },
          title: 'Update Bank Account',
          type: 'button',
        },
      ],
      alias: 'funds_critical_action_item',
      analytics: null,
      components: [],
      description: 'Your funds are on hold . Please update your bank account detail',
      id: '313',
      inputs: [],
      styles: null,
      title: 'Funds on hold',
      type: 'critical_action_item',
    },
    {
      actions: [
        {
          action: 'navigate',
          action_params: {
            path: '/settings/kyc',
          },
          icon: '',
          icon_position: '',
          properties: {
            variant: 'secondary',
          },
          title: 'Complete KYC',
          type: 'button',
        },
      ],
      alias: 'kyc_critical_action_item',
      analytics: null,
      components: [],
      description: 'Kyc update is required to keep your account active',
      id: '314',
      inputs: [],
      styles: null,
      title: 'Kyc Update Required',
      type: 'critical_action_item',
    },
    {
      actions: [
        {
          action: 'navigate',
          action_params: {
            path: '/settings/payouts',
          },
          icon: '',
          icon_position: '',
          properties: {
            variant: 'secondary',
          },
          title: 'Add money here',
          type: 'button',
        },
      ],
      alias: 'balance_critical_action_item',
      analytics: null,
      components: [],
      description:
        'Your current account balance is low. Please add some money to continue your payout journey',
      id: '315',
      inputs: [],
      styles: null,
      title: 'Low balance',
      type: 'critical_action_item',
    },
  ],
  id: '31',
  inputs: [],
  styles: null,
  title: 'Critical Actions for you',
  type: 'critical_actions',
};
