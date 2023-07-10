import {
  BatchId,
  BatchPage,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/types';
import { ALL_OPTION } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/constants';
import { ResPayload, ResType } from 'merchant_common/views/Reports/api/types';

export const parseBatchPages = (data: ResType<ResPayload<BatchPage>>): BatchPage[] => {
  const { items = [] } = data?.data || {};

  return items;
};

export const getOptionsAccordingToAllOptionSelected = <
  SelectedOptions extends Array<{ value: string }>,
>({
  currSelectedOptions,
  prevSelectedOptions,
}: Record<string, SelectedOptions>): SelectedOptions => {
  const hasAllOptionInPreviouslySelectedValues =
    prevSelectedOptions?.[0]?.value === ALL_OPTION.value;
  const hasAllOption = Boolean(
    currSelectedOptions?.[0]?.value === ALL_OPTION.value ||
      currSelectedOptions?.[currSelectedOptions.length - 1]?.value === ALL_OPTION.value,
  );

  if (hasAllOption && !hasAllOptionInPreviouslySelectedValues) {
    return [ALL_OPTION] as unknown as SelectedOptions;
  } else if (hasAllOption && hasAllOptionInPreviouslySelectedValues) {
    return currSelectedOptions.slice(1) as unknown as SelectedOptions;
  }

  return currSelectedOptions;
};

export const formatBatchIds = (ids: string[]): BatchId[] => {
  const formattedIds = ids.map((id) => ({ label: id, value: id }));

  return [ALL_OPTION, ...formattedIds];
};
