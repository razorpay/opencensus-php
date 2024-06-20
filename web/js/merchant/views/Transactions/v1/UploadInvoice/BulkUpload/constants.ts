import { UploadConfigType } from './types';

export const UPLOAD_INVOICE_BILL = 'Upload Invoice Bill';
export const UPLOAD_AIRWAY_BILL = 'Upload Airway Bill';

export const TABS: Array<string> = [UPLOAD_INVOICE_BILL, UPLOAD_AIRWAY_BILL];

//upload configs
export const UPLOAD_CONFIG: UploadConfigType = {
  batchSize: 10,
  retries: 1,
  maxFiles: 500,
};

export const TAB_DETAILS = {
  [UPLOAD_INVOICE_BILL]: {
    fileTypes: ['application/pdf', 'image/jpeg', 'image/png'],
    acceptedTypes: ['jpeg', 'png', 'pdf'],
  },
  [UPLOAD_AIRWAY_BILL]: {
    fileTypes: ['application/pdf'],
    acceptedTypes: ['pdf'],
  },
};

export const AWB_PURPOSE_CODES = ['S0101', 'S0102'];
