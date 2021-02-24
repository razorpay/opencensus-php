import store from 'merchant/store';
import { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';
import Invoice from 'merchant/models/Invoice';

import { transformPLDetails_NewToOld } from 'merchant/views/PaymentLinks/PaymentLinks/js/transformer';

import { fetchUserDetailsById } from 'merchant/reducers/session';

export const PL_UPDATE = 'PL_UPDATE';
const PL_FETCH = 'PL_FETCH';
const PL_CANCEL = 'PL_CANCEL';
const SMS_SEND = 'SMS_SEND';
const EMAIL_SEND = 'EMAIL_SEND';

export const fetchPLCount = (data) => {
  const user = store.getState().session.user;
  const url = user.isPaymentlinksV2Enabled ? 'payment_links_count' : 'invoices-count';

  const reqPayload = {
    url,
  };

  if (!user.isPaymentlinksV2Enabled) {
    reqPayload.data = data;
  }

  return merchantFetch(reqPayload);
};

export const fetchPLRemindersList = (id) => {
  const user = store.getState().session.user;
  const url = user.isPaymentlinksV2Enabled
    ? `payment_links/${id}/reminders/next_run`
    : `reminders/next_run/invoice/${id}`;

  return merchantFetch({
    url,
  });
};
export const fetchPaymentLinkV2Details = (paymentLinkId) => {
  return merchantFetch({
    url: `payment_links/${paymentLinkId}`,
  });
};

export const fetchPaymentLinkDetails = (paymentLinkId) => {
  let payload;
  const user = store.getState().session.user;

  if (user.isPaymentlinksV2Enabled) {
    // TODO: remove once proper fix in backend, api should give proper response for IDs with inv_ as well
    if (paymentLinkId.startsWith('inv_')) {
      paymentLinkId = paymentLinkId.replace('inv', 'plink');
    }

    const reqPayload = {
      url: `payment_links/${paymentLinkId}`,
      params: { expand: ['payments', 'user', 'reminder_status'] },
    };

    payload = merchantFetch(reqPayload).then((resp) => {
      if (resp.data) {
        const userId = resp.data.user_id;

        const paymentLink = {
          ...resp.data,
        };

        if (userId) {
          return fetchUserDetailsById(userId)
            .then((userResp) => {
              if (userResp.data) {
                paymentLink.user = userResp.data;
              }

              return transformPLDetails_NewToOld(paymentLink);
            })
            .catch((err) => {
              return transformPLDetails_NewToOld(paymentLink);
            });
        }

        return transformPLDetails_NewToOld(paymentLink);
      }

      return resp;
    });
  } else {
    let paymentlink = new Invoice();

    payload = paymentlink
      .fetch(paymentLinkId, {}, { expand: ['payments', 'user', 'reminder_status'] })
      .then((data) => {
        if (data) {
          const userId = data.user_id;

          const paymentLink = {
            ...data,
          };

          if (userId) {
            return fetchUserDetailsById(userId)
              .then((userResp) => {
                if (userResp.data) {
                  paymentLink.user = userResp.data;
                }

                return paymentLink;
              })
              .catch((err) => {
                return paymentLink;
              });
          }

          return paymentLink;
        }

        return data;
      });
  }

  return {
    type: PL_FETCH,
    payload,
  };
};

export const notifyCustomer = (paymentLink, medium) => {
  let payload;
  const user = store.getState().session.user;

  if (user.isPaymentlinksV2Enabled) {
    const reqPayload = {
      url: `payment_links/${paymentLink.id}/notify_by/${medium}`,
      method: 'post',
    };

    payload = merchantFetch(reqPayload).then((resp) => {
      if (resp.data) {
        return transformPLDetails_NewToOld(resp.data);
      }

      return resp;
    });
  } else {
    let _paymentlink = new Invoice(paymentLink);

    payload = _paymentlink.notify(medium);
  }

  return {
    type: medium === 'sms' ? SMS_SEND : EMAIL_SEND,
    payload,
  };
};

export const cancelPaymentLink = (paymentLink) => {
  let payload;
  const user = store.getState().session.user;

  if (user.isPaymentlinksV2Enabled) {
    const reqPayload = {
      url: `payment_links/${paymentLink.id}/cancel`,
      method: 'post',
    };

    payload = merchantFetch(reqPayload).then((resp) => {
      if (resp.data) {
        return transformPLDetails_NewToOld(resp.data);
      }

      return resp.data;
    });
  } else {
    let _paymentLink = new Invoice(paymentLink);

    payload = _paymentLink.cancel();
  }

  return {
    type: PL_CANCEL,
    payload,
  };
};

let initialState = {
  loading: true,
  paymentlink: {
    customer_details: {},
    line_items: [],
    notes: {},
    payments: [],
  },
  error: null,
};

export default function (state = initialState, action) {
  switch (action.type) {
    case `${PL_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case PL_UPDATE:
    case `${PL_CANCEL}::SUCCESS`:
    case `${PL_FETCH}::SUCCESS`: {
      return merge(state, {
        loading: false,
        paymentlink: action.payload,
        error: null,
      });
    }

    case `${PL_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        paymentlink: initialState.paymentlink,
      });

    case `${SMS_SEND}::SUCCESS`:
      return set(state, 'paymentlink.sms_status', 'sent');

    case `${EMAIL_SEND}::SUCCESS`:
      return set(state, 'paymentlink.email_status', 'sent');

    default:
      return state;
  }
}
