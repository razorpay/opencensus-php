import { getAvailableDelimiter } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/utils';
import { Delimiter, Format } from 'merchant_common/views/Reports/types';
import {
  BatchId,
  BatchPage,
  PaymentStatus,
  PredefinedDurationType,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/types';
import { validateDurationRange } from '.';
import { SelectedRangeType } from 'merchant_common/views/Reports/components/types';

export const isDelimiterFieldValid = ({
  selectedFormat,
  selectedDelimiter,
}: {
  selectedFormat?: Format;
  selectedDelimiter?: Delimiter;
}): boolean => {
  const isDelimiterAvailable = getAvailableDelimiter(selectedFormat).length > 0;

  if (selectedFormat?.value && isDelimiterAvailable) {
    return Boolean(selectedDelimiter?.value);
  }

  return true;
};

export const isCustomDurationFieldValid = (customDurationRange?: SelectedRangeType): boolean =>
  validateDurationRange(customDurationRange?.startDate, customDurationRange?.endDate);

export const isDefaultDurationFieldValid = (
  selectedPredefinedDurationRange?: PredefinedDurationType,
): boolean => {
  if (selectedPredefinedDurationRange?.label) {
    const { startDate, endDate } = selectedPredefinedDurationRange.value;
    return validateDurationRange(startDate, endDate);
  } else {
    return false;
  }
};

export const isBatchIdsFieldValid = ({
  selectedBatchPage,
  selectedBatchIds,
  batchIds,
}: {
  selectedBatchPage?: BatchPage;
  selectedBatchIds?: BatchId[];
  batchIds: BatchId[];
}): boolean => {
  if (Boolean(batchIds.length) && selectedBatchPage?.id) {
    return Array.isArray(selectedBatchIds) && selectedBatchIds.length > 0;
  }

  return true;
};

export const isPaymentStatusFieldValid = (status: PaymentStatus[]): boolean => {
  return Array.isArray(status) && status.length > 0;
};
