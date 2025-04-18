import { merchantFetch } from 'merchant/utils/ajax';
import { RECON_API_BASE_URL } from 'merchant/views/Reconciliations/Dashboard/constants';
import {
  makeUpdatedReportConfigPayload,
  makeReportConfigPayload,
} from 'merchant/views/Reconciliations/helper';
import type { DownloadReportParams } from 'merchant/views/Reconciliations/SplitScreen/types';

const fetchProcessSourceColumns = async ({ processIds }: { processIds: string[] }) => {
  const fetchProcessesSourceColumnPayload = {
    merchant_process_ids: processIds.length > 0 ? processIds : [],
  };

  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_process/source_cols`,
    mode: 'live',
    method: 'post',
    data: fetchProcessesSourceColumnPayload,
  });

  if (response?.status_code !== 200) {
    throw response;
  }

  return response;
};

const joiningConfig = async ({ selectedProcessesItem, matchingFieldKeys }) => {
  const hasMultipleProcess =
    Array.isArray(selectedProcessesItem) && selectedProcessesItem.length >= 2;
  const joiningConfigPayload = {
    merchant_process_ids: Array.isArray(selectedProcessesItem)
      ? selectedProcessesItem.map((process) => process?.id)
      : [],
    joining_config:
      hasMultipleProcess && matchingFieldKeys.length > 0
        ? matchingFieldKeys.map((field) => ({
            matching_fields: Object.values(field).map((f: any) => {
              return {
                merchant_process_id: f.merchant_process_id,
                merchant_source_id: f.merchant_source_id,
                column: f.name,
                merchant_process_name: f.merchant_process_name,
                merchant_source_name: f.merchant_source_name,
              };
            }),
          }))
        : [],
  };

  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/joining_config`,
    mode: 'live',
    method: 'post',
    data: joiningConfigPayload,
  });

  if (response.status_code !== 200) {
    throw response;
  }

  return response;
};

const createReportConfig = async ({
  selectedProcessesItem,
  selectedColumnForReport,
  joiningConfig,
  reportName,
}) => {
  const reportConfigPayload = makeReportConfigPayload({
    selectedProcessesItem,
    selectedColumnForReport,
    joiningConfig,
    reportName,
  });

  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/config`,
    mode: 'live',
    method: 'post',
    data: reportConfigPayload,
  });

  if (response.status_code !== 200) {
    throw response;
  }

  return response;
};

const updateReportConfig = async ({
  configId,
  selectedColumnForReport,
  previousReportConfigData,
  reportName,
}) => {
  const updatedReportConfig = makeUpdatedReportConfigPayload({
    selectedColumnForReport,
    previousReportConfigData,
    reportName,
  });

  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/config/${configId}`,
    mode: 'live',
    method: 'patch',
    data: updatedReportConfig,
  });

  if (response.status_code !== 200) {
    throw response;
  }

  return response;
};

const fetchDownloadList = async ({ currentPage, first_id, last_id }) => {
  const downloadPayload = {
    sort_key: '',
    page: currentPage,
    offset: 0,
    page_size: 10,
    first_id,
    last_id,
  };

  const response = await merchantFetch({
    method: 'post',
    url: `${RECON_API_BASE_URL}/reporting/reporting_run`,
    mode: 'live',
    data: downloadPayload,
  });

  if (response.status_code !== 200) {
    throw response;
  }

  return response;
};

const downloadReportFile = async ({ reportId }) => {
  const downloadReportConfig = {
    reporting_run_log_id: reportId,
  };

  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/signed_url`,
    method: 'post',
    mode: 'live',
    data: downloadReportConfig,
  });

  if (response.status_code !== 200) {
    throw response;
  }

  return response;
};

const fetchReconStatusAndRemarkFiltersData = async ({ processIds }) => {
  const payload = { merchant_process_ids: processIds };
  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_process/recon_filters`,
    mode: 'live',
    method: 'post',
    data: payload,
  });

  if (response.status_code !== 200) {
    throw response;
  }

  return response;
};

const triggerDownloadReport = async (payload) => {
  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/trigger_report`,
    mode: 'live',
    method: 'post',
    data: payload,
  });

  if (response.status_code !== 200) {
    throw response;
  }

  return response;
};

const fetchProcessList = async () => {
  const response = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_process`,
    mode: 'live',
    method: 'get',
  });

  if (response?.status_code !== 200) {
    throw response;
  }

  return response;
};

const fetchReportList = async () => {
  const reportListRes = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/config`,
    mode: 'live',
    method: 'get',
  });

  if (reportListRes.status_code !== 200) {
    throw reportListRes;
  }

  return reportListRes;
};

const fetchReport = async ({ configId }) => {
  const reportListRes = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/config/${configId}`,
    mode: 'live',
    method: 'get',
  });

  if (reportListRes.status_code !== 200) {
    throw reportListRes;
  }

  return reportListRes;
};

const fetchSplitScreenSourceList = async ({
  runId,
  sourceId,
  filter = {},
  fromDate,
  toDate,
  first_id,
  last_id,
}) => {
  const fetchSplitScreenPayload = {
    run_id: runId,
    merchant_source_id: sourceId,
    page_size: 100,
    first_id,
    last_id,
    ...(fromDate ? { from_date: fromDate } : {}),
    ...(toDate ? { to_date: toDate } : {}),
    filters: Object.entries(filter).reduce<{ key: string; value: string }[]>(
      (acc, [key, value]) => {
        if (value) {
          acc.push({ key: key as string, value: value as string });
        }
        return acc;
      },
      [],
    ),
  };

  const fetchSourceListResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_process/recon-run/list`,
    mode: 'live',
    method: 'post',
    data: fetchSplitScreenPayload,
  });

  if (fetchSourceListResp.status_code !== 200) {
    throw fetchSourceListResp;
  }

  return fetchSourceListResp;
};

const fetchProcessRunList = async ({ processId }) => {
  const fetchRunsPayload = {
    filter: {
      ...(processId && { merchant_process_id: [processId] }),
    },
    page_size: 50,
  };
  const fetchProcessRunListResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_run`,
    mode: 'live',
    method: 'POST',
    data: fetchRunsPayload,
  });
  if (fetchProcessRunListResp.status_code !== 200) {
    throw fetchProcessRunListResp;
  }
  return fetchProcessRunListResp;
};

const fetchReconProcessDetail = async ({ processId }) => {
  const fetchProcessDetailResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_process/${processId}`,
    mode: 'live',
    method: 'GET',
  });
  if (fetchProcessDetailResp.status_code !== 200) {
    throw fetchProcessDetailResp;
  }
  return fetchProcessDetailResp;
};

const fetchingSplitScreenMatchingRecord = async ({ recordId }) => {
  const matchingRecordResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_process/record/${recordId}`,
    mode: 'live',
    method: 'GET',
  });
  if (matchingRecordResp.status_code !== 200) {
    throw matchingRecordResp;
  }
  return matchingRecordResp;
}

const deleteReconRun = async ({ runId }) => {
  const deleteReconRunRes = await merchantFetch({
    url: `${RECON_API_BASE_URL}/recon_run/${runId}`,
    mode: 'live',
    method: 'delete',
  });

  if (deleteReconRunRes?.status_code !== 200) {
    throw deleteReconRunRes;
  }

  return deleteReconRunRes;
};

const getPreSignedUrl = async ({ sourceId }) => {
  const payload = {
    source_id: sourceId,
  };

  const preSignelUrlResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/file_detail/config/get_upload_url`,
    method: 'post',
    data: payload,
  });

  if (preSignelUrlResp.status_code !== 200) {
    throw preSignelUrlResp;
  }

  return preSignelUrlResp;
};

const uploadFileToPresignedUrl = async ({ s3Url, file, sourceId }) => {
  const headers = new Headers();
  headers.append('Content-Type', `binary/octet-stream`);
  const requestOptions = {
    method: 'PUT',
    headers,
    body: file,
  };

  const response = await fetch(s3Url, requestOptions);

  if (response.status !== 200) {
    throw response;
  }

  return {
    success: true,
    status_code: response.status,
    data: {
      source_id: sourceId,
    },
  };
};

const createMlReconConfig = async ({ sources, processName }) => {
  const payload = {
    source_config:
      sources && sources.length > 0
        ? sources.map((source) => ({ upload_path: source.fileUploadPath, name: source.name }))
        : [],
    process_name: processName,
  };

  const mlReconConfigResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/mlconfig`,
    method: 'post',
    data: payload,
  });

  if (mlReconConfigResp.status_code !== 200) {
    throw mlReconConfigResp;
  }

  return mlReconConfigResp;
};

const fetchMlConfig = async ({ sessionId, auditLogId }) => {
  const payload = {
    session_id: sessionId,
    audit_log_id: auditLogId,
  };

  const fetchMlConfigResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/mlconfig/fetch_config`,
    method: 'post',
    data: payload,
  });

  if (fetchMlConfigResp.status_code !== 200) {
    throw fetchMlConfig;
  }

  return fetchMlConfigResp;
};

const generateMlReconConfig = async ({ columnConfig, auditLogId, created = true }) => {
  const payload = {
    column_config: columnConfig,
    audit_log_id: auditLogId,
    create_config: created,
  };

  const generateMlReconConfigResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/mlconfig/generate_config`,
    method: 'post',
    data: payload,
  });

  if (generateMlReconConfigResp.status_code !== 200) {
    throw generateMlReconConfigResp;
  }

  return generateMlReconConfigResp;
};

const getMlReconStats = async ({ auditLogId }) => {
  const payload = {
    audit_log_id: auditLogId,
  };

  const mlReconStatsResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/mlconfig/recon_stats`,
    method: 'post',
    data: payload,
  });

  if (mlReconStatsResp.status_code !== 200) {
    throw mlReconStatsResp;
  }

  return mlReconStatsResp;
};

const saveMlGeneratedProcess = async ({ auditLogId }) => {
  const payload = {
    audit_log_id: auditLogId,
  };

  const saveProcessResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/mlconfig/confirm_process`,
    method: 'post',
    data: payload,
  });

  if (saveProcessResp.status_code !== 200) {
    throw saveProcessResp;
  }

  return saveProcessResp;
};

const downloadReconReport = async ({ runId }) => {
  const downloadReportResp = await merchantFetch({
    url: `${RECON_API_BASE_URL}/file_detail/report/signed_url?file_detail_workflow_id=${runId}`,
    mode: 'live',
    method: 'GET',
  });

  if (downloadReportResp.status_code !== 200) {
    throw downloadReportResp;
  }

  return downloadReportResp;
}


const downloadSplitScreenReport = async ({ runId, sources, fromDate, toDate, filter }: DownloadReportParams) => {
  const downloadReportPayload = {
    trigger_type: 'multi-sheet-manual',
    run_ids: [runId],
    merchant_source_ids: sources,
    transaction_date_range: {
      from_date: fromDate,
      to_date: toDate,
    },
    column_filters: Object.entries(filter).reduce<{ key: string; value: string }[]>(
      (acc, [key, value]) => {
        if (value) {
          acc.push({ key, value: value as string });
        }
        return acc;
      },
      [],
    ),
  };

  const downloadReport = await merchantFetch({
    url: `${RECON_API_BASE_URL}/reporting/trigger_report`,
    mode: 'live',
    method: 'post',
    data: downloadReportPayload,
  });

  if (downloadReport.status_code !== 200) {
    throw downloadReport;
  }

  return downloadReport;
};

export {
  fetchProcessSourceColumns,
  createReportConfig,
  updateReportConfig,
  fetchDownloadList,
  downloadReportFile,
  fetchReconStatusAndRemarkFiltersData,
  triggerDownloadReport,
  fetchProcessList,
  fetchReportList,
  joiningConfig,
  fetchReport,
  fetchSplitScreenSourceList,
  fetchProcessRunList,
  fetchReconProcessDetail,
  fetchingSplitScreenMatchingRecord,
  deleteReconRun,
  getPreSignedUrl,
  uploadFileToPresignedUrl,
  createMlReconConfig,
  fetchMlConfig,
  generateMlReconConfig,
  getMlReconStats,
  saveMlGeneratedProcess,
  downloadReconReport,
  downloadSplitScreenReport,
};
