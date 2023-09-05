import { RefObject } from 'react';
import { BatchError, BulkUploadResponse, TransfromDroppedFilesType } from './types';
import { saveInvoice } from './services';
import { UPLOAD_CONFIG } from './constants';

const { batchSize, acceptedTypes } = UPLOAD_CONFIG;

export const transfromDroppedFiles = (data: DataTransfer): TransfromDroppedFilesType => {
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
): Promise<BatchError[]> => {
  const batchFails: Array<BatchError> = [];
  for (const i of Array(Math.ceil(files.length / batchSize)).keys()) {
    if (!mounted.current) break;
    const filePromises: Array<Promise<unknown>> = files
      .slice(i * batchSize, i * batchSize + batchSize)
      .map((currentFile: File) => saveInvoice(currentFile));
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
