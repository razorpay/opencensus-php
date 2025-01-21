import { UpcomingSettlementKeys } from './types';
import { merchantFetch } from 'merchant/utils/ajax';
import { getMode, getUser } from 'merchant/store';
import errorService from '@razorpay/universe-utils/errorService';
import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

// returns date in this format: 'Wed, Feb 7'
export function getGreetingAndDate(date = new Date()): {
  formattedDate: string;
  greeting: string;
} {
  const formattedDate = date.toLocaleString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  });

  const currentHour = date.getHours();

  let greeting = '';
  if (currentHour >= 12 && currentHour <= 17) {
    // Between 12 PM and 5PM
    greeting = 'Good afternoon';
  } else if (currentHour >= 17) {
    // Between 5PM and Midnight
    greeting = 'Good evening';
  } else {
    greeting = 'Good morning';
  }
  return {
    formattedDate,
    greeting,
  };
}

export function getAnalyticsHeroCardStateIdentifier(heroCardData) {
  const { is_settlement, is_transacted, settlement = {} } = heroCardData;
  if (!is_transacted && !is_settlement) {
    return 'NOT_TRANSACTED_NO_SETTLEMENT';
  } else if (is_transacted && !is_settlement) {
    return 'TRANSACTED_NO_SETTLEMENT';
  } else if (
    [
      UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_FOH,
      UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_MOH,
      UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_SOH,
      UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_NO_NEXT_SETTLEMENT,
    ].includes(settlement.upcoming_settlement?.title_key)
  ) {
    return settlement.upcoming_settlement.title_key;
  } else if (settlement.today) {
    return `${settlement.today.title_key}${settlement.previous ? '_WITH_PREVIOUS' : ''}`;
  } else if (settlement.upcoming_settlement) {
    return `${settlement.upcoming_settlement.title_key}${
      settlement.previous ? '_WITH_PREVIOUS' : ''
    }`;
  } else if (settlement.previous) {
    return 'ONLY_PREVIOUS_SETTLEMENT';
  } else {
    return 'UNKNOWN_STATE';
  }
}

export const CURRENT_BALANCE_TOOLTIP =
  'This is the total amount that is due to be deposited in your bank account after deduction of taxes, platform fees, any other applicable charges, and adjustment of refunds and credits';

export const fetchFohTicketData = async () => {
  const user = getUser();
  const mode = getMode();
  try {
    const fohTicketData = await merchantFetch({
      url: 'care_service/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetFOHTicket',
      method: 'post',
      data: {
        merchant: {
          id: user.merchant.id,
        },
        type: 'support_dashboard',
        tags: user.tags,
        mode,
      },
    });
    if (fohTicketData.success) {
      return fohTicketData;
    } else {
      throw new Error(fohTicketData.error);
    }
  } catch (error) {
    errorService.captureError(error, {
      tags: {
        team: Teams.PG_DASHBOARD,
      },
      rank: Ranks.P0,
    });
  }
};
