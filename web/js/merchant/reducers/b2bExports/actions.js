// resources
import B2bExportsResource from 'merchant/models/B2bExports';
import B2bExportsPayments from 'merchant/models/B2bExportsPayments';

// constants
import {
  B2B_EXPORTS_FETCH_ACCOUNTS,
  B2B_EXPORTS_UPLOAD_INVOICE,
  B2B_EXPORTS_ACTIVATE_ACCOUNTS,
  B2B_EXPORTS_GET_INVOICE_DETAILS,
  B2B_EXPORTS_SET_FEATURE,
  B2B_EXPORTS_GET_BALANCE,
  B2B_EXPORTS_GET_BENEFICIARY,
  B2B_EXPORTS_CREATE_PAYOUT,
} from './constants';

const fetchB2bAccounts = () => {
  const resource = new B2bExportsResource();
  return {
    type: B2B_EXPORTS_FETCH_ACCOUNTS,
    payload: resource.accounts(),
  };
};

const activateAccountPending = (payload) => ({
  type: `${B2B_EXPORTS_ACTIVATE_ACCOUNTS}::PENDING`,
  payload,
});

const activateAccountSuccess = (payload) => ({
  type: `${B2B_EXPORTS_ACTIVATE_ACCOUNTS}::SUCCESS`,
  payload,
});

const activateAccountError = (payload) => ({
  type: `${B2B_EXPORTS_ACTIVATE_ACCOUNTS}::ERROR`,
  payload,
});

const uploadInvoicePending = (payload) => ({
  type: `${B2B_EXPORTS_UPLOAD_INVOICE}::PENDING`,
  payload,
});

const uploadInvoiceSuccess = (payload) => ({
  type: `${B2B_EXPORTS_UPLOAD_INVOICE}::SUCCESS`,
  payload,
});

const uploadInvoiceError = (payload) => ({
  type: `${B2B_EXPORTS_UPLOAD_INVOICE}::ERROR`,
  payload,
});

const uploadInvoice = (id, file) => {
  const resource = new B2bExportsPayments();

  const formData = new FormData();
  formData.append('file', file);
  formData.append('purpose', 'b2b_export_invoice');

  return resource.uploadInvoice(id, formData);
};
const setFeatureFlag = (data) => {
  return {
    type: B2B_EXPORTS_SET_FEATURE,
    payload: data,
  };
};

const getInvoiceDetailsPending = (payload) => {
  return {
    type: `${B2B_EXPORTS_GET_INVOICE_DETAILS}::STATUS_PENDING`,
    payload,
  };
};
const getInvoiceDetailsError = (payload) => {
  return {
    type: `${B2B_EXPORTS_GET_INVOICE_DETAILS}::STATUS_ERROR`,
    payload,
  };
};
const getInvoiceDetailsSuccess = (payload) => {
  return {
    type: `${B2B_EXPORTS_GET_INVOICE_DETAILS}::STATUS_SUCCESS`,
    payload,
  };
};
const getInvoiceDetails = (id) => {
  const resource = new B2bExportsPayments();
  return resource.getInvoice(id);
};

const uploadB2bInvoice = (file) => {
  const resource = new B2bExportsResource();
  return {
    type: B2B_EXPORTS_UPLOAD_INVOICE,
    payload: resource.uploadInvoice(file),
  };
};

const fetchAccountBalance = (currency) => {
  const resource = new B2bExportsResource();

  return {
    type: B2B_EXPORTS_GET_BALANCE,
    payload: resource.getBalance(currency),
  };
};

const fetchBeneficiaryDetails = () => {
  const resource = new B2bExportsResource();

  return resource.getBeneficiaryDetails();
};

const fetchBeneficiaryDetailsPending = (payload) => {
  return {
    type: `${B2B_EXPORTS_GET_BENEFICIARY}::PENDING`,
    payload,
  };
};

const fetchBeneficiaryDetailsSuccess = (payload) => {
  return {
    type: `${B2B_EXPORTS_GET_BENEFICIARY}::SUCCESS`,
    payload,
  };
};

const fetchBeneficiaryDetailsError = (payload) => {
  return {
    type: `${B2B_EXPORTS_GET_BENEFICIARY}::ERROR`,
    payload,
  };
};

const createPayout = (data) => {
  const resource = new B2bExportsResource();

  return resource.createPayout(data);
};

const createPayoutPending = () => {
  return {
    type: `${B2B_EXPORTS_CREATE_PAYOUT}::PENDING`,
  };
};

const createPayoutSuccess = () => {
  return {
    type: `${B2B_EXPORTS_CREATE_PAYOUT}::SUCCESS`,
  };
};

export {
  uploadInvoice,
  uploadInvoiceError,
  uploadInvoiceSuccess,
  uploadInvoicePending,
  getInvoiceDetailsSuccess,
  getInvoiceDetailsPending,
  getInvoiceDetailsError,
  getInvoiceDetails,
  fetchB2bAccounts,
  setFeatureFlag,
  uploadB2bInvoice,
  activateAccountError,
  activateAccountPending,
  activateAccountSuccess,
  fetchAccountBalance,
  createPayout,
  createPayoutPending,
  createPayoutSuccess,
  fetchBeneficiaryDetails,
  fetchBeneficiaryDetailsPending,
  fetchBeneficiaryDetailsSuccess,
  fetchBeneficiaryDetailsError,
};
