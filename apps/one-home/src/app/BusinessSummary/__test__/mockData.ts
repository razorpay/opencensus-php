import { BusinessSummarySuccessResponse } from '../types';

const fullResponse: BusinessSummarySuccessResponse = {
  id: '33',
  type: 'home_business_summary',
  title: '',
  actions: [],
  inputs: [
    {
      type: 'select',
      values: ['yesterday', 'last_7_days', 'last_30_days'],
      default_value: 'last_7_days',
      name: 'date',
    },
  ],
  data: {
    one_home_data: {
      business_summary: {
        data_summary: {
          last_updated: '1738061936',
          input_time: 'last_7_days',
        },
      },
    },
  },
  components: [
    {
      id: '331',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payment: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                previous_data: '510000000000',
                percentage_change: 11,
              },
              international_payment_enabled: true,
              online_international_amount: '420000000000',
              online_domestic_amount: '120000000000',
              offline_amount: '110000000000',
              payment_enabled: true,
              payment_locked: false,
              offline_payment_enabled: true,
            },
          },
        },
      },
      alias: 'home_summary_earnings_item',
      analytics: null,
      styles: null,
    },
    {
      id: '332',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            balance: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                percentage_change: 11,
              },
              locked_current_account: false,
              locked_settlement_account: false,
              current_account_balance: '110000000000',
              settlement_account_balance: '110000000000',
              current_account_present: true,
              settlement_account_present: true,
            },
          },
        },
      },
      alias: 'home_summary_balance_item',
      analytics: null,
      styles: null,
    },
    {
      id: '333',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payout: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                previous_data: '510000000000',
                percentage_change: 9,
              },
              api_and_bulk_payouts_amount: '110000000000',
              vendor_payouts_amount: '120000000000',
              business_banking_enabled: true,
              locked: false,
              payroll: {
                enabled: false,
              },
              vendor_payouts_enabled: true,
              api_and_bulk_payouts_enabled: true,
            },
          },
        },
      },
      alias: 'home_summary_spends_item',
      analytics: null,
      styles: null,
    },
  ],
  alias: 'home_business_summary',
  analytics: null,
  styles: null,
};

const responseWithLocked: BusinessSummarySuccessResponse = {
  id: '33',
  type: 'home_business_summary',
  title: '',
  actions: [],
  inputs: [
    {
      type: 'select',
      values: ['yesterday', 'last_7_days', 'last_30_days'],
      default_value: 'last_7_days',
      name: 'date',
    },
  ],
  data: {
    one_home_data: {
      business_summary: {
        data_summary: {
          last_updated: '1738061936',
          input_time: 'last_7_days',
        },
      },
    },
  },
  components: [
    {
      id: '331',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payment: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
              },
              payment_locked: true,
              payment_enabled: true,
            },
          },
        },
      },
      alias: 'home_summary_earnings_item',
      analytics: null,
      styles: null,
    },
    {
      id: '332',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            balance: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                percentage_change: 11,
              },
              locked_current_account: false,
              locked_settlement_account: false,
              current_account_balance: '110000000000',
              settlement_account_balance: '110000000000',
              current_account_present: true,
              settlement_account_present: true,
            },
          },
        },
      },
      alias: 'home_summary_balance_item',
      analytics: null,
      styles: null,
    },
    {
      id: '333',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payout: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
              },
              locked: true,
              business_banking_enabled: true,
            },
          },
        },
      },
      alias: 'home_summary_spends_item',
      analytics: null,
      styles: null,
    },
  ],
  alias: 'home_business_summary',
  analytics: null,
  styles: null,
};

const responseWithGrowth: BusinessSummarySuccessResponse = {
  id: '33',
  type: 'home_business_summary',
  title: '',
  actions: [],
  inputs: [
    {
      type: 'select',
      values: ['yesterday', 'last_7_days', 'last_30_days'],
      default_value: 'last_7_days',
      name: 'date',
    },
  ],
  data: {
    one_home_data: {
      business_summary: {
        data_summary: {
          last_updated: '1738061936',
          input_time: 'last_7_days',
        },
      },
    },
  },
  components: [
    {
      id: '331',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payment: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
              },
              payment_enabled: false,
            },
          },
        },
      },
      alias: 'home_summary_earnings_item',
      analytics: null,
      styles: null,
    },
    {
      id: '332',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            balance: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                percentage_change: 11,
              },
              locked_current_account: false,
              locked_settlement_account: false,
              current_account_balance: '110000000000',
              settlement_account_balance: '110000000000',
              current_account_present: true,
              settlement_account_present: true,
            },
          },
        },
      },
      alias: 'home_summary_balance_item',
      analytics: null,
      styles: null,
    },
    {
      id: '333',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payout: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
              },
              business_banking_enabled: false,
              payroll: {
                enabled: false,
              },
            },
          },
        },
      },
      alias: 'home_summary_spends_item',
      analytics: null,
      styles: null,
    },
  ],
  alias: 'home_business_summary',
  analytics: null,
  styles: null,
};

const responseWithNoOffline: BusinessSummarySuccessResponse = {
  id: '33',
  type: 'home_business_summary',
  title: '',
  actions: [],
  inputs: [
    {
      type: 'select',
      values: ['yesterday', 'last_7_days', 'last_30_days'],
      default_value: 'last_7_days',
      name: 'date',
    },
  ],
  data: {
    one_home_data: {
      business_summary: {
        data_summary: {
          last_updated: '1738061936',
          input_time: 'last_7_days',
        },
      },
    },
  },
  components: [
    {
      id: '331',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payment: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                previous_data: '510000000000',
                percentage_change: 11,
              },

              online_domestic_amount: '120000000000',
              offline_amount: '110000000000',
              payment_enabled: true,
              payment_locked: false,
              offline_payment_enabled: false,
            },
          },
        },
      },
      alias: 'home_summary_earnings_item',
      analytics: null,
      styles: null,
    },
    {
      id: '332',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            balance: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                percentage_change: 11,
              },
              locked_current_account: false,
              locked_settlement_account: false,
              current_account_balance: '110000000000',
              settlement_account_balance: '110000000000',
              current_account_present: true,
              settlement_account_present: true,
            },
          },
        },
      },
      alias: 'home_summary_balance_item',
      analytics: null,
      styles: null,
    },
    {
      id: '333',
      type: 'home_business_summary_item',
      title: '',
      actions: [],
      inputs: [],
      components: [],
      data: {
        one_home_data: {
          business_summary: {
            payout: {
              data_summary: {
                last_updated: '1738061936',
                input_time: 'last_7_days',
                current_data: '520000000000',
                previous_data: '510000000000',
                percentage_change: 9,
              },
              api_and_bulk_payouts_amount: '110000000000',
              vendor_payouts_amount: '120000000000',
              business_banking_enabled: true,
              locked: false,
              payroll: {
                enabled: false,
              },
              vendor_payouts_enabled: false,
              api_and_bulk_payouts_enabled: true,
            },
          },
        },
      },
      alias: 'home_summary_spends_item',
      analytics: null,
      styles: null,
    },
  ],
  alias: 'home_business_summary',
  analytics: null,
  styles: null,
};

export { fullResponse, responseWithLocked, responseWithGrowth, responseWithNoOffline };
