import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import { setTrackData } from 'common/utils/googleAnalytics';

const track = setTrackData({ eventCategory: 'LA Dashboard - Transfers' });

export const trackClickReverseDetails = eventLabel =>
  track({
    eventAction: 'Click - Reverse details',
    eventLabel,
  });

export const trackClickReversalID = eventLabel =>
  track({
    eventAction: 'Click - Reversal ID',
    eventLabel,
  });

export const trackClickRefundToCustomer = _ =>
  track({
    eventAction: 'Click - Refund to Customer',
  });

export const trackClickCreateRefund = eventLabel =>
  track({
    eventAction: 'Click - Refund to Customer',
    eventLabel,
  });
