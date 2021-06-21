import moment from 'moment';
import analyticsService from 'common/utils/analyticsService';
import { getFixedINRAmount } from 'common/utils/rzp-utils';
import store from '../../../merchant/store';

const dateFormat = 'DD MMM YYYY, hh:mm:ss a';

const trackEvent = (obj) => {
  const {
    session: { user },
  } = store.getState();

  analyticsService.track({
    ...obj,
    properties: {
      ...obj.properties,
      flags: {
        es_on_demand: user.isFeatureEnabled('es_on_demand'),
        es_on_demand_restricted: user.isFeatureEnabled('es_on_demand_restricted'),
        es_automatic: user.isFeatureEnabled('es_automatic'),
      },
    },
  });
};

const trackEnableNowEvent = (fromWhere, actionName, behaviour) => {
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
    objectName: 'ScheduledES',
    actionName,
    screen,
    properties: {
      location,
      behaviour,
    },
  });
};

const trackSettleNowEvent = (fromWhere, actionName, stage, additionalProperties) => {
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
      screen = 'Ondemand Settlements Tab';
      stage = null;
      properties = {
        location: 'Empty State',
      };
      break;
    }

    default:
      break;
  }

  trackEvent({
    objectName: 'OndemandES',
    actionName,
    screen,
    properties: {
      stage,
      ...additionalProperties,
      ...properties,
    },
  });
};

const trackOnDemandEsEvent = (actionName, additionalProperties) => {
  trackEvent({
    objectName: 'OndemandES',
    actionName,
    screen: 'Ondemand Settlements Tab',
    properties: {
      ...additionalProperties,
    },
  });
};

const trackPayoutDetailsEvent = (actionName, additionalProperties) => {
  trackEvent({
    objectName: 'OndemandES>2L',
    actionName,
    screen: 'Payout Details',
    properties: {
      ...additionalProperties,
    },
  });
};

export const trackEnableNow = (fromWhere) => trackEnableNowEvent(fromWhere, 'ClickedEnableNow');

export const trackConfirmEnableNow = (fromWhere) =>
  trackEnableNowEvent(fromWhere, 'ClickedEnableES', 'Confirm Enable ES');

export const trackEnableNowClose = (fromWhere) =>
  trackEnableNowEvent(fromWhere, 'ClickedClose', 'Cancel Enable ES modal');

export const trackSettleNowClicked = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'ClickedSettleNow', 'Settlement Initiated');

export const trackSettleNowInfoHover = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'Hovered', 'Seen tooltip');

export const trackSettleAmountUpdated = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'UpdatedAmount', 'Update Prefilled Amount');

export const trackSettleNowAmountError = (fromWhere, error) =>
  trackSettleNowEvent(fromWhere, 'SeenAmountError', 'Amount error', {
    error,
  });

export const trackSettleNowShowBreakup = (fromWhere, clickedConfirm) => {
  const stage = clickedConfirm ? 'After Confirm' : 'Before Confirm';
  trackSettleNowEvent(fromWhere, 'ClickedShowbreakup', stage);
};

export const trackSettleNowCloseClick = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'ClickedClose', 'Before Confirm');

export const trackSettleNowCloseReason = (fromWhere, desc) =>
  trackSettleNowEvent(fromWhere, 'SelectReason', `Cancel Reason | ${desc}`);

export const trackSettleNowConfirmClose = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'ClickedConfirmClose', 'Exits Settlement | Churn');

export const trackSettleNowFirstConfirm = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'ClickedFirstConfirm', 'First Confirm');

export const trackSettleNowSecondConfirm = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'ClickedSecondConfirm', 'Second Confirm');

export const trackSettleNowCancelConfirm = (fromWhere) =>
  trackSettleNowEvent(fromWhere, 'ClickedCancelConfirm', 'Cancel | Back to Settlement Initiated');

export const trackOnDemandTabClick = () => trackOnDemandEsEvent('TabClicked');

export const trackOnDemandSearchClick = (search) => {
  const { id, status, count } = search;
  trackOnDemandEsEvent('ClickedSearch', {
    search: {
      'Settlement ID': id,
      status,
      'Count Field': count,
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
  trackOnDemandEsEvent('ClickedLineDetails', {
    'Settlement Line Details': {
      'Ondemand Settlement ID': id,
      'Requested Amount': getFixedINRAmount(amount_requested),
      'Settled Amount': getFixedINRAmount(amount_settled),
      'Created At': moment.unix(created_at).format(dateFormat),
      'Settlement Status': status,
      'Ondemand Fee': getFixedINRAmount(fees - tax),
      Tax: getFixedINRAmount(tax),
      UTR: items[0].utr,
    },
  });
};

export const trackOnDemandHoverInfo = () => trackOnDemandEsEvent('Total Hovered');

export const trackOnDemandViewMoreClick = () => trackOnDemandEsEvent('ViewMoreClicked');

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

  trackPayoutDetailsEvent('UTRClicked', {
    'UTR Line Details': {
      'Ondemand Settlement ID': id,
      'Requested Amount': getFixedINRAmount(amount_requested),
      'Settled Amount': getFixedINRAmount(amount_settled),
      'Created At': moment.unix(created_at).format(dateFormat),
      'Settlement Status': settlement_status,
      UTR: utr,
      'Payout Amount': getFixedINRAmount(payout_amount),
      'Payout Status': payout_status,
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

  trackPayoutDetailsEvent('DetailsPage', {
    'UTR Payout Details': {
      'Ondemand Settlement ID': id,
      'Requested Amount': getFixedINRAmount(amount_requested),
      'Settled Amount': getFixedINRAmount(amount_settled),
      'Created At': moment.unix(created_at).format(dateFormat),
      'Settlement Status': settlement_status,
    },
  });
};

export const trackOnDemandPayoutDeductionsHover = () => trackPayoutDetailsEvent('Hovered');

export const trackOnDemandPayoutSearch = (values) => {
  trackPayoutDetailsEvent('ClickedSearch', {
    values,
  });
};

export const trackOnDemandPayoutDetailsBreakup = (settlement) => {
  const {
    id,
    amount_settled,
    amount_pending,
    amount_reversed,
    tax,
    fees,
    ondemand_payouts: { items = [], count: total_count = 0 },
  } = settlement;

  const reversedTxnCount = items.reduce((count, item) => {
    return item.status.toLowerCase() === 'reversed' ? count + 1 : count;
  }, 0);

  const processedTxnCount = items.reduce((count, item) => {
    return item.status.toLowerCase() === 'processed' ? count + 1 : count;
  }, 0);

  const pendingTxnCount = total_count - reversedTxnCount - processedTxnCount;

  trackPayoutDetailsEvent('PayoutDetails', {
    'Payout Details': {
      'Ondemand Settlement Id': id,
      'Settled Amount | Count': `${getFixedINRAmount(amount_settled)} | ${processedTxnCount}`,
      'Pending Amount | Count': `${getFixedINRAmount(amount_pending)} | ${pendingTxnCount}`,
      'Reversed Amount | Count': `${getFixedINRAmount(amount_reversed)} | ${reversedTxnCount}`,
      'Ondemand Fee': `${getFixedINRAmount(fees - tax)}`,
      Tax: getFixedINRAmount(tax),
    },
  });
};

export const onDemandModalTrackEvents = {
  trackSettleNowAmountError,
  trackSettleAmountUpdated,
  trackSettleNowInfoHover,
  trackSettleNowShowBreakup,
  trackSettleNowCloseClick,
  trackSettleNowFirstConfirm,
  trackSettleNowSecondConfirm,
  trackSettleNowCancelConfirm,
};
