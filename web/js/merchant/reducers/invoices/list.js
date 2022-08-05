import { set, merge, unshift, remove } from 'common/utils/immutable';
import Invoice from 'merchant/models/Invoice';
import { decodeSensitiveFields } from 'common/utils/rzp-utils';

export const INVOICES_FETCH = 'INVOICES_FETCH';
export const INVOICE_CREATE = 'INVOICE_CREATE';
export const INVOICE_EDIT = 'INVOICE_EDIT';
export const INVOICE_DELETED = 'INVOICE_DELETED';

export const fetchInvoices = (params) => {
  const invoice = new Invoice();
  return {
    type: INVOICES_FETCH,
    payload: invoice.fetchAll(decodeSensitiveFields(params)),
  };
};

export const saveInvoice = (params, headers = {}, isIntentDuplicate) => {
  const invoice = new Invoice(params);

  return {
    type: invoice.isNew ? INVOICE_CREATE : INVOICE_EDIT,
    payload: invoice.save(
      null,
      {
        headers,
      },
      isIntentDuplicate,
    ),
  };
};

/* Hook to update newly created payment-page in redux list */
export const updatePPInReduxList = (newLink, isNew) => {
  return {
    type: isNew ? 'PP_CREATE' : 'PP_EDIT',
    payload: newLink,
  };
};

/* Hook to populate payment-page list fetched separately from api */
export const populateRPLReduxList = (newLinksList) => {
  return {
    type: 'PP_FETCH',
    payload: newLinksList,
  };
};

export const deleteInvoice = (params) => {
  const invoice = new Invoice(params);
  return {
    type: INVOICE_DELETED,
    payload: invoice.delete(),
  };
};

const initialState = {
  loading: true,
  invoices: [],
  paymentPages: [],
  count: 0,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${INVOICES_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        invoices: [],
      });

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

    case 'PP_CREATE':
      return set(state, 'paymentPages', unshift(state.paymentPages, action.payload));

    case 'PP_EDIT':
      return set(
        state,
        `paymentPages.${state.paymentPages.findIndex((entity) => entity.id === action.payload.id)}`,
        action.payload,
      );

    case 'PP_FETCH':
      return merge(state, {
        paymentPages: action.payload.data.items,
        loading: false,
      });

    case `${INVOICE_EDIT}::SUCCESS`:
      return set(
        state,
        `invoices.${state.invoices.findIndex((invoice) => invoice.id === action.payload.id)}`,
        action.payload,
      );

    case INVOICE_DELETED:
      return set(
        state,
        'invoices',
        remove(state.invoices, (invoice) => invoice.id === action.payload.id),
      );

    default:
      return state;
  }
};
