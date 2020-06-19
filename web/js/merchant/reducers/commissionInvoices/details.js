import { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';

const COMMISSION_INVOICE_FETCH = 'COMMISSION_INVOICE_FETCH';
const COMMISSION_INVOICE_UPDATE = 'COMMISSION_INVOICE_UPDATE';

export const fetchCommissionInvoiceDetails = commInvoiceId => {
  const url = `commissions/invoice/${commInvoiceId}`;
  const payload = merchantFetch({ url }).then(res => res);

  return {
    type: COMMISSION_INVOICE_FETCH,
    payload,
  };
};

export const updateCommissionInvoiceDetail = commissionInvoice => {
  return {
    type: COMMISSION_INVOICE_UPDATE,
    payload: commissionInvoice,
  };
};

let initialState = {
  loading: true,
  commissionInvoice: {},
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${COMMISSION_INVOICE_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${COMMISSION_INVOICE_FETCH}::SUCCESS`: {
      return merge(state, {
        loading: false,
        commissionInvoice: action.payload.data,
        error: null,
      });
    }

    case `${COMMISSION_INVOICE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        commissionInvoice: initialState.commissionInvoice,
      });

    case `${COMMISSION_INVOICE_UPDATE}`:
      return merge(state, {
        commissionInvoice: {
          ...state.commissionInvoice,
          status: 'under_review',
        },
      });

    default:
      return state;
  }
}
