import { set, merge } from 'common/utils/immutable';
import { getActionName, makeActionCollectionReducer } from 'merchant/reducers/collection';
import store from 'merchant/store';
import { merchantFetch } from 'merchant/utils/ajax';
import { BATCH_TYPE } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { getRecurringChargeAPILabel } from 'merchant/views/Subscriptions/utils';
import {
  makeEntityReducer,
  entityFetchPendingState,
  entityFetchErrorState,
} from 'merchant_common/reducers/entity';

const REFUND = 'REFUND_BATCHS';
const VIRTUAL_ACCOUNT_BATCHS = 'VIRTUAL_ACCOUNT_BATCHS';

//Spelling it `batchs` instead of `batches` due to makeActionCollectionReducer use of singular namespace. see web/js/merchant_common/reducers/collection.js
const BATCH_DOWNLOAD = 'BATCH_DOWNLOAD';
const ISSUABLE_BATCHES = 'ISSUABLE_BATCHES';
const EDIT_ISSUABLE_BATCHES = 'EDIT_ISSUABLE_BATCHES';

/* Batch Names */
const BATCH = 'BATCH';
const PAYMENT_LINK = 'PAYMENT_LINK';

/* New Batch Action Types */
const NOTIFY_BATCH = 'NOTIFY_BATCH';

const appendBatches = (namespace) => `${namespace}_BATCHS`;
const getCreateActioName = (namespace) => `${namespace}_BATCH_CREATE`;
const getValidateActionName = (namespace) => `${namespace}_BATCH_VALIDATE`;
const getFetchActionName = (namespace) => `${appendBatches(namespace)}_FETCH`;
const getFetchDetailAction = (namespace) => `${namespace}_BATCHS_FETCH_DETAILS`;

const BATCH_DETAILS = getFetchDetailAction(BATCH);
const BATCH_LIST = getFetchActionName(BATCH);
const PAYMENT_LINK_DETAILS = getFetchDetailAction(PAYMENT_LINK);

export const fetchBatchAjax = (id) =>
  merchantFetch(`batches/${id}`).then((response) => ({
    batch: response.data,
  }));

export const fetchBatchesAjax = (params = {}, type) => {
  // add types/type only if type filter not applied
  if (!params.type) {
    params[Array.isArray(type) ? 'types' : 'type'] = type;
  }
  return merchantFetch({
    url: 'batches',
    params,
  });
};

export const fetchIssuableBatchList = (batchIdList) => {
  return {
    type: ISSUABLE_BATCHES,
    payload: merchantFetch({
      url: 'invoices/batches/issuable',
      params: {
        batch_ids: batchIdList,
      },
    }),
  };
};

// Removing 'Issue all links' btn from view
export const editIssuableBatchList = (batchIdToRemove) => {
  return {
    type: EDIT_ISSUABLE_BATCHES,
    batchIdToRemove,
  };
};

/////

function _validateBatch(file, progressTracker, batchType) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);

  return {
    type: getValidateActionName(BATCH),
    payload: merchantFetch({
      url: 'batches/validate',
      method: 'post',
      data: formData,
      onUploadProgress: progressTracker,
    }),
  };
}

/* methods to create actions for validating batch */
export const validateBatch = (batchType) => (file, progressTracker) => {
  return _validateBatch(file, progressTracker, batchType);
};

const _validatePaymentPageBatch = ({ file, progressTracker, batchType, id }) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);
  formData.append('config[payment_page_id]', id);

  return {
    type: getValidateActionName(BATCH),
    payload: merchantFetch({
      url: 'batches/validate',
      method: 'post',
      data: formData,
      onUploadProgress: progressTracker,
    }),
  };
};

/////

/* method to create action for fetching batch list */
const fetchBatches = (batchType, fetchActionName) => (params) => ({
  type: BATCH_LIST || fetchActionName,
  payload: fetchBatchesAjax(params, batchType),
});

/* method to create action for fetching batch details */
const fetchBatchDetails = (batchType, fetchDetailAction) => (params) => ({
  type: BATCH_DETAILS || fetchDetailAction,
  payload: fetchBatchAjax(params.id, batchType),
});

/////

function _createBatch(data, batchType, customBatch, customHeaders) {
  return {
    type: getCreateActioName(customBatch || BATCH),
    payload: merchantFetch({
      url: 'batches',
      method: 'post',
      data: {
        type: batchType,
        ...data,
      },
      headers: customHeaders,
    }).then((response) => response.data),
  };
}

/* method to create action for create batch action */
export const createBatch =
  (batchType, actionPrefix, customHeaders = {}) =>
  (data) => {
    return _createBatch(data, batchType, actionPrefix, customHeaders);
  };

/////

/* method to create action for upload batch action */
// currently used by only refund batches
const uploadBatch = (actionType, batchType) => (file, mode, extraFields) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);

  for (const key in extraFields) {
    if (extraFields.hasOwnProperty(key)) {
      formData.append(key, extraFields[key]);
    }
  }

  return {
    type: actionType,
    payload: merchantFetch({
      url: 'batches',
      method: 'post',
      data: formData,
    }),
  };
};

/////

const _cancelBatch = (batchId, actionType) => {
  return {
    type: actionType,
    payload: merchantFetch({
      url: `invoices/batch/${batchId}/cancel`,
      method: 'post',
    }),
  };
};

export const updateBatchInList = (batch) => {
  const REFUND_BATCH_EDIT = 'REFUND_BATCH_EDIT';
  return {
    type: `${REFUND_BATCH_EDIT}::SUCCESS`,
    payload: batch,
  };
};

const _cancelBatchRefund = (batchId, actionType, prefix) => {
  return {
    type: actionType,
    payload: merchantFetch({
      url: `${prefix}/batch/${batchId}/cancel`,
      method: 'post',
    }).then(() =>
      fetchBatchAjax(batchId).then((r) => {
        r.batch.status = 'created';
        return r.batch;
      }),
    ),
  };
};

export const cancelBatchRefund = (batchId) => {
  const actionType = 'REFUND_BATCH_CANCEL';
  return _cancelBatchRefund(batchId, actionType, 'refunds');
};

export const cancelBatch = (actionType) => (batchId) => {
  return _cancelBatch(batchId, actionType);
};

/////

/* extra methods for more details related to payment link batch */
export const fetchBatchStats = (batchId) =>
  merchantFetch({
    method: 'get',
    url: `batches/${batchId}/stats`,
  });

/* extra methods for more details related to payment link batch */
export const fetchBatchStatsForPLV2 = (batchId, batchType) => {
  const queryParams = {};

  if (batchType) {
    queryParams.batch_type = batchType;
  }

  return merchantFetch({
    method: 'get',
    url: `payment_links/${batchId}/batch`,
    params: queryParams,
  });
};

export const fetchBatchInvoices = (batchId) => {
  const queryParams = {
    batch_id: batchId,
  };

  queryParams.type = 'link';

  return merchantFetch({
    url: 'invoices',
    params: queryParams,
  });
};

const fetchBatchPaymentLinks = (batchId) => merchantFetch(`payment_links?source_id=${batchId}`);

/* actions currently used by only payment link batch */
export const issuePaymentLinkBatch = (batchId, data) => {
  return {
    type: `${PAYMENT_LINK}_ISSUE`,
    payload: merchantFetch({
      method: 'post',
      url: `invoices/batch/${batchId}/issue`,
      data,
    }),
  };
};

export const notifyBatch = (batchId, data) => {
  return {
    type: NOTIFY_BATCH,
    payload: merchantFetch({
      url: `invoices/batch/${batchId}/notify`,
      method: 'put',
      data,
    }),
  };
};

/* common batch actions */
export const batchDownload = (batchId) => {
  return {
    type: BATCH_DOWNLOAD,
    payload: merchantFetch(`batches/${batchId}/download`),
  };
};

/* actions VA batches */
export const fetchVABatches = (params) => {
  return {
    type: getActionName(VIRTUAL_ACCOUNT_BATCHS),
    payload: fetchBatchesAjax(params, 'virtual_account_edit'),
  };
};

/* actions refund batches */
export const fetchRefundBatches = (params) => {
  return {
    type: getActionName(REFUND),
    payload: params.id
      ? fetchBatchAjax(params.id).then(({ batch }) => ({
          data: {
            items: [batch],
          },
        }))
      : fetchBatchesAjax(params, 'refund'),
  };
};

export const uploadRefundBatch = uploadBatch(REFUND, 'refund');

/* action for payment link batch */
export const fetchPaymentLinkBatches = (params) => {
  const user = store.getState().session.user;

  let type;

  if (user.isPaymentlinksV2Enabled) {
    type = ['payment_link', 'payment_link_v2'];
  } else {
    type = 'payment_link';
  }

  //for new batches
  params.with_config = '1';

  return (dispatch) => {
    return dispatch({
      type: BATCH_LIST,
      payload: fetchBatchesAjax(params, type).then((res) => {
        const listOfBatchIds = [];

        res.data.items.forEach((item) => {
          listOfBatchIds.push(item.id);
        });

        dispatch(fetchIssuableBatchList(listOfBatchIds));

        return res;
      }),
    });
  };
};

/* action for payment page batch */
export const fetchPaymentPageBatches = ({ id, params }) => {
  return {
    type: BATCH_LIST,
    payload: merchantFetch({
      url: `payment_pages/${id}/batches`,
      params,
    }),
  };
};

export const notifyPaymentPageBatch = ({ id, batchId, data }) => {
  const { sms_notify, email_notify } = data;
  const payload = {
    notify_on: [],
    batch_id: batchId,
  };
  sms_notify && payload.notify_on.push('sms');
  email_notify && payload.notify_on.push('email');
  return {
    type: NOTIFY_BATCH,
    payload: merchantFetch({
      url: `payment_pages/${id}/fetch_notify_details`,
      method: 'post',
      data: payload,
    }),
  };
};

export const fetchPaymentLinkBatchesDetails = (params) => {
  const user = store.getState().session.user;
  const id = params.id;

  const promise = new Promise((resolve) => {
    return fetchBatchAjax(id).then((batchData) => {
      if (batchData) {
        const batchType = batchData.batch.type;
        const isBatchTypePaymentlinksV2 =
          batchType === 'payment_link_v2' || batchType === BATCH_TYPE;

        const promises = [];

        if (isBatchTypePaymentlinksV2) {
          promises.push(fetchBatchStatsForPLV2(id, batchType));
          promises.push(fetchBatchPaymentLinks(id));
        } else {
          promises.push(fetchBatchStats(id));
          promises.push(fetchBatchInvoices(id, user.isPaymentlinksV2CompatEnabled));
        }

        return Promise.all(promises).then((data) => {
          const [stats, paymentLinksList] = data;

          resolve([batchData, stats, paymentLinksList]);
        });
      }
      return null;
    });
  });

  params.with_config = '1';
  return {
    type: PAYMENT_LINK_DETAILS,
    payload: promise,
  };
};

export const createPaymentLinkBatch = (data) => {
  const user = store.getState().session.user;
  const batchType = user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link';

  return _createBatch(data, batchType);
};

export const createPaymentPageBatch = (data) => {
  return _createBatch(data, 'payment_page');
};

export const validatePaymentPageBatch = (file, progressTracker, id) => {
  return _validatePaymentPageBatch({ file, progressTracker, batchType: 'payment_page', id });
};

export const cancelPaymentLinkBatch = (batchId) => {
  const user = store.getState().session.user;
  const actionType = user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link';

  return _cancelBatch(batchId, actionType);
};

export const validatePaymentLinkBatch = (file, progressTracker) => {
  const user = store.getState().session.user;
  const batchType = user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link';

  return _validateBatch(file, progressTracker, batchType);
};

/* Partner Submerchant linking batches */
export const validatePartnerSubmerchantBatch = validateBatch('partner_submerchant_invite');
export const validatePartnerSubmerchantCapitalBatch = validateBatch(
  'partner_submerchant_invite_capital',
);
export const createPartnerSubmerchantBatch = createBatch('partner_submerchant_invite');
export const createPartnerSubmerchantCapitalBatch = createBatch(
  'partner_submerchant_invite_capital',
);

// new invite flow
export const validatePartnerSubmerchantReferralInvitesBatch = validateBatch(
  'partner_submerchant_referral_invite',
);
export const createPartnerSubmerchantReferralInvitesBatch = createBatch(
  'partner_submerchant_referral_invite',
);

/* direct debit batches */
export const createPaymentsBatch = createBatch('direct_debit');
export const fetchPaymentBatches = fetchBatches('direct_debit');

/* batches for emandate */
export const fetchHostMandateBatches = fetchBatches([
  'recurring_charge',
  'recurring_charge_bulk',
  'auth_link',
  'recurring_charge_axis',
]);
export const fetchHostMandateAuthLinkBatches = fetchBatches('auth_link');
export const createRegistrationLinkBatch = createBatch('auth_link');
export const validateRegistrationLinkBatch = validateBatch('auth_link');
export const validateRefundBatch = validateBatch('refund');
export const createRefundBatch = createBatch('refund', 'REFUND');
export const validateVABatch = validateBatch('virtual_account_edit');
export const createVABatch = createBatch('virtual_account_edit', 'VIRTUAL_ACCOUNT');
export const createRecurringChargeBatch = createBatch(getRecurringChargeAPILabel());
export const validateRecurringChargeBatch = validateBatch(getRecurringChargeAPILabel());
export const createRecurringChargeAxisBatch = createBatch('recurring_charge_axis');
export const validateRecurringChargeAxisBatch = validateBatch('recurring_charge_axis');
export const fetchHostedMandateBatchDetails = fetchBatchDetails();

/* batches for route */
export const fetchAllRouteBatches = fetchBatches([
  'payment_transfer',
  'linked_account_create',
  'transfer_reversal',
]);
export const createTransferBatch = createBatch('payment_transfer');
export const validateTransferBatch = validateBatch('payment_transfer');
export const createLinkedAccountBatch = createBatch('linked_account_create');
export const validateLinkedAccountBatch = validateBatch('linked_account_create');
export const createReversalsBatch = createBatch('transfer_reversal');
export const validateReversalsBatch = validateBatch('transfer_reversal');
export const fetchRouteBatchDetails = fetchBatchDetails();

/* batches for wallet */
export const fetchAllWalletBatches = fetchBatches([
  'create_wallet_accounts',
  'create_wallet_loads',
  'create_wallet_container_loads',
  'create_wallet_user_containers',
  'create_wallet_container_reversals',
]);
export const createWalletAccountsBatch = createBatch('create_wallet_accounts');
export const validateWalletAccountsBatch = validateBatch('create_wallet_accounts');
export const createWalletLoadsBatch = createBatch('create_wallet_loads');
export const validateWalletLoadsBatch = validateBatch('create_wallet_loads');
export const createContainerLoadsBatch = createBatch('create_wallet_container_loads');
export const validateContainerLoadsBatch = validateBatch('create_wallet_container_loads');
export const createUsersBatch = createBatch('create_wallet_user_containers');
export const validateUsersBatch = validateBatch('create_wallet_user_containers');
export const createReversalBatch = createBatch('create_wallet_container_reversals');
export const validateReversalBatch = validateBatch('create_wallet_container_reversals');

/* reducers */
export const refundBatchesReducer = makeActionCollectionReducer(REFUND);
export const batchesReducer = makeActionCollectionReducer(appendBatches(BATCH));
export const virtualAccountBatchesReducer = makeActionCollectionReducer(VIRTUAL_ACCOUNT_BATCHS);

const paymentBatchIdsInitialState = {
  issuableIdList: [],
};

const onPaymentLinkDetails = (state, { payload }) =>
  merge(state, {
    loading: false,
    entity: {
      batch: payload[0].batch,
      stats: payload[1].data.stats,
      paymentlinks: (function makePaymentLinks() {
        let paymentLinks;

        if (payload[2].data.hasOwnProperty('payment_links')) {
          paymentLinks = payload[2].data.payment_links;

          paymentLinks.forEach((item) => {
            if (!item.entity) {
              item.entity = 'invoice';
            }
          });
        } else {
          paymentLinks = payload[2].data.items;
        }

        return paymentLinks;
      })(),
      invoices: payload[2].data.items,
    },
  });

const customBatchDetailsSet = (fetchDetailAction, onSuccess) => ({
  [`${fetchDetailAction}::PENDING`]: entityFetchPendingState,
  [`${fetchDetailAction}::ERROR`]: entityFetchErrorState,
  [`${fetchDetailAction}::SUCCESS`]: onSuccess,
});

export const batchDetailsReducer = makeEntityReducer(BATCH_DETAILS, {
  ...customBatchDetailsSet(PAYMENT_LINK_DETAILS, onPaymentLinkDetails),
});

export function PaymentBatchIdsReducer(state = paymentBatchIdsInitialState, action) {
  switch (action.type) {
    case `${ISSUABLE_BATCHES}::SUCCESS`:
      return set(state, 'issuableIdList', action.payload.data);

    case 'EDIT_ISSUABLE_BATCHES':
      return set(
        state,
        'issuableIdList',
        state.issuableIdList.filter((id) => id !== action.batchIdToRemove),
      );

    default:
      return state;
  }
}
