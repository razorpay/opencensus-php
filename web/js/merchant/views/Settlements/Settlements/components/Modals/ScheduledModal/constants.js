import moment from 'moment';
import { getDiscountPercentage, isPricingRateValid } from './utils';

export const FULL_SHIFT_DAYS = 30;
export const DEFAULT_PRICING_RATE = 30;
export const NUDGE_TYPES = {
  FULL_SUCCESS: 'FULL_SUCCESS',
};

export const SETTLEMENTS_TIMING = [
  {
    time: '09:00 AM',
    label: 'Morning Settlement',
    icon: 'clock-nine',
    info: 'Transactions successfully completed between 5:00PM to 8:59AM (next day) shall be settled to your bank account at 9:00AM on the same bank working day.',
  },
  {
    time: '05:00 PM',
    label: 'Evening Settlement',
    icon: 'clock-five',
    info: 'Transactions successfully completed between 9:00AM to 4:59PM (same day) shall be settled to the your bank account at 5:00PM on the same bank working day.',
  },
];

export const SAMEDAY_TIMELINE_ICONS = ['unlock', 'watch', 'launch'];

export const PRE_ENABLE_VIEWS = {
  INSTANT_SETTLEMENTS: 'INSTANT_SETTLEMENTS',
  SAMEDAY_SETTLEMENTS: 'SAMEDAY_SETTLEMENTS',
};

export const getSamedayTimeline = () => {
  return [
    {
      title: 'Today',
      date: moment(),
      info: 'Get early access to Same-day Settlements',
    },
    {
      title: 'For the first 30 days',
      info: (
        <>
          Until {moment().add(FULL_SHIFT_DAYS, 'days').format('MMMM D, YYYY')} you will receive 60%
          of your balance upto ₹15,000
        </>
      ),
    },
    {
      title: 'From day 31',
      date: moment().add(FULL_SHIFT_DAYS + 1, 'days'),
      info: 'Consistent cash flow with no delays between sales and cash in hand',
    },
  ];
};

export const ONDEMAND_FEE_BENEFITS = [
  {
    percentage: '100%',
    label: 'Instant and Same-day Settlements, forever!',
  },
  {
    percentage: '₹0',
    label: 'Additional annual maintenance fees',
  },
];

export const getSamedayBenefits = (pricingRate) => {
  if (isPricingRateValid(pricingRate)) {
    return [
      {
        percentage: '100%',
        label: 'Instant and Same-day Settlements, forever!',
      },
      {
        percentage: `${getDiscountPercentage(pricingRate)}%`,
        label: 'Discount on your Instant Settlements',
      },
    ];
  }
  return ONDEMAND_FEE_BENEFITS;
};

export const UNLOCK_POINTS = [
  'Maintain daily payment gateway transactions',
  'Keep refunds low',
  'Minimise bank chargebacks',
];

export const DAILY_LIMIT_POINTS = [
  'Limit assigned to you is available until the next working day',
  'Everyone including you gets a higher success rate throughout the day ',
];

export const POST_ENABLE_TYPES = {
  SAMEDAY_PARTIAL_SUCCESS: 'SAMEDAY_PARTIAL_SUCCESS',
  SAMEDAY_FULL_SUCCESS: 'SAMEDAY_FULL_SUCCESS',
  SAMEDAY_FULL_FAILURE: 'SAMEDAY_FULL_FAILURE',
  SAMEDAY_FULL_SHIFT_PROGRESS: 'SAMEDAY_FULL_SHIFT_PROGRESS',
  SAMEDAY_FULL_SUCCESS_SHIFT: 'SAMEDAY_FULL_SUCCESS_SHIFT',
  FULL_SUCCESS_SHIFT_WITHOUT_SAMEDAY: 'FULL_SUCCESS_SHIFT_WITHOUT_SAMEDAY',
  SAMEDAY_FULL_UNLOCK_STATUS: 'SAMEDAY_FULL_UNLOCK_STATUS',
  ODS_MERCHANT_LEVEL_LIMIT: 'ODS_MERCHANT_LEVEL_LIMIT',
};

export const SAMEDAY_MODAL_LOCATIONS = {
  SETTLEMENTS_HOME: 'SETTLEMENTS_HOME',
  SETTLEMENTS_DETAILS: 'SETTLEMENTS_DETAILS',
  ONDEMAND: 'ONDEMAND',
  ENABLE_AUTOMATIC_ROUTE: 'ENABLE_AUTOMATIC_ROUTE',
};
