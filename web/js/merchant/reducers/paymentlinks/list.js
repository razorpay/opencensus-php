import { set, merge, unshift, remove } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

import store from 'merchant/store';
import {
  transformPLDetails_NewToOld,
  transformPLListFilters_NewToOld,
} from 'merchant/views/PaymentLinks/PaymentLinks/js/transformer';
import Invoice from 'merchant/models/Invoice';

import { PL_UPDATE } from './details';

export const PAYMENTLINKS_FETCH = 'PAYMENTLINKS_FETCH';
export const PL_CREATE_UPDATE_LIST = 'PL_CREATE_UPDATE_LIST';
export const PL_EDIT_UPDATE_LIST = 'PL_EDIT_UPDATE_LIST';

export const fetchPaymentLinks = (params) => {
  const user = store.getState().session.user;
  let url, queryParams;

  if (user.isPaymentlinksV2Enabled) {
    url = 'payment_links';

    queryParams = transformPLListFilters_NewToOld(params);
  } else {
    url = 'invoices';

    const { id, ...restParams } = params;
    queryParams = restParams;

    if (id) {
      url += `/${id}`;
    }
  }

  const payload = merchantFetch({
    url,
    params: queryParams,
  }).then((resp) => {
    if (resp.data) {
      if (user.isPaymentlinksV2Enabled) {
        resp.data.items = resp.data.payment_links.map((paymentlink) =>
          transformPLDetails_NewToOld(paymentlink),
        );

        delete resp.data.payment_links;

        return resp;
      } else if (!resp.data.items) {
        // Handling id case, otherwise it doesn't return items array but only details of the id
        resp = {
          data: {
            items: [resp.data],
          },
        };
      }
    }

    return resp;
  });

  return {
    type: PAYMENTLINKS_FETCH,
    payload,
  };
};

/* Hook to update newly-created/edited payment link in redux list*/
export const updatePLInReduxList = (respPayload, isNew) => {
  let payload;
  const user = store.getState().session.user;

  if (user.isPaymentlinksV2Enabled) {
    payload = respPayload.data;
  } else {
    payload = new Invoice(respPayload.data).deserialize();
  }

  return (dispatch) => {
    // Update list view
    dispatch({
      type: isNew ? `${PL_CREATE_UPDATE_LIST}::SUCCESS` : `${PL_EDIT_UPDATE_LIST}::SUCCESS`,
      payload,
    });

    // Update details view
    dispatch({
      type: PL_UPDATE,
      payload: respPayload.data,
    });
  };
};

let initialState = {
  loading: true,
  paymentlinks: [],
  count: 0,
};

export default function (state = initialState, action) {
  switch (action.type) {
    case `${PAYMENTLINKS_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        paymentlinks: [],
      });

    case `${PAYMENTLINKS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        paymentlinks: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${PAYMENTLINKS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    // Update the list view after creation / edit of payment link

    case `${PL_CREATE_UPDATE_LIST}::SUCCESS`:
      return set(state, 'paymentlinks', unshift(state.paymentlinks, action.payload));

    case `${PL_EDIT_UPDATE_LIST}::SUCCESS`:
      let paymentlinkIndex = state.paymentlinks.findIndex(
        (paymentlink) => paymentlink.id === action.payload.id,
      );

      return set(state, `paymentlinks.${paymentlinkIndex}`, action.payload);

    default:
      return state;
  }
}
