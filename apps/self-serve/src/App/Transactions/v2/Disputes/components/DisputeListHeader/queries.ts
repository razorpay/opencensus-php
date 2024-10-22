import { fetch } from '@dashboard/shared-utils/rest-fetch';
const DOWNLOAD_REPORT_ENDPOINT = 'merchant/report/dispute/download';

export const fetchDownloadReportData = async () => {
  const response = await fetch({
    url: DOWNLOAD_REPORT_ENDPOINT,
    method: 'GET',
  });
  return response;
};
