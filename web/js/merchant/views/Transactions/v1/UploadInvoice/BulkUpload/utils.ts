import { RefObject } from 'react';

import {
  JPMC_FEATURE_FLAG,
  UPLOAD_INVOICES_TYPE,
} from 'merchant/views/Transactions/v1/UploadInvoice/components/constants';

import {
  AWB_PURPOSE_CODES,
  TABS,
  TAB_DETAILS,
  UPLOAD_AIRWAY_BILL,
  UPLOAD_CONFIG,
  UPLOAD_INVOICE_BILL,
} from './constants';
import { saveInvoice } from './services';
import { BatchError, BulkUploadResponse, TransfromDroppedFilesType } from './types';

const { batchSize } = UPLOAD_CONFIG;

export const transfromDroppedFiles = (data: DataTransfer, tabIndex): TransfromDroppedFilesType => {
  const acceptedTypes = TAB_DETAILS[TABS[tabIndex]].fileTypes;
  const transformedFiles: Array<File> = [];
  const clientErrors: Array<BatchError> = [];
  Object.keys(data).forEach((key) => {
    const file = data[key].getAsFile();
    if (!acceptedTypes.includes(file?.type)) {
      clientErrors.push({
        fileName: file.name,
        size: Math.floor(file?.size / 1000),
        message: `${file?.type} is not an accepted file type`,
      });
    } else {
      transformedFiles.push(file);
    }
  });
  return { transformedFiles, clientErrors };
};

export const allSettled = (
  promises: Array<Promise<unknown>>,
): Promise<Array<BulkUploadResponse>> => {
  const mappedPromises = promises.map((p) => {
    return p
      .then((value) => {
        return {
          status: 'fulfilled',
          value,
        };
      })
      .catch((reason) => {
        return {
          status: 'rejected',
          reason,
        };
      });
  });
  return Promise.all(mappedPromises);
};

export const getProgressMessage = (
  fileIndex = 0,
  files: Array<File> = [],
  clientErrors: Array<BatchError> = [],
): string => {
  const uploadCount = fileIndex + batchSize + clientErrors.length;
  const totalCount = files.length + clientErrors.length;
  if (fileIndex + batchSize >= files.length) {
    return `Uploading ${totalCount} of ${totalCount}`;
  }
  return `Uploading ${uploadCount} of ${totalCount}`;
};

export const getProgressPercentage = (
  fileIndex = 0,
  files: Array<File> = [],
  clientErrors: Array<BatchError> = [],
): number => {
  const uploadCount = fileIndex + batchSize + clientErrors.length;
  const totalCount = files.length + clientErrors.length;
  if (fileIndex + batchSize >= files.length) {
    return 100;
  }
  return Math.floor((uploadCount / totalCount) * 100);
};

export const uploadFiles = async (
  files: Array<File>,
  setFileIndex: (index: number) => void,
  mounted: RefObject<boolean>,
  purpose: string,
): Promise<BatchError[]> => {
  const batchFails: Array<BatchError> = [];
  for (const i of Array(Math.ceil(files.length / batchSize)).keys()) {
    if (!mounted.current) break;
    const filePromises: Array<Promise<unknown>> = files
      .slice(i * batchSize, i * batchSize + batchSize)
      .map((currentFile: File) => saveInvoice(currentFile, purpose));
    setFileIndex(i * batchSize);
    const results: Array<BulkUploadResponse> = await allSettled(filePromises); // eslint-disable-line
    results.forEach((result, index) => {
      if (result.status === 'rejected') {
        const fileIndex = i * batchSize + index;
        batchFails.push({
          fileName: files[fileIndex].name,
          size: Math.floor(files[fileIndex].size / 1000),
          message: result.reason?.errors?.[0] ?? 'Something went wrong!',
        });
      }
    });
  }
  return batchFails;
};

export const getPurpose = (tags, tab) => {
  const isJpmcMerchant = tags?.some((tag) => tag.toLowerCase() === JPMC_FEATURE_FLAG);
  if (isJpmcMerchant) {
    return UPLOAD_INVOICES_TYPE.JPMC;
  }
  if (tab === 0) {
    return UPLOAD_INVOICES_TYPE.OPGSP_INVOICE;
  }
  return UPLOAD_INVOICES_TYPE.OPGSP_AWB;
};

export const getTabs = (purposeCode) => {
  const tabs = [UPLOAD_INVOICE_BILL];
  if (AWB_PURPOSE_CODES.includes(purposeCode)) {
    tabs.push(UPLOAD_AIRWAY_BILL);
  }
  return tabs;
};
