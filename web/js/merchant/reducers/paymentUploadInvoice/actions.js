// models
import PaymentUploadInvoice from 'merchant/models/PaymentUploadInvoice';

// constants
import { UPLOAD_PAYMENT_INVOICE, UPLOAD_PAYMENT_VIEW_INVOICE } from './constants';

// actions
export const uploadInvoicePending = (payload) => ({
  type: `${UPLOAD_PAYMENT_INVOICE}::PENDING`,
  payload,
});

export const uploadInvoiceSuccess = (payload) => ({
  type: `${UPLOAD_PAYMENT_INVOICE}::SUCCESS`,
  payload,
});

export const uploadInvoiceError = (payload) => ({
  type: `${UPLOAD_PAYMENT_INVOICE}::ERROR`,
  payload,
});

export const uploadInvoice = (id, file, purpose = 'opgsp_invoice') => {
  const resource = new PaymentUploadInvoice();

  const formData = new FormData();
  formData.append('file', file);
  formData.append('purpose', purpose);

  return resource.uploadInvoice(id, formData);
};

export const viewInvoicePending = (payload) => ({
  type: `${UPLOAD_PAYMENT_VIEW_INVOICE}::PENDING`,
  payload,
});

export const viewInvoiceSuccess = (payload) => ({
  type: `${UPLOAD_PAYMENT_VIEW_INVOICE}::SUCCESS`,
  payload,
});

export const viewInvoiceError = (payload) => ({
  type: `${UPLOAD_PAYMENT_VIEW_INVOICE}::ERROR`,
  payload,
});

export const viewInvoice = (id) => {
  const resource = new PaymentUploadInvoice();
  return resource.getInvoice(id);
};
