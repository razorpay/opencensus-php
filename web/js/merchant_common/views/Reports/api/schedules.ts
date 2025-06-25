import { ScheduleServerPayload } from 'merchant_common/views/Reports/types/schedule';
import { reportsLongPoll } from './poll';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  CreateScheduleAPIFnParams,
  LongPollInitiatorArgs,
  LongPollReturnType,
  ReportsFetchAPIParams,
  ReportsFetchHeaders,
  ResPayload,
  ResType,
  ScheduleAPIEditParams,
  ScheduleAPIFnParams,
} from './types';

const getPathPrefix = (isEdgeEnabled: boolean): string =>
  isEdgeEnabled ? 'reporting/merchant' : 'reporting';

export const getSchedules = (
  { page = 1, filter = '' }: ReportsFetchAPIParams,
  headers: ReportsFetchHeaders,
  isEdgeEnabled: boolean,
): Promise<ResType<ResPayload<ScheduleServerPayload>>> => {
  const [type, ...value] = filter.split('.');
  const SCHEDULE_COUNT = 20;

  const paramHeader =
    type === 'header'
      ? {
          ...headers,
          [value[0]]: value[1],
        }
      : headers;

  const filterParams = type === 'param' ? value[0] : '';

  const paginationParams = `limit=${SCHEDULE_COUNT}&offset=${SCHEDULE_COUNT * (page - 1)}`;

  const pathPrefix = getPathPrefix(isEdgeEnabled);

  return merchantFetch({
    url: `${pathPrefix}/schedules?${paginationParams}${filterParams}`,
    headers: paramHeader,
  });
};

export const createSchedule = <T>({
  headers,
  payload,
  method,
  scheduleId,
  isEdgeEnabled,
}: CreateScheduleAPIFnParams<T>): Promise<ResType<ScheduleServerPayload>> => {
  const pathPrefix = getPathPrefix(isEdgeEnabled);

  return merchantFetch({
    url: `${pathPrefix}/schedules${scheduleId ? `/${scheduleId}` : ''}`,
    headers,
    method,
    data: {
      payload,
    },
  });
};

export const deleteSchedule = ({
  headers,
  scheduleId,
  isEdgeEnabled,
}: ScheduleAPIFnParams): Promise<void> => {
  const pathPrefix = getPathPrefix(isEdgeEnabled);

  return merchantFetch({
    url: `${pathPrefix}/schedules/${scheduleId}`,
    headers,
    method: 'delete',
  });
};

export const pauseSchedule = ({
  headers,
  scheduleId,
  isEdgeEnabled,
}: ScheduleAPIFnParams): Promise<void> => {
  const pathPrefix = getPathPrefix(isEdgeEnabled);

  return merchantFetch({
    url: `${pathPrefix}/schedules/${scheduleId}`,
    headers,
    method: 'patch',
    data: {
      payload: {
        status: 'paused',
      },
    },
  });
};

export const resumeSchedule = ({
  headers,
  scheduleId,
  isEdgeEnabled,
}: ScheduleAPIFnParams): Promise<void> => {
  const pathPrefix = getPathPrefix(isEdgeEnabled);

  return merchantFetch({
    url: `${pathPrefix}/schedules/${scheduleId}`,
    headers,
    method: 'patch',
    data: {
      payload: {
        status: 'active',
      },
    },
  });
};

export const editSchedule = <T>({
  headers,
  scheduleId,
  payload,
  isEdgeEnabled,
}: ScheduleAPIEditParams<T>): Promise<ResType<ScheduleServerPayload>> => {
  const pathPrefix = getPathPrefix(isEdgeEnabled);

  return merchantFetch({
    url: `${pathPrefix}/schedules/${scheduleId}`,
    headers,
    method: 'patch',
    data: {
      payload,
    },
  });
};

const scheduleStatuses = ['active'];
export const isScheduleInProgress = (scheduleStatus?: string): boolean =>
  Boolean(scheduleStatus && scheduleStatuses.includes(scheduleStatus));

export const initiateSchedulesPoll = ({
  queryParams,
  headers = {},
  pollResSuccessCallback = () => {},
  pollResFailedCallback = () => {},
  onPollStopCallback = () => {},
  isEdgeEnabled,
}: LongPollInitiatorArgs<ScheduleServerPayload>): LongPollReturnType<ScheduleServerPayload> => {
  return reportsLongPoll({
    fetchFunc: () => getSchedules(queryParams, headers, isEdgeEnabled),
    validator: (data) => !data?.items.some((schedule) => isScheduleInProgress(schedule.status)),
    pollResSuccessCallback,
    pollResFailedCallback,
    onPollStopCallback,
  });
};
