import moment from 'moment';
import { getFixedINRAmount } from 'common/utils/rzp-utils';
import store from 'merchant/store';
import analyticsService from '@razorpay/commander-services/analytics';

const dateFormat = 'DD MMM YYYY, hh:mm:ss a';

const trackEvent = (obj) => {
  const {
    session: { user },
  } = store.getState();

  try {
    analyticsService.track({
      ...obj,
      properties: {
        ...obj.properties,
        es_on_demand: user.isOndemandSettlementEnabled,
        es_on_demand_restricted: user.isOndemandSettlementsRestricted,
        es_automatic: user.isAutomaticSettlementEnabled,
      },
    });
  } catch (e) {
    // handle error
  }
};

const trackEnableNowEvent = ({ fromWhere, objectName, actionName, behaviour }) => {
  let screen, location;

  switch (fromWhere) {
    case '/settlements': {
      screen = 'Settlements';
      location = 'Tab Banner';
      break;
    }
    case '/instantsettlements': {
      screen = 'Ondemand Settlements Tab';
      location = 'Tab Banner';
      break;
    }
    case 'banner': {
      screen = 'Ondemand Settlements Tab';
      location = 'Banner inside';
      break;
    }
    default:
      break;
  }

  trackEvent({
    objectName,
    actionName,
    screen,
    properties: {
      location,
      behaviour,
    },
  });
};

const trackSettleNowEvent = ({ fromWhere, objectName, actionName, additionalProperties }) => {
  let screen, properties;

  switch (fromWhere) {
    case 'Home': {
      screen = 'Home Dashboard';
      break;
    }

    case 'Settlements': {
      screen = 'Settlements';
      break;
    }

    case 'Instant Settlements': {
      screen = 'Ondemand Settlements Tab';
      break;
    }

    case 'Empty State': {
      actionName = 'Settle Now Empty State';
      screen = 'Ondemand Settlements Tab';
      properties = {
        location: 'Empty State',
      };
      break;
    }

    default:
      break;
  }

  trackEvent({
    objectName,
    actionName,
    screen,
    properties: {
      ...additionalProperties,
      ...properties,
    },
  });
};

export const trackEnableNow = (fromWhere) =>
  trackEnableNowEvent({ fromWhere, objectName: 'Scheduled ES Enable Now', actionName: 'Clicked' });

export const trackConfirmEnableNow = (fromWhere) =>
  trackEnableNowEvent({
    fromWhere,
    objectName: 'Scheduled ES Enable Modal Confirm',
    actionName: 'Clicked',
    behaviour: 'Confirm Enable ES',
  });

export const trackEnableNowClose = (fromWhere) =>
  trackEnableNowEvent({
    fromWhere,
    objectName: 'Scheduled ES Enable Modal Close',
    actionName: 'Clicked',
    behaviour: 'Cancel Enable ES modal',
  });

export const trackSettleNowClicked = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now',
    actionName: 'Clicked',
    additionalProperties: { stage: 'Settlement Initiated' },
  });

export const trackSettleNowInfoHover = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'ES Restricted Info icon',
    actionName: 'Hovered',
    additionalProperties: { stage: 'Seen tooltip' },
  });

export const trackSettleAmountUpdated = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settlement Amount',
    actionName: 'Updated',
    additionalProperties: {
      stage: 'Update Prefilled Amount',
    },
  });

export const trackSettleNowShowBreakup = (fromWhere, clickedConfirm) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Show breakup',
    actionName: 'Clicked',
    additionalProperties: {
      stage: clickedConfirm ? 'After Confirm' : 'Before Confirm',
    },
  });

export const trackSettleNowCloseClick = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now Modal Close',
    actionName: 'Clicked',
    additionalProperties: {
      stage: 'Before Confirm',
    },
  });

export const trackSettleNowCloseReason = (fromWhere, desc) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Reason for Close',
    actionName: 'Selected',
    additionalProperties: {
      stage: `Cancel Reason | ${desc}`,
    },
  });

export const trackSettleNowConfirmClose = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now Confirm Close',
    actionName: 'Clicked',
    additionalProperties: {
      stage: 'Exits Settlement | Churn',
    },
  });

export const trackSettleNowFirstConfirm = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now First Confirm',
    actionName: 'Clicked',
    additionalProperties: {
      stage: 'First Confirm',
    },
  });

export const trackSettleNowSecondConfirm = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now Second Confirm',
    actionName: 'Clicked',
    additionalProperties: {
      stage: 'Second Confirm',
    },
  });

export const trackSettleNowCancelConfirm = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: "Settle Now No Don't",
    actionName: 'Clicked',
    additionalProperties: {
      stage: 'Cancel | Back to Settlement Initiated',
    },
  });

export const trackOnDemandTabClick = (user) =>
  trackEvent({
    objectName: 'Ondemand Settlement Tab',
    actionName: 'Clicked',
    screen: 'Ondemand Settlements Tab',
    properties: {
      page: 'Home Screen',
      settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : ' v1',
      state: user.isTransacted ? 'Complete' : 'Empty',
      activation_status: user.activation_status,
      sessionId: window?.session_id ? window.session_id : undefined,
      isL2Completed: user.isActivated && true,
      international_payments_enabled: user.international,
    },
  });

export const trackOnDemandSearchClick = (search) => {
  const { id, status, count } = search;

  trackEvent({
    objectName: 'Ondemand ES Search',
    actionName: 'Clicked',
    screen: 'Ondemand Settlements Tab',
    properties: {
      search_settlement_id: id,
      search_status: status,
      search_count_field: count,
    },
  });
};

export const trackOnDemandIdDetails = (settlement) => {
  const {
    id,
    amount_requested,
    amount_settled,
    created_at,
    status,
    fees,
    tax,
    ondemand_payouts: { items },
  } = settlement;

  trackEvent({
    objectName: 'Ondemand Line Details',
    actionName: 'Fetched',
    screen: 'Ondemand Settlements Tab',
    properties: {
      ondemand_settlement_id: id,
      requested_amount: getFixedINRAmount(amount_requested),
      settled_amount: getFixedINRAmount(amount_settled),
      created_at: moment.unix(created_at).format(dateFormat),
      settlement_status: status,
      ondemand_fee: getFixedINRAmount(fees - tax),
      Tax: getFixedINRAmount(tax),
      UTR: items[0].utr,
    },
  });
};

export const trackOnDemandHoverInfo = () =>
  trackEvent({
    objectName: 'Ondemand Total Settled Amount',
    actionName: 'Hovered',
    screen: 'Ondemand Settlements Tab',
  });

export const trackOnDemandViewMoreClick = () =>
  trackEvent({
    objectName: 'Ondemand View More Details',
    actionName: 'Clicked',
    screen: 'Ondemand Settlements Tab',
  });

export const trackOnDemandPayoutIdClick = (details) => {
  const {
    payoutDetails: { amount: payout_amount, status: payout_status, utr },
    settlementDetails: {
      id,
      amount_requested,
      amount_settled,
      created_at,
      status: settlement_status,
    },
  } = details;

  trackEvent({
    objectName: 'Ondemand UTR Line item',
    actionName: 'Clicked',
    screen: 'Payout Details',
    properties: {
      ondemand_settlement_id: id,
      requested_amount: getFixedINRAmount(amount_requested),
      settled_amount: getFixedINRAmount(amount_settled),
      created_at: moment.unix(created_at).format(dateFormat),
      settlement_status,
      UTR: utr,
      payout_amount: getFixedINRAmount(payout_amount),
      payout_status,
    },
  });
};

export const trackOnDemandPayoutDetailsFetched = (settlement) => {
  const {
    id,
    amount_requested,
    amount_settled,
    created_at,
    status: settlement_status,
  } = settlement;

  trackEvent({
    objectName: 'Ondemand UTR Page',
    actionName: 'Visited',
    screen: 'Payout Details',
    properties: {
      ondemand_settlement_id: id,
      requested_amount: getFixedINRAmount(amount_requested),
      settled_amount: getFixedINRAmount(amount_settled),
      created_at: moment.unix(created_at).format(dateFormat),
      settlement_status,
    },
  });
};

export const trackOnDemandPayoutDeductionsHover = () =>
  trackEvent({
    objectName: 'Ondemand Deductions info icon',
    actionName: 'Hovered',
    screen: 'Payout Details',
  });

export const trackOnDemandPayoutSearch = (values) =>
  trackEvent({
    objectName: 'Ondemand UTR Search',
    actionName: 'Clicked',
    screen: 'Payout Details',
    properties: {
      values,
    },
  });

/* IS++ tracking events */
export const trackISCheckbox = (fromWhere, checked) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Get extra cash advance (IS)',
    actionName: checked ? 'Checked' : 'Unchecked',
  });

export const trackISSettleNowFirstConfirm = (fromWhere, amount, advanceAmount) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now - First Confirm (IS)',
    actionName: 'Clicked',
    additionalProperties: {
      amount,
      advanceAmount,
    },
  });

export const trackISSettleNowSecondConfirm = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now - Second Confirm (IS)',
    actionName: 'Clicked',
  });

export const trackISSuccess = (fromWhere) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now - Get Settlement Balance (IS)',
    actionName: 'Clicked',
  });

export const trackISFailure = (fromWhere, reason) =>
  trackSettleNowEvent({
    fromWhere,
    objectName: 'Settle Now - Go to Instant Settlement (IS)',
    actionName: 'Clicked',
    additionalProperties: {
      reason,
    },
  });
export const onDemandModalTrackEvents = {
  trackSettleAmountUpdated,
  trackSettleNowInfoHover,
  trackSettleNowShowBreakup,
  trackSettleNowCloseClick,
  trackSettleNowFirstConfirm,
  trackSettleNowSecondConfirm,
  trackSettleNowCancelConfirm,
  trackISCheckbox,
  trackISSettleNowFirstConfirm,
  trackISSettleNowSecondConfirm,
  trackISSuccess,
  trackISFailure,
};
