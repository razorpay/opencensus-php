import api from '../../api';
import {
  COLLECTIONS_PRODUCT_TYPE,
  COLLECTIONS_PRODUCT_ENTITY_TYPE,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
} from '../../constants';

export const loadCheckoutScript = () => {
  return new Promise((resolve, reject) => {
    if (window.Razorpay) return resolve();

    const script = document.createElement('script');
    script.src = 'https://checkout.razorpay.com/v1/checkout.js';
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
    return null;
  });
};

function createRepayment(payload) {
  const { creditId, disbursalId, amount, type } = payload;

  return api.createRepaymentOrder({
    currency: 'INR',
    credit_id: creditId,
    product_entity_reference_id: disbursalId,
    product_entity_type: disbursalId ? COLLECTIONS_PRODUCT_ENTITY_TYPE.DISBURSEMENT : null,
    product_type: COLLECTIONS_PRODUCT_TYPE.LOANS,
    amount: Number(amount),
    payment_reference_type: type,
  });
}

function updateRepayment(data, onResolve, onReject) {
  return api.updateRepayment(data).then(onResolve).catch(onReject);
}

export function handleCheckoutPayment({ dispatch, loanData, amount }) {
  const { creditId, disbursalId } = loanData;
  const type = COLLECTIONS_PAYMENT_REFERENCE_TYPE.ORDER;
  const requests = [
    loadCheckoutScript(),
    createRepayment({
      creditId,
      disbursalId,
      amount,
      type,
    }),
  ];

  return Promise.all(requests)
    .then(([_, repaymentDetails]) => {
      const { data: { payment_reference_id: order_id } = {} } = repaymentDetails;
      if (!order_id) return Promise.reject(new Error('No Order Id found'));

      return new Promise((resolve, reject) => {
        //eslint-disable-next-line
        const razorpayInstance = new Razorpay({
          order_id,
          handler: (response) => updateRepayment(response, resolve, reject),
          modal: {
            ondismiss: reject,
          },
        });
        razorpayInstance.open();
      });
    })
    .then((res) => {
      dispatch({
        type: 'REPAYMENT_SUCCESS',
        payload: { id: res.data.id, amount, type, success: true },
      });
    })
    .catch(() => {
      dispatch({
        type: 'REPAYMENT_FAILURE',
        payload: { amount, type, success: false },
      });
      throw new Error('Payment Failure');
    });
}

export function handleSettlementBalancePayment({ dispatch, loanData, amount }) {
  const { creditId, disbursalId } = loanData;
  const type = COLLECTIONS_PAYMENT_REFERENCE_TYPE.CREDIT_REPAYMENT;
  return createRepayment({
    creditId,
    disbursalId,
    amount,
    type,
  })
    .then((res) =>
      dispatch({
        type: 'REPAYMENT_SUCCESS',
        payload: { id: res.data.id, amount, type, success: true },
      }),
    )
    .catch(() => {
      dispatch({
        type: 'REPAYMENT_FAILURE',
        payload: { amount, type, success: false },
      });
      throw new Error('Payment Failure');
    });
}

export function getPaymentStatusMessage(success, method) {
  if (success) {
    return `Your payment is successfully paid via ${method}`;
  } else {
    return `Your payment using ${method} has failed due to some internal error.`;
  }
}

export function handleRepayment(payload, loanData, dispatch) {
  const { viaNetbanking, viaBalance } = payload;

  if (viaNetbanking && viaBalance) {
    return handleSettlementBalancePayment({
      loanData,
      amount: viaBalance,
      dispatch,
    }).finally(() => handleCheckoutPayment({ loanData, amount: viaNetbanking, dispatch }));
  } else if (viaNetbanking) {
    return handleCheckoutPayment({
      loanData,
      amount: viaNetbanking,
      dispatch,
    });
  } else {
    return handleSettlementBalancePayment({
      loanData,
      amount: viaBalance,
      dispatch,
    });
  }
}
