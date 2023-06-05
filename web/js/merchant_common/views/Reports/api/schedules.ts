import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import { reportsLongPoll } from './poll';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  CreateScheduleAPIFnParams,
  LongPollInitiatorArgs,
  LongPollReturnType,
  ReportsFetchAPIParams,
  ReportsFetchHeaders,
  ResType,
  ScheduleAPIEditParams,
  ScheduleAPIFnParams,
} from './types';

export const getSchedules = (
  { page = 1, filter = '' }: ReportsFetchAPIParams,
  headers: ReportsFetchHeaders,
): Promise<ResType<ScheduleType>> => {
  const [type, ...value] = filter.split('.');
  const SCHEDULE_COUNT = 20;

  const paramHeader =
    type === 'header'
      ? {
          ...headers,
          [value[0]]: value[1],
        }
      : headers;

  const filterParams = type === 'param' ? value : '';

  const paginationParams = `limit=${SCHEDULE_COUNT}&offset=${SCHEDULE_COUNT * (page - 1)}`;

  return merchantFetch({
    url: `reporting/schedules?${paginationParams}${filterParams}`,
    headers: paramHeader,
  });
};

export const createSchedule = <T>({
  headers,
  payload,
}: CreateScheduleAPIFnParams<T>): Promise<ResType<ScheduleType>> => {
  return merchantFetch({
    url: `reporting/schedules`,
    headers,
    method: 'post',
    data: payload,
  });
};

export const deleteSchedule = ({ headers, scheduleId }: ScheduleAPIFnParams): Promise<void> => {
  return merchantFetch({
    url: `reporting/schedules/${scheduleId}`,
    headers,
    method: 'delete',
  });
};

export const pauseSchedule = ({ headers, scheduleId }: ScheduleAPIFnParams): Promise<void> => {
  return merchantFetch({
    url: `reporting/schedules/${scheduleId}`,
    headers,
    method: 'patch',
    data: {
      payload: {
        status: 'paused',
      },
    },
  });
};

export const resumeSchedule = ({ headers, scheduleId }: ScheduleAPIFnParams): Promise<void> => {
  return merchantFetch({
    url: `reporting/schedules/${scheduleId}`,
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
}: ScheduleAPIEditParams<T>): Promise<ResType<ScheduleType>> => {
  return merchantFetch({
    url: `reporting/schedules/${scheduleId}`,
    headers,
    method: 'patch',
    data: {
      payload,
    },
  });
};

const scheduleStatuses = ['active'];
export const isScheduleInProgress = (scheduleStatus: string): boolean =>
  scheduleStatuses.includes(scheduleStatus);

export const initiateSchedulesPoll = ({
  queryParams,
  headers = {},
  pollResSuccessCallback = () => {},
  pollResFailedCallback = () => {},
  onPollStopCallback = () => {},
}: LongPollInitiatorArgs<ScheduleType>): LongPollReturnType<ScheduleType> => {
  return reportsLongPoll({
    fetchFunc: () => getSchedules(queryParams, headers),
    validator: (data) => !data?.items.some((schedule) => isScheduleInProgress(schedule.status)),
    pollResSuccessCallback,
    pollResFailedCallback,
    onPollStopCallback,
  });
};
