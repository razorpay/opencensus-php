import {
  BatchSectionValidParams,
  DurationSectionValidParams,
  ReportSectionValidParams,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/types';
import {
  BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
  BATCH_PAYMENT_PAGE_PAYMENT_REPORT,
  CONFIG_TYPE_BATCH_PAGES,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/constants';

import {
  isBatchIdsFieldValid,
  isCustomDurationFieldValid,
  isDefaultDurationFieldValid,
  isDelimiterFieldValid,
  isPaymentStatusFieldValid,
} from './fieldValidators';

export const isReportSectionValid = ({
  selectedConfig,
  selectedFormat,
  selectedDelimiter,
}: ReportSectionValidParams): boolean => {
  return Boolean(selectedConfig) && isDelimiterFieldValid({ selectedFormat, selectedDelimiter });
};

export const isRecipientsSectionValid = ({
  isRecipientsEnabled,
  recipients,
}: {
  isRecipientsEnabled: boolean;
  recipients: string[];
}): boolean => {
  if (isRecipientsEnabled) {
    return Array.isArray(recipients) && recipients.length > 0;
  }

  return true;
};

export const isDurationSectionValid = ({
  selectedConfig,
  customDurationRange,
  selectedPredefinedDurationRange,
  isCustomDurationEnabled,
}: DurationSectionValidParams): boolean => {
  if (selectedConfig?.name === BATCH_PAYMENT_PAGE_PAYMENT_REPORT) {
    return true;
  } else if (isCustomDurationEnabled) {
    return isCustomDurationFieldValid(customDurationRange);
  }

  return isDefaultDurationFieldValid(selectedPredefinedDurationRange);
};

export const isBatchSectionValid = ({
  selectedConfig,
  selectedBatchPage,
  selectedBatchIds,
  selectedPaymentStatus,
  batchIds,
}: BatchSectionValidParams): boolean => {
  if (selectedConfig?.type !== CONFIG_TYPE_BATCH_PAGES) {
    return true;
  }

  const hasSelectedPage = Boolean(selectedBatchPage);

  if (selectedConfig?.name === BATCH_PAYMENT_PAGE_PAYMENT_REPORT) {
    return hasSelectedPage;
  } else if (selectedConfig?.name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT) {
    return (
      hasSelectedPage &&
      isBatchIdsFieldValid({ selectedBatchPage, selectedBatchIds, batchIds }) &&
      isPaymentStatusFieldValid(selectedPaymentStatus)
    );
  }

  return true;
};
