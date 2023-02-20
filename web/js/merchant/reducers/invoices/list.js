import { set, merge, unshift, remove } from 'common/utils/immutable';
import Invoice from 'merchant/models/Invoice';
import { fetchStorefrontPaymentsList } from 'merchant/views/PaymentPages/PaymentPages/model';
import { decodeSensitiveFields } from 'common/utils/rzp-utils';

export const INVOICES_FETCH = 'INVOICES_FETCH';
export const INVOICE_CREATE = 'INVOICE_CREATE';
export const INVOICE_EDIT = 'INVOICE_EDIT';
export const INVOICE_DELETED = 'INVOICE_DELETED';
export const PP_STOREFRONT_PAYMENTS_FETCH = 'PP_STOREFRONT_PAYMENTS_FETCH';

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

export const populateStorefrontReduxList = (newLinksList) => {
  return {
    type: 'PP_STOREFRONT_FETCH',
    payload: newLinksList,
  };
};

export const fetchStorefrontPayments = (id, data) => {
  return {
    type: PP_STOREFRONT_PAYMENTS_FETCH,
    payload: fetchStorefrontPaymentsList(id, data),
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
  blacklistQueryParams: ['source'],
  storefrontPages: [],
  totalStorefrontLength: 0,
  totalPaymentPagesLength: 0,
  storefrontPayments: {
    items: [],
    loading: false,
    error: null,
  },
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
        totalPaymentPagesLength: action.payload.data?.total || action.payload.data.items.length,
        loading: false,
      });
    case 'PP_STOREFRONT_FETCH':
      return merge(state, {
        storefrontPages: action.payload.data.items,
        totalStorefrontLength: action.payload.data?.total || action.payload.data.items.length,
        loading: false,
      });

    case `${PP_STOREFRONT_PAYMENTS_FETCH}::PENDING`:
      return set(state, 'storefrontPayments', {
        loading: true,
        items: [],
        error: '',
      });

    case `${PP_STOREFRONT_PAYMENTS_FETCH}::SUCCESS`: {
      return set(state, 'storefrontPayments', {
        loading: false,
        items: action.payload.data?.items || [],
        error: '',
      });
    }

    case `${PP_STOREFRONT_PAYMENTS_FETCH}::ERROR`:
      return set(state, 'storefrontPayments', {
        loading: false,
        items: [],
        error: action.payload.errors,
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
