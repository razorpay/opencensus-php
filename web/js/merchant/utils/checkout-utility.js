import { rupeesToPaise } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';

const fetchOrderId = (type, data) => {
  if (type === 'current') {
    return merchantFetch({
      url: `orders`,
      method: 'post',
      data,
    });
  }

  return merchantFetch({
    url: `fund_addition/initialize`,
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
  const amountInPaise = rupeesToPaise(fieldProps.amountInINR);
  let payload = null;

  if (type === 'current') {
    payload = {
      amount: amountInPaise,
      currency: 'INR',
      payment_capture: 1,
    };
  } else {
    payload = {
      type,
      method: fieldProps.paymentMethod,
      amount: amountInPaise,
    };
  }

  try {
    response = await fetchOrderId(type, payload);
  } catch (e) {
    statusHandler(e);
    return;
  }

  const options = {
    order_id: type === 'current' ? response?.data?.id : response?.data?.order_id,
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
