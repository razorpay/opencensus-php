import { merchantFetch } from 'merchant/utils/ajax';
import { BaseLogPayloadType } from 'merchant_common/views/Reports/types/log';
import { BatchPage } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/types';
import { getQueryString } from 'merchant_common/views/Reports/utils/commonUtils';
import { FILE_UPLOAD_PAGE } from 'merchant_common/views/Reports/constants';

import { ResPayload, ResType } from './types';

type DownloadReportArgType = {
  payload: BaseLogPayloadType;
  headers: Record<string, string>;
  generatedBy: string;
  accountId?: string;
};

/**
 *
 * @param {DownloadReportArgType} config config required for downloading report
 * @returns
 */
export const downloadNewReport = async ({
  payload,
  headers,
  generatedBy,
  accountId,
}: DownloadReportArgType): Promise<any> => {
  const finalPayload = {
    ...payload,
    generated_by: generatedBy,
  };

  return merchantFetch({
    url: 'reporting/logs',
    method: 'post',
    data: finalPayload,
    ...(accountId && { accountId }),
    headers,
  });
};

export function getPaymentPagesFileUploadPages(
  params: { title?: string } = {},
): Promise<ResType<ResPayload<BatchPage>>> {
  const queryString = getQueryString({ title: params.title, viewType: FILE_UPLOAD_PAGE });

  return merchantFetch(`payment_pages?${queryString}`);
}

export function getBatchIds(batchPaymentPageId: string): Promise<ResType<string[]>> {
  return merchantFetch(`payment_pages/${batchPaymentPageId}/batches?all_batches=1`);
}
