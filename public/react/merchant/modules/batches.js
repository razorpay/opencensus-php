import ajax from 'merchant/utils/ajax';
import { getActionName, makeCollectionReducer } from 'rzp/modules/collection';

const REFUND = 'REFUND_BATCHES';
const PAYMENT_LINK = 'PAYMENT_LINK_BATCHES';

const fetchBatchAjax = id => {
  return ajax({
    url: '/user/generic',
    appendModeInQueryParam: true,
    data: {
      route_name: 'batch_fetch_by_id',
      url_params: JSON.stringify({
        '{id}': id,
      }),
    },
  }).then(response => {
    return {
      data: {
        items: [response.data],
      },
    };
  });
};

const fetchBatchesAjax = (params, type) => {
  return ajax({
    url: '/user/generic',
    appendModeInQueryParam: true,
    data: {
      route_name: 'batch_fetch_multiple',
      query_params: JSON.stringify({
        ...params,
        type: type,
      }),
    },
  });
};

export const fetchRefundBatches = params => {
  return {
    type: getActionName(REFUND),
    payload: params.id
      ? fetchBatchAjax(params.id)
      : fetchBatchesAjax(params, 'refund'),
  };
};

export const fetchPaymentLinkBatches = params => {
  return {
    type: getActionName(PAYMENT_LINK),
    payload: params.id
      ? fetchBatchAjax(params.id)
      : fetchBatchesAjax(params, 'payment_link'),
  };
};

const uploadBatch = (actionType, batchType) => file => {
  let formData = new FormData();
  formData.append('file', file);
  formData.append('type', batchType);

  return {
    type: actionType,
    payload: ajax({
      url: '/batches',
      method: 'post',
      data: formData,
      processData: false,
      contentType: false,
    }),
  };
};

export const uploadRefundBatch = uploadBatch(REFUND, 'refund');
export const uploadPaymentLinkBatch = uploadBatch(PAYMENT_LINK, 'payment_link');

export const refundBatchesReducer = makeCollectionReducer(REFUND);
export const paymentLinkBatchesReducer = makeCollectionReducer(PAYMENT_LINK);

export const issuePaymentLinkBatch = (batchId, body) => {
  return {
    type: `${PAYMENT_LINK}_ISSUE`,
    payload: ajax({
      method: 'POST',
      url: '/user/generic',
      appendModeInQueryParam: true,
      data: {
        route_name: 'invoice_issue_by_batch',
        url_params: JSON.stringify({
          '{batchId}': batchId,
        }),
        body,
      },
    }),
  };
};
