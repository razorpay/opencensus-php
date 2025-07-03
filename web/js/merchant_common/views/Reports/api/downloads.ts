import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { reportsLongPoll } from './poll';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  LongPollInitiatorArgs,
  LongPollReturnType,
  ReportsFetchAPIParams,
  ReportsFetchHeaders,
  ResPayload,
  ResType,
} from './types';

const logProcessingStatuses = ['created', 'processing'];

export const isLogInProgress = (logStatus: string): boolean =>
  logProcessingStatuses.includes(logStatus);

export const fetchDownloadLogs = (
  { page = 1, filter = '' }: ReportsFetchAPIParams,
  headers: ReportsFetchHeaders,
  isEdgeEnabled: boolean,
): Promise<ResType<ResPayload<BaseLogType>>> => {
  const [type, ...value] = filter.split('.');

  const LOGS_COUNT = 20;

  const paramHeader =
    type === 'header'
      ? {
          ...headers,
          [value[0]]: value[1],
        }
      : headers;

  const filterParams = type === 'param' ? value[0] : '';

  const paginationParams = `limit=${LOGS_COUNT}&offset=${LOGS_COUNT * (page - 1)}`;

  const pathPrefix = isEdgeEnabled ? 'reporting/merchant' : 'reporting';

  return merchantFetch({
    url: `${pathPrefix}/logs?${paginationParams}${filterParams}`,
    headers: paramHeader,
  });
};

export const fetchLogDetail = (
  logId: string,
  accountId: string,
  headers: Record<string, string>,
): Promise<ResType<BaseLogType>> => {
  return merchantFetch({
    url: `reporting/logs/${logId}`,
    ...(!!accountId && { accountId }),
    headers,
  });
};

export const initiateLogsPoll = ({
  queryParams,
  headers,
  pollResSuccessCallback,
  pollResFailedCallback,
  onPollStopCallback,
  isEdgeEnabled,
}: LongPollInitiatorArgs<BaseLogType>): LongPollReturnType<BaseLogType> => {
  return reportsLongPoll({
    fetchFunc: () => fetchDownloadLogs(queryParams, headers, isEdgeEnabled),
    // when sent true from validator polling will stop
    // continue polling if status is in progress
    validator: (data) => !data?.items.some((log) => isLogInProgress(log.status)),
    pollResSuccessCallback,
    pollResFailedCallback,
    onPollStopCallback,
  });
};
