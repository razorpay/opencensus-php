import { merchantFetch } from 'merchant/utils/ajax';
import { BaseLogPayloadType } from 'merchant_common/views/Reports/types/log';

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
