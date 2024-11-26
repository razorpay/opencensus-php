import { fetch } from 'common/services/rest/rest-fetch';

const DOWNLOAD_REPORT_ENDPOINT = 'merchant/report/dispute/download';

export const fetchDownloadReportData = async (params: string) => {
  const response = await fetch({
    url: `${DOWNLOAD_REPORT_ENDPOINT}${params}`,
    method: 'GET',
  });
  return response;
};
