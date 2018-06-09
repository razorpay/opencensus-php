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

/* Hook to update newly created payment link in redux list*/
export const savePLInReduxList = newInvoice => {
  return {
    type: `${INVOICE_CREATE}::SUCCESS`,
    payload: new Invoice(newInvoice.data),
  };
};

/* Hook to update edited invoice in redux list */
export const editPLInReduxList = invoice => {
  return {
    type: `${INVOICE_EDIT}::SUCCESS`,
    payload: new Invoice(invoice.data),
  };
};

/* Hook to update newly created reusable link in redux list */
export const saveRPLInReduxList = newLink => {
  return {
    type: 'RPL_CREATE',
    payload: newLink,
  };
};

/* Hook to populate reusable links list fetched separately from api */
export const populateRPLReduxList = newLinksList => {
  return {
    type: 'RPL_FETCH',
    payload: newLinksList,
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
  reusableLinks: [],
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

    case 'RPL_CREATE':
      return set(
        state,
        'reusableLinks',
        unshift(state.reusableLinks, action.payload)
      );

    case 'RPL_FETCH':
      return merge(state, {
        reusableLinks: action.payload.data.items,
      });

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
