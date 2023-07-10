import {
  ALL_OPTION,
  BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
  BATCH_PAYMENT_PAGE_PAYMENT_REPORT,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/constants';
import { GeneratePayloadParams } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/types';
import { BaseLogPayloadType, TemplateOverridesType } from 'merchant_common/views/Reports/types/log';

const getPaymentFilters = ({
  selectedBatchPage,
  selectedBatchIds,
  selectedPaymentStatus,
  selectedConfig,
}): TemplateOverridesType['filters'] => {
  const batchPageId = selectedBatchPage?.id?.replace('pl_', '');

  const batchIds =
    selectedBatchIds?.length && selectedBatchIds[0].value !== ALL_OPTION.value // Does it have "all" option
      ? (selectedBatchIds.map(({ value }) => value) as string[])
      : undefined;

  const paymentStatus =
    selectedPaymentStatus?.length && selectedPaymentStatus[0].value !== ALL_OPTION.value // Does it have "all" option
      ? (selectedPaymentStatus.map(({ value }) => value) as string[])
      : undefined;

  const shouldFilterExist = [batchPageId, batchIds, paymentStatus].some((item) => Boolean(item));

  if (!shouldFilterExist) {
    return undefined;
  } else if (selectedConfig?.name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT) {
    return {
      payment_page_records: {
        payment_link_id: batchPageId ? { op: 'IN', values: [batchPageId] } : undefined,
        batch_id: batchIds ? { op: 'IN', values: batchIds } : undefined,
        status: paymentStatus ? { op: 'IN', values: paymentStatus } : undefined,
      },
    };
  } else if (selectedConfig?.name === BATCH_PAYMENT_PAGE_PAYMENT_REPORT) {
    return {
      payment_links: {
        id: batchPageId ? { op: 'IN', values: [batchPageId] } : undefined,
      },
    };
  }

  return undefined;
};

const getTemplateOverrideFilters = ({
  selectedBatchPage,
  selectedBatchIds,
  selectedPaymentStatus,
  selectedConfig,
}) => {
  const paymentFilters = getPaymentFilters({
    selectedBatchPage,
    selectedBatchIds,
    selectedPaymentStatus,
    selectedConfig,
  });

  // Room for other filters

  return Boolean(paymentFilters)
    ? {
        ...paymentFilters,
      }
    : undefined;
};

const getDurationCoveredInReports = ({
  selectedConfig,
  selectedBatchPage,
  isCustomDurationEnabled,
  customDurationRange,
  selectedPredefinedDurationRange,
}): { start_time: number; end_time: number } => {
  switch (true) {
    case selectedConfig?.name === BATCH_PAYMENT_PAGE_PAYMENT_REPORT:
      return {
        start_time: selectedBatchPage?.created_at,
        end_time: selectedBatchPage?.created_at,
      };
    case isCustomDurationEnabled:
      return {
        start_time: customDurationRange!.startDate.clone().unix(),
        end_time: customDurationRange!.endDate.clone().unix(),
      };
    default:
      return {
        start_time: selectedPredefinedDurationRange!.value.startDate.clone().unix(),
        end_time: selectedPredefinedDurationRange!.value.endDate.clone().unix(),
      };
  }
};

export const generatePayload = ({
  selectedConfig,
  saveReportAs,
  selectedFormat,
  selectedDelimiter,
  selectedBatchPage,
  selectedBatchIds,
  selectedPaymentStatus,
  customDurationRange,
  selectedPredefinedDurationRange,
  recipients,
  isCustomDurationEnabled,
  isRecipientsEnabled,
}: GeneratePayloadParams): BaseLogPayloadType => {
  const extension = Boolean(selectedFormat?.value) ? selectedFormat?.value : undefined;
  const filename = Boolean(saveReportAs) ? saveReportAs : undefined;
  const delimiter = Boolean(selectedDelimiter?.value) ? selectedDelimiter?.value : undefined;
  const filters = getTemplateOverrideFilters({
    selectedBatchIds,
    selectedBatchPage,
    selectedPaymentStatus,
    selectedConfig,
  });

  const shouldIncludeFileMeta = Boolean(extension) || Boolean(filename) || Boolean(delimiter);
  // Check whether to include template overrides property.
  const shouldIncludeTemplateOverrides = shouldIncludeFileMeta || Boolean(filters);

  const payload = {
    config_id: selectedConfig?.id as string,
    emails: isRecipientsEnabled && recipients?.length ? recipients : undefined,
    template_overrides: shouldIncludeTemplateOverrides
      ? {
          file_meta: shouldIncludeFileMeta
            ? {
                extension,
                filename,
                delimiter,
              }
            : undefined,
          filters,
        }
      : undefined,
    ...getDurationCoveredInReports({
      customDurationRange,
      isCustomDurationEnabled,
      selectedBatchPage,
      selectedConfig,
      selectedPredefinedDurationRange,
    }),
  };

  return payload;
};
