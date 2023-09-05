import { UploadConfigType } from './types';

export const TABS: Array<string> = ['Upload Invoice Bill'];

const FILETYPES = ['application/pdf', 'image/jpeg', 'image/png'];

//upload configs
export const UPLOAD_CONFIG: UploadConfigType = {
  batchSize: 10,
  retries: 1,
  maxFiles: 500,
  acceptedTypes: FILETYPES,
};
