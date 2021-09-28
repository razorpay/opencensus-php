import { rupeesToPaise } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';

const creditsType = (type) => {
  switch (type) {
    case 'fee':
      return 'fee_credit';
    case 'refund':
      return 'refund_credit';
    case 'reserve':
      return 'reserve_balance';
    default:
      return '';
  }
};

const fetchOrderId = (data) => {
  return merchantFetch({
    url: `orders`,
    method: 'post',
    data,
  });
};

const openCheckout = async (
  fieldProps,
  type,
  user,
  addHandler,
  analyticsHandler,
  statusHandler,
) => {
  let response = {};
  const merchant_id = `acc_${user.current}`;
  const amountInPaise = rupeesToPaise(fieldProps.amountInINR);
  const payload = {
    amount: amountInPaise,
    currency: 'INR',
    payment_capture: 1,
  };
  const orderObject =
    type === 'current'
      ? payload
      : {
          ...payload,
          transfers: [
            {
              amount: amountInPaise,
              currency: 'INR',
              account: merchant_id,
              balance: creditsType(type),
            },
          ],
          notes: {
            description: fieldProps.description,
          },
        };

  try {
    response = await fetchOrderId(orderObject);
  } catch (e) {
    statusHandler(e);
    return;
  }

  const options = {
    order_id: response.data.id,
    amount: amountInPaise,
    description: fieldProps.description,
    amountInINR: fieldProps.amountInINR,
    prefill: {
      name: user.name,
      email: user.email,
      contact: user.contact_mobile,
    },
    notes: {
      dashboard: true,
    },
    handler: function handler(transaction = {}) {
      if (analyticsHandler) {
        analyticsHandler(amountInPaise);
      }
      addHandler(
        {
          amount: amountInPaise,
          razorpay_payment_id: transaction.razorpay_payment_id,
        },
        type,
      );
    },
  };

  new Promise((resolve, reject) => {
    try {
      const rzp = new window.Razorpay(options);
      rzp.open();
      resolve();
    } catch (e) {
      reject(`An error occured - ${e.message}`);
    }
  }).catch((error) => {
    statusHandler({
      type: 'error',
      message: error,
    });
  });
};

export { openCheckout };
