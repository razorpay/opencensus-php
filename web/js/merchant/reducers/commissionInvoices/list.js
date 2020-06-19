import { set, merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const COMMISSION_INVOICES_FETCH = 'COMMISSION_INVOICES_FETCH';
const COMMISSION_INVOICES_LIST_UPDATE = 'COMMISSION_INVOICES_LIST_UPDATE';

export const fetchCommissionInvoices = params => {
  const url = `commissions/invoice/fetch/bulk`;
  const payload = merchantFetch({ url, params }).then(res => {
    if (res && res.data) {
      return res;
    }
  });

  return {
    type: COMMISSION_INVOICES_FETCH,
    payload,
  };
};

export const updateCommissionInvoiceInList = commissionInvoice => {
  return {
    type: COMMISSION_INVOICES_LIST_UPDATE,
    payload: commissionInvoice,
  };
};

let initialState = {
  loading: true,
  commissionInvoices: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${COMMISSION_INVOICES_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        commissionInvoices: [],
      });

    case `${COMMISSION_INVOICES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        commissionInvoices: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${COMMISSION_INVOICES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case `${COMMISSION_INVOICES_LIST_UPDATE}`:
      let commissionInvoiceIndex = state.commissionInvoices.findIndex(
        commissionInvoice => commissionInvoice.id === action.payload.id
      );

      return set(
        state,
        `commissionInvoices.${commissionInvoiceIndex}`,
        action.payload
      );
    default:
      return state;
  }
}
