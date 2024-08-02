export const activatedUser = {
  instantActivation: {
    isWhitelistFlow: true,
  },
  isUnregisteredBusiness: true,
  activation_status: '',
  isSubmitted: true,
  isActivationFormFullView: '/activation',
};

export const inActivatedUser = { ...activatedUser };
inActivatedUser.isSubmitted = false;

export const unActivatedUserOnHold = { ...activatedUser };
unActivatedUserOnHold.instantActivation.isWhitelistFlow = false;
unActivatedUserOnHold.isUnregisteredBusiness = false;
unActivatedUserOnHold.isSubmitted = false;

export const inActivatedUserOnHoldAndUnderReview = { ...activatedUser };
inActivatedUserOnHoldAndUnderReview.activation_status = 'under_review';
inActivatedUserOnHoldAndUnderReview.isSubmitted = false;

export const props = {
  settlementAmount: {
    balance: 10039837393,
    balance_currency: 'INR',
    settlement_amount: '33684711',
    settlement_currency: 'INR',
    next_settlement_time: 1670092140,
    reason_for_delay: null,
  },
  settlement: {
    loading: true,
    settlement: {},
    error: null,
    breakupDetails: {
      loading: false,
      items: [],
      error: null,
      isBreakupNew: null,
    },
    schedule: {
      loading: false,
      data: [
        {
          resourceIdField: 'id',
          method: null,
          type: 'settlement',
          name: 'its_new',
          period: 'daily',
          interval: 1,
          anchor: null,
          hour: [13],
          delay: 2,
          international: 0,
          is_early_settlement_schedule: false,
          resourceUrl: 'settlements',
        },
        {
          resourceIdField: 'id',
          method: null,
          type: 'settlement',
          name: 'Basic T7',
          period: 'daily',
          interval: 1,
          anchor: null,
          hour: [13],
          delay: 7,
          international: 1,
          is_early_settlement_schedule: false,
          resourceUrl: 'settlements',
        },
      ],
      error: null,
    },
    holidayList: {
      loading: false,
      data: {
        2022: [
          {
            date: '26/01/2022',
            description: 'Republic Day',
          },
          {
            date: '19/02/2022',
            description: 'Chhatrapati Shivaji Maharaj Jayanti',
          },
          {
            date: '01/03/2022',
            description: 'Mahashivratri',
          },
        ],
      },
      error: null,
    },
    config: {
      loading: false,
      data: {
        config: {
          active: true,
          features: {
            block: {
              reason: '',
              status: false,
            },
            global_hold_config: {
              reason: '',
              status: false,
            },
            hold: {
              reason: 'Sample reason',
              status: true,
            },
          },
          initiate_types: {
            default: {
              enable: false,
            },
            delayed: {
              enable: true,
              schedule_id: 'GdOBasBCoFRirD',
            },
          },
          merchant_email: 'parth.patel@razorpay.com',
          org_id: '100000razorpay',
          schedules: {
            adjustment: {
              default: 'minute level settlement schedule',
            },
            commission: {
              default: '',
            },
            credit_repayment: {
              default: '',
            },
            fund_account_validation: {
              default: '',
            },
            payment: {
              'domestic:default': 'minute level settlement schedule',
              'domestic:upi': 'minute level settlement schedule',
              'international:default': 'minute level settlement schedule',
            },
            payout: {
              default: '',
            },
            refund: {
              default: '',
            },
            reversal: {
              default: '',
            },
            'settlement.ondemand': {
              default: 'minute level settlement schedule',
            },
            settlement_transfer: {
              default: 'minute level settlement schedule',
            },
            transfer: {
              default: 'minute level settlement schedule',
            },
          },
          settle_to_org: true,
        },
      },
      error: null,
    },
    timeline: {
      loading: false,
      data: null,
      error: null,
    },
  },
  handleSettlementGuideClick: jest.fn(),
  trackContactSupport: jest.fn(),
  trackKnowMore: jest.fn(),
  trackSameDaySettlement: jest.fn(),
  trackSettlementClose: jest.fn(),
};
