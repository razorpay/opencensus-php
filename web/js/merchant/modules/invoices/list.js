import { set, merge, unshift, remove } from 'rzp/utils/immutable';
import Invoice from 'merchant/models/Invoice';

export const INVOICES_FETCH = 'INVOICES_FETCH';
export const INVOICE_CREATE = 'INVOICE_CREATE';
export const INVOICE_EDIT = 'INVOICE_EDIT';
export const INVOICE_DELETED = 'INVOICE_DELETED';

export const fetchInvoices = params => {
  let invoice = new Invoice();
  return {
    type: INVOICES_FETCH,
    payload: invoice.fetchAll(params),
  };
};

export const saveInvoice = params => {
  let invoice = new Invoice(params);
  return {
    type: invoice.isNew ? INVOICE_CREATE : INVOICE_EDIT,
    payload: invoice.save(),
  };
};

/* Hook to update invoice list from invoice data fetched separately */
export const updatePaymentLinksList = newInvoice => {
  return {
    type: `${INVOICE_CREATE}::SUCCESS`,
    payload: new Invoice(newInvoice.data),
  };
};

export const deleteInvoice = params => {
  let invoice = new Invoice(params);
  return {
    type: INVOICE_DELETED,
    payload: invoice.delete(),
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
