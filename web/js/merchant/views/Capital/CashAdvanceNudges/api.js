import Repayments from 'merchant/models/Capital/Repayments';
import { loadCheckoutScript } from '../utils';
import {
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
  COLLECTIONS_PRODUCT_TYPES,
} from '../CashAdvance/constants';
import { INR_CURRENCY } from './constants';

export function updateRepaymentData(data, onResolve, onReject) {
  const repayment = new Repayments();
  return repayment.updateRepayment(data).then(onResolve).catch(onReject);
}

export const handleRepayment = (user, nextRepayAmount, updateRepaymentcallback, onError) => {
  const RepaymentInstance = new Repayments();

  const requests = [
    loadCheckoutScript(),
    RepaymentInstance.createRepayment({
      credit_id: user.current,
      product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      currency: INR_CURRENCY,
      payment_reference_type: COLLECTIONS_PAYMENT_REFERENCE_TYPE.ORDER,
      amount: Number(nextRepayAmount),
    }),
  ];

  return Promise.all(requests)
    .then(([_, repaymentDetails]) => {
      const { data: { payment_reference_id: order_id } = {} } = repaymentDetails;
      if (!order_id) return Promise.reject(new Error('No Order Id found'));

      return new Promise((resolve, reject) => {
        if (window) {
          const razorpayInstance = new window.Razorpay({
            order_id,
            prefill: {
              name: user.name,
              email: user.email,
              contact: user.contact_mobile,
            },
            handler: (response) => {
              updateRepaymentcallback([response, resolve, reject]);
            },
            modal: {
              ondismiss: reject,
            },
          });
          razorpayInstance.open();
        } else {
          reject();
        }
      });
    })
    .catch(onError);
};
