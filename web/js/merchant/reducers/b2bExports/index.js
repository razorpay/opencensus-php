// actions
import {
  fetchB2bAccounts,
  uploadInvoice,
  uploadInvoiceError,
  uploadInvoiceSuccess,
  uploadInvoicePending,
  activateB2bAccounts,
  getInvoiceDetails,
  getInvoiceDetailsSuccess,
  getInvoiceDetailsPending,
  getInvoiceDetailsError,
} from './actions';

// reducers
import {
  b2bExportsTransactionsReducer,
  b2bExportsAccountsReducer,
  b2bExportsAccountBalanceReducer,
} from './reducer';

export const b2bReducers = {
  b2bExportsTransactionsReducer,
  b2bExportsAccountsReducer,
  b2bExportsAccountBalanceReducer,
};

export const b2bActions = {
  fetchB2bAccounts,
  activateB2bAccounts,
  uploadInvoice,
  uploadInvoiceError,
  uploadInvoiceSuccess,
  uploadInvoicePending,
  getInvoiceDetails,
  getInvoiceDetailsSuccess,
  getInvoiceDetailsPending,
  getInvoiceDetailsError,
};
