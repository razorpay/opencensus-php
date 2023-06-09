import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { reportsLongPoll } from './poll';
import {
  LongPollInitiatorArgs,
  LongPollReturnType,
  ReportsFetchAPIParams,
  ReportsFetchHeaders,
  ResPayload,
  ResType,
} from './types';
import { merchantFetch } from 'merchant/utils/ajax';

export const fetchSchedulesRunLogs = (
  { page = 1, filter = '', scheduleId = '' }: ReportsFetchAPIParams,
  headers?: ReportsFetchHeaders,
): Promise<ResType<ResPayload<BaseLogType>>> => {
  // For better understanding:
  // filter can be of type:
  // 1. param.&sort_by=created_at&sort_order=desc
  // 2. header.status.processing
  const [type, ...value] = filter.split('.');

  const LOGS_COUNT = 10;

  const paramHeader =
    type === 'header'
      ? {
          ...headers,
          [value[0]]: value[1],
        }
      : headers;

  const filterParams = type === 'param' ? value[0] : '';

  const paginationParams = `limit=${LOGS_COUNT}&offset=${LOGS_COUNT * (page - 1)}`;

  return merchantFetch({
    url: `reporting/logs?${paginationParams}${filterParams}&schedule_id=${scheduleId}`,
    headers: paramHeader,
  });
};

const logProcessingStatuses = ['created', 'processing'];
const isLogInProgress = (logStatus) => logProcessingStatuses.includes(logStatus);

export const initiateScheduleRunHistoryPoll = ({
  queryParams,
  headers,
  pollResSuccessCallback,
  pollResFailedCallback = () => {},
  onPollStopCallback = () => {},
}: LongPollInitiatorArgs<BaseLogType>): LongPollReturnType<BaseLogType> => {
  return reportsLongPoll({
    fetchFunc: () => fetchSchedulesRunLogs(queryParams, headers),
    // when sent true from validator polling will stop
    // continue polling if status is in progress
    validator: (data) => !data?.items.some((log) => isLogInProgress(log.status)),
    pollResSuccessCallback,
    pollResFailedCallback,
    onPollStopCallback,
  });
};
