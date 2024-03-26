import cloneDeep from 'lodash/cloneDeep';

import {
  IMerchantOverviewData,
  TodaySettlementKeys,
  UpcomingSettlementKeys,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';

export enum heroCardVariants {
  NON_SETTLEMENT_NOT_TRANSACTED = 'NON_SETTLEMENT_NOT_TRANSACTED',
  NON_SETTLEMENT_TRANSACTED = 'NON_SETTLEMENT_TRANSACTED',
  SETTLEMENT_TODAY_CREATED = 'SETTLEMENT_TODAY_CREATED',
  SETTLEMENT_TODAY_PROCESSED_BEFORE_SLA_PREVIOUS = 'SETTLEMENT_TODAY_PROCESSED_BEFORE_SLA_PREVIOUS',
  SETTLEMENT_TODAY_PROCESSED_AFTER_SLA_PREVIOUS = 'SETTLEMENT_TODAY_PROCESSED_AFTER_SLA_PREVIOUS',
  SETTLEMENT_TODAY_DLEAYED_PREVIOUS = 'SETTLEMENT_TODAY_DLEAYED_PREVIOUS',
  SETTLEMENT_TODAY_FAIL_RETRY_SLA_NOT_BREACHED_PREVIOUS = 'SETTLEMENT_TODAY_FAIL_RETRY_SLA_NOT_BREACHED_PREVIOUS',
  SETTLEMENT_TODAY_FAIL_RETRY_SLA_BREACHED_PREVIOUS = 'SETTLEMENT_TODAY_FAIL_RETRY_SLA_BREACHED_PREVIOUS',
  SETTLEMENT_TODAY_FAIL_SOH_PREVIOUS = 'SETTLEMENT_TODAY_FAIL_SOH_PREVIOUS',
  SETTLEMENT_TODAY_MULTIPLE = 'SETTLEMENT_TODAY_MULTIPLE',
  SETTLEMENT_PREVIOUS_ONLY = 'SETTLEMENT_PREVIOUS_ONLY',
  SETTLEMENT_UPCOMMING_BLOCK_FOH = 'SETTLEMENT_UPCOMMING_BLOCK_FOH',
  SETTLEMENT_UPCOMMING_BLOCK_MOH = 'SETTLEMENT_UPCOMMING_BLOCK_MOH',
  SETTLEMENT_UPCOMMING_BLOCK_SOH = 'SETTLEMENT_UPCOMMING_BLOCK_SOH',
  SETTLEMENT_UPCOMMING_PAUSED_NO_NEXT_SETTLEMENT = 'SETTLEMENT_UPCOMMING_PAUSED_NO_NEXT_SETTLEMENT',
  SETTLEMENT_UPCOMMING_SKIP_NEGATIVE_BALANCE = 'SETTLEMENT_UPCOMMING_SKIP_NEGATIVE_BALANCE',
  SETTLEMENT_UPCOMMING_SKIP_LESS_THAN_1 = 'SETTLEMENT_UPCOMMING_SKIP_LESS_THAN_1',
  SETTLEMENT_UPCOMMING_SETTLEMENT_ON_TRACK = 'SETTLEMENT_UPCOMMING_SETTLEMENT_ON_TRACK',
}

type HeroCardsVariantsData = {
  [key in heroCardVariants]: IMerchantOverviewData;
};
export const heroCardsVariantsData: HeroCardsVariantsData = {
  [heroCardVariants.NON_SETTLEMENT_NOT_TRANSACTED]: { is_transacted: false, is_settlement: false },
  [heroCardVariants.NON_SETTLEMENT_TRANSACTED]: {
    is_transacted: true,
    is_settlement: false,
    settlement_schedule: 'T+3',
  },
  [heroCardVariants.SETTLEMENT_TODAY_CREATED]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 98494,
        total_count: 1,
        delayed_total_amount: 0,
        delayed_count: 0,
        failed_total_amount: 0,
        failed_count: 0,
        created_total_amount: 98494,
        created_count: 1,
        processed_total_amount: 0,
        processed_count: 0,
        title_key: TodaySettlementKeys.SETL_TODAY_CREATED,
      },
    },
  },
  [heroCardVariants.SETTLEMENT_TODAY_PROCESSED_BEFORE_SLA_PREVIOUS]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 98494,
        total_count: 1,
        delayed_total_amount: 0,
        delayed_count: 0,
        failed_total_amount: 0,
        failed_count: 0,
        created_total_amount: 0,
        created_count: 0,
        processed_total_amount: 98494,
        processed_count: 1,
        title_key: TodaySettlementKeys.SETL_TODAY_PROCESSED_BEFORE_THAN_8PM,
      },
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_TODAY_PROCESSED_AFTER_SLA_PREVIOUS]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 98494,
        total_count: 1,
        delayed_total_amount: 0,
        delayed_count: 0,
        failed_total_amount: 0,
        failed_count: 0,
        created_total_amount: 0,
        created_count: 0,
        processed_total_amount: 98494,
        processed_count: 1,
        title_key: TodaySettlementKeys.SETL_TODAY_PROCESSED_AFTER_THAN_8PM,
      },
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_TODAY_DLEAYED_PREVIOUS]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 98494,
        total_count: 1,
        delayed_total_amount: 98494,
        delayed_count: 1,
        failed_total_amount: 0,
        failed_count: 0,
        created_total_amount: 0,
        created_count: 0,
        processed_total_amount: 0,
        processed_count: 0,
        title_key: TodaySettlementKeys.SETL_TODAY_DELAYED,
      },
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_TODAY_FAIL_RETRY_SLA_NOT_BREACHED_PREVIOUS]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 98494,
        total_count: 1,
        delayed_total_amount: 0,
        delayed_count: 0,
        failed_total_amount: 98494,
        failed_count: 1,
        created_total_amount: 0,
        created_count: 0,
        processed_total_amount: 0,
        processed_count: 0,
        title_key: TodaySettlementKeys.SETL_TODAY_FAIL_RETRY_SLA_NOT_BREACHED,
      },
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_TODAY_FAIL_RETRY_SLA_BREACHED_PREVIOUS]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 98494,
        total_count: 1,
        delayed_total_amount: 0,
        delayed_count: 0,
        failed_total_amount: 98494,
        failed_count: 1,
        created_total_amount: 0,
        created_count: 0,
        processed_total_amount: 0,
        processed_count: 0,
        title_key: TodaySettlementKeys.SETL_TODAY_FAIL_SLA_BREACHED,
      },
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_TODAY_FAIL_SOH_PREVIOUS]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 98494,
        total_count: 1,
        delayed_total_amount: 0,
        delayed_count: 0,
        failed_total_amount: 98494,
        failed_count: 1,
        created_total_amount: 0,
        created_count: 0,
        processed_total_amount: 0,
        processed_count: 0,
        title_key: TodaySettlementKeys.SETL_TODAY_FAIL_SOH,
      },
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_TODAY_MULTIPLE]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      today: {
        total_amount: 3900000,
        total_count: 4,
        delayed_total_amount: 1200000,
        delayed_count: 2,
        failed_total_amount: 0,
        failed_count: 0,
        created_total_amount: 2700000,
        created_count: 2,
        processed_total_amount: 0,
        processed_count: 0,
        title_key: TodaySettlementKeys.SETL_TODAY_MULTIPLE,
      },
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_PREVIOUS_ONLY]: {
    is_transacted: false,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
    },
  },
  [heroCardVariants.SETTLEMENT_UPCOMMING_BLOCK_FOH]: {
    is_transacted: true,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
      upcoming_settlement: {
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_FOH,
        next_settlement_time: 0,
        settlement_amount: 0,
      },
    },
  },
  [heroCardVariants.SETTLEMENT_UPCOMMING_BLOCK_MOH]: {
    is_transacted: true,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
      upcoming_settlement: {
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_MOH,
        next_settlement_time: 0,
        settlement_amount: 0,
      },
    },
  },
  [heroCardVariants.SETTLEMENT_UPCOMMING_BLOCK_SOH]: {
    is_transacted: true,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
      upcoming_settlement: {
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_SOH,
        next_settlement_time: 0,
        settlement_amount: 0,
      },
    },
  },
  [heroCardVariants.SETTLEMENT_UPCOMMING_PAUSED_NO_NEXT_SETTLEMENT]: {
    is_transacted: true,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
      upcoming_settlement: {
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_NO_NEXT_SETTLEMENT,
        next_settlement_time: 0,
        settlement_amount: 0,
      },
    },
  },
  [heroCardVariants.SETTLEMENT_UPCOMMING_SKIP_NEGATIVE_BALANCE]: {
    is_transacted: true,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 4400,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
      upcoming_settlement: {
        settlement_amount: 112210,
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_AMOUNT_GREATER_THAN_BALANCE,
        next_settlement_time: 929022002,
      },
    },
  },
  [heroCardVariants.SETTLEMENT_UPCOMMING_SKIP_LESS_THAN_1]: {
    is_transacted: true,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 1023523,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
      upcoming_settlement: {
        settlement_amount: 53,
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_AMOUNT_LESS_THAN_ONE,
        next_settlement_time: 929022002,
      },
    },
  },
  [heroCardVariants.SETTLEMENT_UPCOMMING_SETTLEMENT_ON_TRACK]: {
    is_transacted: true,
    is_settlement: true,
    settlement_schedule: 'T+3',
    settlement: {
      current_balance: 87272929,
      current_balance_currency: 'INR',
      settlement_currency: 'INR',
      previous: { total_amount: 8292, total_count: 7, created_at: 929022002 },
      upcoming_settlement: {
        settlement_amount: 1110,
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_ON_TRACK,
        next_settlement_time: 929022002,
      },
    },
  },
};

export const heroCardComponentTemplate = {
  id: '1111',
  type: 'hero_card',
  title: 'Hero card',
  background_img_url: '',
  actions: [],
  inputs: [],
  components: [],
  data: {
    hero_card_data: {
      is_transacted: false,
      is_settlement: false,
    },
  },
};

type HeroCardVariant = {
  data: {
    hero_card_data: IMerchantOverviewData;
  };
};
type HeroCardCases = {
  [key in heroCardVariants]: HeroCardVariant;
};

// this creates mock response for hero card widgets, each object in this array represent every possible view for hero card
// Merchant Overview Possible Cases
// ├── Non Settlement
// │   ├── Non Transacted user
// │   └── Transacted user
// └── Settlement
//     ├── Today Settlement
//     │   ├── Single Settlement
//     │   └── Multiple Settlement
//     ├── Upcomming Settlement
//     │   ├── Upcomming Settlement Blocked/Paused
//     │   │   ├── Blocked => FOH,SOH,MOH
//     │   │   └── Paused => no next settlement time due to lack of activity
//     │   └── Upcomming Settlement Skipped/On Track
//     │       ├── Skipped => negative balance, balance less than 1rs
//     │       └── On Track => else if we have next settlement amount and time.
//     └── Previous Settlement
//         ├── Previous Settlement along with today/upcomming settlement
//         └── Only Previous Settlement
export const getMockDataForHeroCardWithWidget = (): HeroCardCases => {
  const heroCardCases = {} as HeroCardCases;
  for (const key of Object.values(heroCardVariants)) {
    const data = heroCardsVariantsData[key];
    const variant: HeroCardVariant = cloneDeep(heroCardComponentTemplate);
    variant.data.hero_card_data = data;
    heroCardCases[key] = variant;
  }
  return heroCardCases;
};
