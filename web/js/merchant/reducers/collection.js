import { set, merge, unshift, remove } from 'common/utils/immutable';
import omit from 'lodash/omit';
import createReducer from 'merchant_common/reducers/createReducer';

import QRPayment from 'merchant/models/QRPayment';
import Payment from 'merchant/models/Payment';
import Refund from 'merchant/models/Refund';
import Order from 'merchant/models/Order';
import Settlement from 'merchant/models/Settlement';
import InstantSettlement from 'merchant/models/InstantSettlement';
import RouteOndemandSettlements from 'merchant/models/RouteOndemandSettlements';
import Reversal from 'merchant/models/Reversal';
import Transfer from 'merchant/models/Transfer';
import Dispute from 'merchant/models/Dispute';
import Submerchant from 'merchant/models/Submerchant';
import Token from 'merchant/models/Token';
import Commission from 'merchant/models/Commission';
import Invitation from 'merchant/models/Invitation';
import B2bExportsPayments from 'merchant/models/B2bExportsPayments';

import RegistrationLink from 'merchant/models/RegistrationLink';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties, decodeSensitiveFields } from 'common/utils/rzp-utils';

const TRACK_NAMESPACES = ['PAYMENTS', 'REFUNDS', 'SETTLEMENTS'];

const trackNamespaceEvents = (action, success) => {
  const NAMESPACE = action?.type?.split('_')[0];

  if (TRACK_NAMESPACES.includes(NAMESPACE)) {
    const QUERY_PARAMS = window.location.href.split('?');
    const PROPERTIES = success ? { success } : { success, failureReason: action?.payload?.errors };

    analyticsTrack({
      objectName: `${NAMESPACE?.toLowerCase()} collection search`,
      actionName: 'result',
      properties: {
        ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
        ...PROPERTIES,
        queryParams: QUERY_PARAMS?.length > 1 ? QUERY_PARAMS[1] : '',
      },
      screen: window.location.pathname.split('/').pop() || NAMESPACE,
      toLumberjack: true,
    });
  }
};

export const getActionName = (namespace) => {
  return `${namespace}_FETCH`;
};

// useEntityReducer tells whether to use common reducer or entity-specific
export const fetchAll = (params, Entity, namespace) => {
  let entity;
  if (typeof Entity === 'function') {
    entity = new Entity();
  } else {
    entity = Entity;
  }
  // TODO: Filter non-INR currencies temporarily (as Blade's Amount component doesn't support it)
  const test = entity.fetchAll(params).then((res) => {
    const newRes = { ...res };
    newRes.data.items = newRes.data.items.filter((item) => item.currency === 'INR');
    return newRes;
  });
  console.log('test', test);
  return {
    type: getActionName(namespace),
    payload: test,
  };
};

const defaultInitialState = {
  loading: true,
  items: [],
  error: null,
};

export const listFetchPendingState = (state, action, initialState) => {
  return initialState;
};

export const listFetchSuccessState = (state, action) => {
  trackNamespaceEvents(action, true);
  return merge(state, {
    loading: false,
    items: action.payload.data.items,
    error: null,
  });
};

export const listFetchErrorState = (state, action, initialState) => {
  trackNamespaceEvents(action, false);
  return merge(state, {
    loading: false,
    items: initialState.items,
    error: action.payload.errors,
  });
};

export const appendEntityToList = (state, action) => {
  return set(state, 'items', unshift(state.items, action.payload));
};

export const addUniqueEntityToList = (state, action) => {
  const itemIndex = state.items.findIndex((item) => item.id === action.payload.id);
  return itemIndex < 0 ? set(state, 'items', [...state.items, action.payload]) : state;
};

export const updateEntityInList = (state, action) => {
  const itemIndex = state.items.findIndex((item) => item.id === action.payload.id);
  if (itemIndex < 0) return state;
  return set(state, `items.${itemIndex}`, action.payload);
};

export const removeEntityFromList = (state, action) => {
  const itemsList = remove(state.items, (item) => item.id === action.payload.id);
  return set(state, 'items', itemsList);
};

export const makeCollectionReducer = (
  namespace,
  actionHandlers = {},
  initialState = defaultInitialState,
) => {
  const fetchActionName = getActionName(namespace);
  const defaultHandlers = {
    [`${fetchActionName}::PENDING`]: listFetchPendingState,
    [`${fetchActionName}::SUCCESS`]: listFetchSuccessState,
    [`${fetchActionName}::ERROR`]: listFetchErrorState,
  };

  const handlers = { ...defaultHandlers, ...actionHandlers };
  return createReducer({
    handlers,
    initialState,
  });
};

export const makeActionCollectionReducer = (
  namespace,
  actionHandlers = {},
  initialState = defaultInitialState,
) => {
  const singularNamespace = namespace.slice(0, namespace.length - 1);

  const defaultHandlers = {
    [`${singularNamespace}_CREATE::SUCCESS`]: appendEntityToList,
    [`${singularNamespace}_EDIT::SUCCESS`]: updateEntityInList,
    [`${singularNamespace}_CANCEL::SUCCESS`]: updateEntityInList,
    [`${singularNamespace}_DELETE::SUCCESS`]: removeEntityFromList,
  };

  const handlers = { ...defaultHandlers, ...actionHandlers };
  return makeCollectionReducer(namespace, handlers, initialState);
};

// TODO: Below things should be moved to individual files

export const fetchPayments = (params) => {
  return fetchAll(decodeSensitiveFields(params), Payment, 'PAYMENTS');
};
export const paymentsReducer = makeActionCollectionReducer(
  'PAYMENTS',
  {},
  // ignore( do not send to API ) "ref" param if seen present the url
  { ...defaultInitialState, blacklistQueryParams: ['ref'] },
);

export const fetchOrders = (params) => fetchAll(params, Order, 'ORDERS');
export const ordersReducer = makeCollectionReducer('ORDERS');

export const fetchTransfers = (params) => fetchAll(params, Transfer, 'TRANSFERS');
export const transfersReducer = makeActionCollectionReducer('TRANSFERS');

export const fetchReversals = (params) => fetchAll(params, Reversal, 'REVERSALS');
export const reversalsReducer = makeCollectionReducer('REVERSALS');

export const fetchB2bPayments = (params) =>
  fetchAll(params, B2bExportsPayments, 'B2B_EXPORTS_TRANSACTIONS');

export const fetchMarketplacePayments = (params) => {
  params.transferred = 1;
  return fetchAll(decodeSensitiveFields(params), Payment, 'MP_PAYMENTS');
};
export const mpPaymentsReducer = makeCollectionReducer('MP_PAYMENTS');

export const fetchRefunds = (params) => fetchAll(params, Refund, 'REFUNDS');
export const refundsReducer = makeCollectionReducer(
  'REFUNDS',
  {},
  // ignore( do not send to API ) "ref" param if seen present the url
  { ...defaultInitialState, blacklistQueryParams: ['ref'] },
);

export const fetchSettlements = (params) =>
  fetchAll(params.status === 'all' ? omit(params, 'status') : params, Settlement, 'SETTLEMENTS');

export const settlementsReducer = makeCollectionReducer('SETTLEMENTS');

export const fetchInstantSettlements = (params) =>
  fetchAll(params, InstantSettlement, 'INSTANT_SETTLEMENTS');
export const instantSettlementsReducer = makeCollectionReducer('INSTANT_SETTLEMENTS');

export const fetchRouteOndemandSettlements = (params) =>
  fetchAll(params, RouteOndemandSettlements, 'ROUTE_ONDEMAND_SETTLEMENTS');
export const routeOndemandSettlementsReducer = makeCollectionReducer('ROUTE_ONDEMAND_SETTLEMENTS');

export const fetchDisputes = (params) => fetchAll(params, Dispute, 'DISPUTES');
export const disputesReducer = makeCollectionReducer('DISPUTES');

export const fetchSubmerchants = (params) =>
  fetchAll(decodeSensitiveFields(params), Submerchant, 'SUB_MERCHANTS');

const submerchantsHandler = {
  'SUB_MERCHANT_CREATE::SUCCESS': (state, action) => {
    if (action.payload?.isInsertTable) {
      return set(state, 'items', unshift(state.items, action.payload));
    }
    return state;
  },
};
export const submerchantsReducer = makeActionCollectionReducer(
  'SUB_MERCHANTS',
  submerchantsHandler,
);

export const fetchRegistrationLinks = (params) =>
  fetchAll(decodeSensitiveFields(params), RegistrationLink, 'REGISTRATION_LINKS');
export const registrationLinksReducer = makeActionCollectionReducer('REGISTRATION_LINKS');

export const fetchTokens = (params) => fetchAll(decodeSensitiveFields(params), Token, 'TOKENS');
export const tokensReducer = makeActionCollectionReducer('TOKENS');

export const fetchEmandatePayments = (params) => {
  params.recurring = 1;
  return fetchAll(decodeSensitiveFields(params), Payment, 'PAYMENTS');
};

const fetchCommissions = (params) => fetchAll(params, Commission, 'COMMISSIONS');
export const fetchEarnings = (params) => fetchCommissions({ ...params, model: 'commission' });
export const fetchSubventions = (params) => fetchCommissions({ ...params, model: 'subvention' });

export const commissionsReducer = makeActionCollectionReducer('COMMISSIONS');
export const commissionsAggregateReducer = makeCollectionReducer('COMMISSION_AGGREGATE');

// Invitations
export const fetchInvitations = (params) => fetchAll(params, Invitation, 'INVITATIONS');
export const invitationsReducer = makeActionCollectionReducer('INVITATIONS');

// Smart Collect
export const fetchSmartCollectPayments = (params) => {
  params.virtual_account = 1;
  return fetchAll(decodeSensitiveFields(params), Payment, 'SC_PAYMENTS');
};
export const smartCollectPaymentsReducer = makeCollectionReducer('SC_PAYMENTS');

// QR codes
export const fetchQRCodesPayments = (params) => {
  return fetchAll(decodeSensitiveFields(params), QRPayment, 'QR_CODE_PAYMENTS');
};

export const updateItemInPayments = (item) => {
  return {
    type: 'PAYMENT_EDIT::SUCCESS',
    payload: item,
  };
};

export const qrCodePaymentsReducer = makeCollectionReducer('QR_CODE_PAYMENTS');
