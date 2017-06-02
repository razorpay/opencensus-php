import { set, merge, unshift, remove } from 'rzp/utils/immutable';
import Invoice from 'merchant/models/Invoice';

export const INVOICES_FETCH = 'INVOICES_FETCH';
export const INVOICE_CREATE = 'INVOICE_CREATE';
export const INVOICE_EDIT = 'INVOICE_EDIT';
export const INVOICE_DELETED = 'INVOICE_DELETED';

export const fetchInvoices = params => {
  return dispatch => {
    let invoice = new Invoice();
    return dispatch({
      type: INVOICES_FETCH,
      payload: invoice.fetchAll(params),
    });
  };
};

export const saveInvoice = params => {
  return dispatch => {
    let invoice = new Invoice(params);
    return dispatch({
      type: invoice.isNew ? INVOICE_CREATE : INVOICE_EDIT,
      payload: invoice.save(),
    });
  };
};

export const deleteInvoice = params => {
  return dispatch => {
    let invoice = new Invoice(params);
    return invoice.delete().then(() => {
      dispatch({
        type: INVOICE_DELETED,
        payload: invoice,
      });
    });
  };
};

let initialState = {
  loading: true,
  invoices: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${INVOICES_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${INVOICES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        invoices: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${INVOICES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case `${INVOICE_CREATE}::SUCCESS`:
      return set(state, 'invoices', unshift(state.invoices, action.payload));

    case `${INVOICE_EDIT}::SUCCESS`:
      let invoiceIndex = state.invoices.findIndex(
        invoice => invoice.id === action.payload.id
      );
      return set(state, `invoices.${invoiceIndex}`, action.payload);

    case INVOICE_DELETED:
      var invoicesList = remove(
        state.invoices,
        invoice => invoice.id === action.payload.id
      );
      return set(state, 'invoices', invoicesList);

    default:
      return state;
  }
}
