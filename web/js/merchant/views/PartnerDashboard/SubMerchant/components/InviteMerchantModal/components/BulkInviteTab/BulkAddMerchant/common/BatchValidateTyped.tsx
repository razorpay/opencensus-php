import { ComponentType, ReactNode } from 'react';

import { CommonApiResponse } from 'common/typings';
import BatchValidateJS from 'merchant/containers/BatchNew/Validate';

type BatchValidateProps = {
  batchClass: string;
  batchType: string;
  batchTypeText: string;
  clickToUploadAnalytics?: () => void;
  onFileRemove?: () => void;
  maxFileSize: number;
  maxRows: number;
  nullStatusNotification?: ReactNode;
  onValidation: (response: { file_id: string; processable_count: number }, name: string) => void;
  onValidationFail?: (error: Error) => void;
  sampleFileDownloadAnalytics: () => void;
  sampleUrl: string;
  validateBatch: () => Promise<CommonApiResponse<{ status: boolean }, string[]>>;
};

// Note: here we are re-exporting a JS component with necessary prop types;
const BatchValidate = BatchValidateJS as ComponentType<BatchValidateProps>;

export default BatchValidate;
